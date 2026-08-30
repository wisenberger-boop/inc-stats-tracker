param(
  [Parameter(Mandatory=$true)][string]$TaskFile,
  [Parameter(Mandatory=$true)][string]$ResultsFile,
  [string]$OutputFile,
  [switch]$DryRun
)
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$rootPrefix = $root.TrimEnd('\') + '\'
function Resolve-ProjectFile([string]$RelativePath, [string]$Label) {
  if ([IO.Path]::IsPathRooted($RelativePath)) { throw "$Label must be project-relative." }
  $resolved = (Resolve-Path -LiteralPath (Join-Path $root $RelativePath)).Path
  if (-not $resolved.StartsWith($rootPrefix, [StringComparison]::OrdinalIgnoreCase)) { throw "$Label escapes the project root." }
  return $resolved
}
$config = Get-Content -LiteralPath (Join-Path $root '.claude-review.json') -Raw | ConvertFrom-Json
$taskPath = Resolve-ProjectFile $TaskFile 'Task file'
$resultsPath = Resolve-ProjectFile $ResultsFile 'Results file'
$reviewRoot = [IO.Path]::GetFullPath((Join-Path $root $config.reviewOutputRoot))
if (-not $OutputFile) { $OutputFile = Join-Path $config.reviewOutputRoot "$([IO.Path]::GetFileNameWithoutExtension($TaskFile))-claude-review.json" }
$outputPath = [IO.Path]::GetFullPath((Join-Path $root $OutputFile))
if (-not $outputPath.StartsWith(($reviewRoot.TrimEnd('\') + '\'), [StringComparison]::OrdinalIgnoreCase)) { throw 'Review output escapes the configured local review root.' }

$sections = [Collections.Generic.List[string]]::new()
foreach ($relative in $config.controlFiles) {
  if ([IO.Path]::IsPathRooted($relative)) { throw 'Control file must be project-relative.' }
  if (-not (Test-Path -LiteralPath (Join-Path $root $relative))) { continue }
  $path = Resolve-ProjectFile $relative 'Control file'
  $sections.Add("## FILE: $relative`n$(Get-Content -LiteralPath $path -Raw)")
}
$taskText = Get-Content -LiteralPath $taskPath -Raw
$resultsText = Get-Content -LiteralPath $resultsPath -Raw
$sections.Add("## TASK: $TaskFile`n$taskText")
$sections.Add("## RESULTS: $ResultsFile`n$resultsText")
$sections.Add("## GIT STATUS`n$(git -C $root status --short)")
$workingDiff = git -C $root diff --no-ext-diff --no-renames
if ($LASTEXITCODE -ne 0) { throw 'Cannot assemble the working-tree diff.' }
if ($workingDiff) { $sections.Add("## WORKING TREE DIFF`n$workingDiff") }
$sections.Add("## RECENT COMMITS`n$(git -C $root log -5 --oneline --decorate)")
$commitCandidates = @([regex]::Matches("$taskText`n$resultsText", '(?<![0-9A-Za-z])[0-9a-f]{7,40}(?![0-9A-Za-z])') | ForEach-Object Value | Select-Object -Unique)
$commits = @($commitCandidates | Where-Object {
  git -C $root rev-parse --verify --quiet "$_^{commit}" 2>$null | Out-Null
  $LASTEXITCODE -eq 0
})
$skippedCommits = @($commitCandidates | Where-Object { $_ -notin $commits })
if ($skippedCommits.Count -gt 0) { $sections.Add("## EXTERNAL OR UNAVAILABLE COMMIT REFERENCES`n$($skippedCommits -join "`n")") }
$commits = @($commits | ForEach-Object { (git -C $root rev-parse --verify "$_^{commit}").Trim() })
$headCommit = (git -C $root rev-parse --verify HEAD).Trim()
if ($LASTEXITCODE -ne 0 -or -not $headCommit) { throw 'Cannot resolve current HEAD for review evidence.' }
$commits = @($commits + $headCommit | Select-Object -Unique)
foreach ($commit in $commits) {
  $diff = git -C $root show --format=fuller --no-ext-diff --no-renames --find-renames=0 $commit
  if ($LASTEXITCODE -ne 0) { throw "Cannot assemble Git evidence for $commit." }
  $sections.Add("## REVIEW COMMIT: $commit`n$diff")
}
$packet = $sections -join "`n`n"
$packetBytes = [Text.Encoding]::UTF8.GetByteCount($packet)
if ($packetBytes -gt [int]$config.maxPacketBytes) { throw "Review packet exceeds the configured limit: $packetBytes bytes." }
if ($DryRun) { Write-Output "dry_run=passed packet_bytes=$packetBytes commits=$($commits -join ',') sections=$($sections.Count)"; exit 0 }

$roleLock = 'ROLE LOCK: Claude is reviewer only. Do not edit implementation files. Do not execute the next task set. Only review task/result documents, commits, diffs, verification, and plan alignment.'
$prompt = "$roleLock`n`nIndependently review the supplied task, results, diffs, verification claims, safety boundaries, and plan alignment. You have no tools and cannot modify files or systems. Return only JSON matching the supplied schema.`n`n$packet"
$schema = @{type='object';additionalProperties=$false;required=@('verdict','summary','blockingIssues','nonBlockingRecommendations','nextAction','reviewedCommit');properties=@{verdict=@{type='string';enum=@('PASS','FAIL')};summary=@{type='string'};blockingIssues=@{type='array';items=@{type='object';additionalProperties=$false;required=@('severity','title','evidence','recommendedFix');properties=@{severity=@{type='string';enum=@('P0','P1','P2')};title=@{type='string'};evidence=@{type='string'};recommendedFix=@{type='string'}}}};nonBlockingRecommendations=@{type='array';items=@{type='string'}};nextAction=@{type='string';enum=@('proceed','fix-and-rereview','escalate-to-user')};reviewedCommit=@{type='string'}}} | ConvertTo-Json -Depth 12 -Compress
$claude = (Get-Command claude -ErrorAction Stop).Source
$info = [Diagnostics.ProcessStartInfo]::new()
$info.FileName=$claude; $info.UseShellExecute=$false; $info.RedirectStandardInput=$true; $info.RedirectStandardOutput=$true; $info.RedirectStandardError=$true
$escapedSchema = $schema.Replace('\','\\').Replace('"','\"')
$info.Arguments = "-p --safe-mode --output-format=json --permission-mode=dontAsk --tools=`"`" --max-budget-usd=$($config.maxBudgetUsd) --json-schema `"$escapedSchema`""
$process=[Diagnostics.Process]::new(); $process.StartInfo=$info
if (-not $process.Start()) { throw 'Claude review process did not start.' }
$stdout=$process.StandardOutput.ReadToEndAsync(); $stderr=$process.StandardError.ReadToEndAsync()
$process.StandardInput.Write($prompt); $process.StandardInput.Close()
if (-not $process.WaitForExit([int]$config.reviewTimeoutSeconds * 1000)) { $process.Kill(); throw 'Claude review timed out.' }
if ($process.ExitCode -ne 0) { throw "Claude review mechanism failed: $($stderr.Result)" }
$envelope = $stdout.Result | ConvertFrom-Json
$review = if ($envelope.structured_output) { $envelope.structured_output } elseif ($envelope.result) { ([string]$envelope.result) | ConvertFrom-Json } else { throw 'Claude returned no structured review.' }
if ($review.verdict -notin @('PASS','FAIL')) { throw 'Invalid review verdict.' }
$record=[ordered]@{schemaVersion='1.0.0';reviewedAt=(Get-Date).ToUniversalTime().ToString('o');reviewer='Claude Code';roleLock=$roleLock;taskFile=$TaskFile;resultsFile=$ResultsFile;review=$review}
New-Item -ItemType Directory -Force -Path (Split-Path -Parent $outputPath) | Out-Null
$record | ConvertTo-Json -Depth 12 | Set-Content -LiteralPath $outputPath
Write-Output ($record | ConvertTo-Json -Depth 12 -Compress)
if ($review.verdict -eq 'FAIL') { exit 2 }

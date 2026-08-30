#Requires -Version 5.1
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$runner = Join-Path $root 'scripts\agent-workflow\Invoke-ClaudeReview.ps1'
$source = Get-Content -Raw -LiteralPath $runner
$failures = [Collections.Generic.List[string]]::new()
$assertionCount = 0

function Assert-Contract([bool]$Condition, [string]$Message) {
    $script:assertionCount++
    if (-not $Condition) { $script:failures.Add($Message) }
}

$tokens = $null
$parseErrors = $null
[Management.Automation.Language.Parser]::ParseFile($runner, [ref]$tokens, [ref]$parseErrors) | Out-Null
Assert-Contract ($parseErrors.Count -eq 0) 'Claude review runner must parse without errors.'
Assert-Contract ($source -match 'rev-parse --verify HEAD') 'Review evidence must resolve the current HEAD commit explicitly.'
Assert-Contract ($source -match 'rev-parse --verify "\$_\^\{commit\}"') 'Referenced commits must be normalized to full commit hashes.'
Assert-Contract ($source -match '\$commits = @\(\$commits \+ \$headCommit \| Select-Object -Unique\)') 'Current HEAD must always be added to the reviewed commit set.'
Assert-Contract ($source -notmatch "if \(\$commits\.Count -eq 0\) \{ \$commits = @\('HEAD'\) \}") 'HEAD review must not be only a fallback for an empty referenced-commit set.'

if ($failures.Count) {
    $failures | ForEach-Object { Write-Host "FAIL: $_" }
    exit 1
}

Write-Host "claude_review_runner_contract=passed assertions=$assertionCount"

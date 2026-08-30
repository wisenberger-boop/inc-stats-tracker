#Requires -Version 5.1
param(
    [string]$ZipPath
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$pluginRoot = Join-Path $root 'plugin\inc-stats-tracker'
$errors = [Collections.Generic.List[string]]::new()

function Add-Failure([string]$Message) {
    $script:errors.Add($Message)
    Write-Host "FAIL: $Message"
}

function Read-Version([string]$Path, [string]$Pattern, [string]$Label) {
    $match = [regex]::Match((Get-Content -LiteralPath $Path -Raw), $Pattern, 'Multiline')
    if (-not $match.Success) {
        Add-Failure "Could not read $Label version from $Path"
        return $null
    }
    return $match.Groups[1].Value
}

$phpFiles = @(Get-ChildItem -LiteralPath $pluginRoot -Recurse -Filter '*.php' -File)
foreach ($file in $phpFiles) {
    & php -l $file.FullName *> $null
    if ($LASTEXITCODE -ne 0) {
        Add-Failure "PHP syntax check failed: $($file.FullName)"
    }
}
Write-Host "php_lint_files=$($phpFiles.Count)"

$mainFile = Join-Path $pluginRoot 'inc-stats-tracker.php'
$headerVersion = Read-Version $mainFile '^\s*\*\s*Version:\s*([^\s]+)' 'plugin header'
$constantVersion = Read-Version $mainFile "define\(\s*'IST_VERSION',\s*'([^']+)'\s*\)" 'IST_VERSION'
$readmeVersion = Read-Version (Join-Path $pluginRoot 'readme.txt') '^Stable tag:\s*([^\s]+)' 'stable tag'
$batchVersion = Read-Version (Join-Path $root 'tools\package-plugin.bat') '^set VERSION=([^\r\n]+)' 'package script'
$changelogVersion = Read-Version (Join-Path $pluginRoot 'CHANGELOG.md') '^## \[([0-9]+\.[0-9]+\.[0-9]+)\]' 'changelog'
$versions = @($headerVersion, $constantVersion, $readmeVersion, $batchVersion, $changelogVersion)
if (@($versions | Select-Object -Unique).Count -ne 1) {
    Add-Failure "Version mismatch: $($versions -join ', ')"
} else {
    Write-Host "version=$headerVersion"
}

$stagedCsv = @(Get-ChildItem -LiteralPath (Join-Path $pluginRoot 'docs\source-assets\csv') -Filter '*.csv' -File -ErrorAction SilentlyContinue)
if ($stagedCsv.Count -gt 0) {
    Add-Failure 'Runtime CSV staging folder contains release-forbidden CSV files.'
}

& php (Join-Path $root 'tests\fiscal-year-timezone-regression.php')
if ($LASTEXITCODE -ne 0) {
    Add-Failure 'Fiscal-year timezone regression check failed.'
}

& php (Join-Path $root 'tests\monthly-submitter-leaderboard.php')
if ($LASTEXITCODE -ne 0) {
    Add-Failure 'Monthly submitter leaderboard query/UI contract check failed.'
}

& powershell -NoProfile -File (Join-Path $root 'tests\deploy-script-contract.ps1')
if ($LASTEXITCODE -ne 0) {
    Add-Failure 'Deployment script contract check failed.'
}

& powershell -NoProfile -File (Join-Path $root 'tests\claude-review-runner-contract.ps1')
if ($LASTEXITCODE -ne 0) {
    Add-Failure 'Claude review runner contract check failed.'
}

if ($ZipPath) {
    $resolvedZip = (Resolve-Path -LiteralPath $ZipPath).Path
    Add-Type -Assembly 'System.IO.Compression.FileSystem'
    $archive = [System.IO.Compression.ZipFile]::OpenRead($resolvedZip)
    try {
        $entries = @($archive.Entries)
        if ($entries.Count -eq 0) {
            Add-Failure 'Release ZIP is empty.'
        }
        foreach ($entry in $entries) {
            $name = $entry.FullName
            if (-not $name.StartsWith('inc-stats-tracker/')) {
                Add-Failure "ZIP entry is outside the plugin root: $name"
            }
            if ($name.Contains('\')) {
                Add-Failure "ZIP entry uses a backslash separator: $name"
            }
            if ($name -match '(?i)(^|/)(\.git|\.local|tools|project-management)(/|$)' -or $name -match '(?i)\.csv$') {
                Add-Failure "Release-forbidden ZIP entry: $name"
            }
            if ($name -match '(?i)(^|/)(\.env($|\.)|id_(rsa|dsa|ecdsa|ed25519)(\.pub)?$)|\.(pem|key|ppk)$') {
                Add-Failure "Secret-like ZIP entry: $name"
            }
            if ($name -match '(?i)\.(php|js|css|txt|md|json|html|xml)$' -and $entry.Length -le 5MB) {
                $reader = [System.IO.StreamReader]::new($entry.Open())
                try {
                    $text = $reader.ReadToEnd()
                    if ($text -match '-----BEGIN (OPENSSH |RSA |EC )?PRIVATE KEY-----') {
                        Add-Failure "Private-key material found in ZIP entry: $name"
                    }
                } finally {
                    $reader.Dispose()
                }
            }
        }
        Write-Host "zip_entries=$($entries.Count)"
    } finally {
        $archive.Dispose()
    }
}

if ($errors.Count -gt 0) {
    Write-Host "verification=failed count=$($errors.Count)"
    exit 1
}

Write-Host 'verification=passed'

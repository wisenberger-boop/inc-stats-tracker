#Requires -Version 5.1
<#
.SYNOPSIS
    Populate ignored INC Stats Tracker target configs from the authorized INC SSH config.

.DESCRIPTION
    Resolves the ignored deploy environment referenced by the verified Inclusive
    Networking Content Manager runner, reads only host, user, and port, and
    references that project's existing private key in place. Private values are
    never printed.
#>

[CmdletBinding()]
param(
    [string]$SourceProject = 'C:\Users\isen\OneDrive\Documents\Code Files\inclusive-networking-content-manager',
    [string]$SourceRunner = 'C:\Users\isen\OneDrive\Documents\Code Files\inclusive-networking-content-manager\.local\deploy-approved-dev-0.4.0.ps1',
    [string]$SourceConfig = '',
    [string]$SourceKey = 'C:\Users\isen\OneDrive\Documents\Code Files\inclusive-networking-content-manager\.local\credentials\inc-content-dev-ed25519',
    [string]$ProductionBackupReference = '',
    [string]$ProductionRollbackOwner = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$expectedFingerprint = 'SHA256:mkToJ1gVeXJ8zTaE5LwSJb85kdZn93N9a2otCQjM7mY'
$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

function Resolve-RunnerConfig([string]$RunnerPath) {
    if (-not (Test-Path -LiteralPath $RunnerPath -PathType Leaf)) {
        throw "Verified source runner is missing: $RunnerPath"
    }
    $tokens = $null
    $parseErrors = $null
    $ast = [Management.Automation.Language.Parser]::ParseFile($RunnerPath, [ref]$tokens, [ref]$parseErrors)
    if ($parseErrors.Count) { throw 'Verified source runner could not be parsed.' }
    $candidates = @($ast.FindAll({
        param($node)
        ($node -is [Management.Automation.Language.StringConstantExpressionAst] -or
            $node -is [Management.Automation.Language.ExpandableStringExpressionAst]) -and
        $node.Value -match '(?i)deploy\.env$'
    }, $true) | ForEach-Object { $_.Value } | Where-Object { Test-Path -LiteralPath $_ -PathType Leaf })
    if ($candidates.Count -ne 1) {
        throw 'Verified source runner must reference exactly one existing deploy.env.'
    }
    return (Resolve-Path -LiteralPath $candidates[0]).Path
}

if (-not $SourceConfig) { $SourceConfig = Resolve-RunnerConfig $SourceRunner }

function Read-Env([string]$Path) {
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) { throw "Required ignored source file is missing: $Path" }
    $values = @{}
    foreach ($raw in Get-Content -LiteralPath $Path) {
        $line = $raw.Trim()
        if (-not $line -or $line.StartsWith('#')) { continue }
        $parts = $line -split '=', 2
        if ($parts.Count -ne 2) { continue }
        $values[$parts[0].Trim()] = $parts[1].Trim().Trim('"').Trim("'")
    }
    return $values
}

function Require-Value([hashtable]$Values, [string]$Name) {
    if (-not $Values.ContainsKey($Name) -or -not $Values[$Name]) { throw "Required source setting is missing: $Name" }
    return $Values[$Name]
}

function Write-TargetConfig([string]$Path, [string]$Prefix, [string]$CanonicalUrl, [string]$WpRoot, [string]$TmpDir, [string]$KnownHosts, [hashtable]$Source, [string]$BackupOverride = '', [string]$OwnerOverride = '') {
    $existing = if (Test-Path -LiteralPath $Path) { Read-Env $Path } else { @{} }
    $backupReference = if ($existing.ContainsKey("${Prefix}_BACKUP_REFERENCE")) { $existing["${Prefix}_BACKUP_REFERENCE"] } else { '' }
    $rollbackOwner = if ($existing.ContainsKey("${Prefix}_ROLLBACK_OWNER")) { $existing["${Prefix}_ROLLBACK_OWNER"] } else { '' }
    if ($BackupOverride) { $backupReference = $BackupOverride }
    if ($OwnerOverride) { $rollbackOwner = $OwnerOverride }
    $content = @(
        '# Private, ignored configuration. Shared INC SSH identity; target state remains isolated.',
        "${Prefix}_CANONICAL_URL=$CanonicalUrl",
        "${Prefix}_SSH_HOST=$(Require-Value $Source 'INC_DEPLOY_HOST')",
        "${Prefix}_SSH_USER=$(Require-Value $Source 'INC_DEPLOY_USER')",
        "${Prefix}_SSH_PORT=$(Require-Value $Source 'INC_DEPLOY_PORT')",
        "${Prefix}_REMOTE_WP_PATH=$WpRoot",
        "${Prefix}_REMOTE_TMP_DIR=$TmpDir",
        "${Prefix}_SSH_KEY=$SourceKey",
        "${Prefix}_SSH_KEY_OWNER_ROOT=$SourceProject",
        "${Prefix}_KNOWN_HOSTS=$KnownHosts",
        "${Prefix}_BACKUP_REFERENCE=$backupReference",
        "${Prefix}_ROLLBACK_OWNER=$rollbackOwner"
    )
    [IO.File]::WriteAllLines($Path, $content, [Text.UTF8Encoding]::new($false))
}

foreach ($path in $SourceRunner, $SourceKey) {
    & git -C $SourceProject check-ignore --quiet -- $path
    if ($LASTEXITCODE -ne 0) { throw 'A required Content Manager private path is not ignored by its owner repository.' }
}
$configOwner = (& git -C (Split-Path -Parent $SourceConfig) rev-parse --show-toplevel 2>$null)
if ($LASTEXITCODE -ne 0 -or -not $configOwner) { throw 'Unable to resolve the source deploy.env owner repository.' }
& git -C $configOwner check-ignore --quiet -- $SourceConfig
if ($LASTEXITCODE -ne 0) { throw 'The source deploy.env is not ignored by its owner repository.' }
$fingerprintOutput = & ssh-keygen -lf $SourceKey 2>$null
if ($LASTEXITCODE -ne 0 -or $fingerprintOutput -notmatch [regex]::Escape($expectedFingerprint)) {
    throw 'The authorized INC private key fingerprint does not match the required fingerprint.'
}

$source = Read-Env $SourceConfig
$localRoot = Join-Path $projectRoot '.local'
foreach ($targetConfig in 'deploy-dev.env', 'deploy-production.env') {
    & git -C $projectRoot check-ignore --quiet -- ".local/$targetConfig"
    if ($LASTEXITCODE -ne 0) { throw 'A target private configuration path is not ignored.' }
}

Write-TargetConfig `
    (Join-Path $localRoot 'deploy-dev.env') `
    'IST_DEPLOY_DEV' `
    'https://dev.inclusivenetworkingcoalition.org' `
    '/home/customer/www/dev.inclusivenetworkingcoalition.org/public_html' `
    '~/inc-stats-tracker-development-deploys' `
    (Join-Path $localRoot 'known-hosts-development') `
    $source

Write-TargetConfig `
    (Join-Path $localRoot 'deploy-production.env') `
    'IST_DEPLOY_PRODUCTION' `
    'https://www.inclusivenetworkingcoalition.org' `
    '/home/customer/www/inclusivenetworkingcoalition.org/public_html' `
    '~/inc-stats-tracker-production-deploys' `
    (Join-Path $localRoot 'known-hosts-production') `
    $source `
    $ProductionBackupReference `
    $ProductionRollbackOwner

Write-Host 'Shared INC SSH identity configured for both isolated targets; private values were not displayed.'

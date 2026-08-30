#Requires -Version 5.1
<#
.SYNOPSIS
    Preflight or deploy INC Stats Tracker to an explicitly selected WordPress target.

.DESCRIPTION
    Development and Production use separate ignored target configuration and
    known-host stores while reusing the already-authorized INC SSH identity.
    The default mode is read-only. -Discover performs bounded WordPress-root discovery.
    -Live creates a plugin-only rollback archive, uploads one verified ZIP, and updates
    only the inc-stats-tracker plugin with WP-CLI.
#>

[CmdletBinding(DefaultParameterSetName = 'Preflight')]
param(
    [Parameter(Mandatory)]
    [ValidateSet('Development', 'Production')]
    [string]$Target,

    [Parameter(ParameterSetName = 'Discovery')]
    [switch]$Discover,

    [Parameter(ParameterSetName = 'Live')]
    [switch]$Live,

    [Parameter(ParameterSetName = 'Live')]
    [switch]$ConfirmProduction,

    [Parameter(ParameterSetName = 'Preflight')]
    [switch]$ConnectionOnly,

    [string]$ArtifactPath,
    [switch]$SkipBuild,

    [ValidatePattern('^\d{8}-\d{6}$')]
    [string]$DeploymentId
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$pluginSlug = 'inc-stats-tracker'
$scriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = (Resolve-Path -LiteralPath (Join-Path $scriptRoot '..')).Path
$pluginRoot = Join-Path $projectRoot "plugin\$pluginSlug"
$configName = if ($Target -eq 'Development') { 'deploy-dev.env' } else { 'deploy-production.env' }
$configPath = Join-Path $projectRoot ".local\$configName"
$expectedCanonicalUrl = if ($Target -eq 'Development') {
    'https://dev.inclusivenetworkingcoalition.org'
} else {
    'https://www.inclusivenetworkingcoalition.org'
}

function Stop-Deploy([string]$Message) {
    throw $Message
}

function Read-DeployEnv([string]$Path) {
    if (-not (Test-Path -LiteralPath $Path)) {
        Stop-Deploy "Deploy config not found: $Path"
    }

    $values = @{}
    foreach ($rawLine in Get-Content -LiteralPath $Path) {
        $line = $rawLine.Trim()
        if (-not $line -or $line.StartsWith('#')) { continue }
        $parts = $line -split '=', 2
        if ($parts.Count -ne 2) { continue }
        $name = $parts[0].Trim()
        $value = $parts[1].Trim()
        if (($value.StartsWith('"') -and $value.EndsWith('"')) -or
            ($value.StartsWith("'") -and $value.EndsWith("'"))) {
            $value = $value.Substring(1, $value.Length - 2)
        }
        $values[$name] = $value
    }
    return $values
}

function Get-ConfigValue([hashtable]$Values, [string]$Name, [switch]$Optional) {
    if (-not $Values.ContainsKey($Name) -or -not $Values[$Name]) {
        if ($Optional) { return '' }
        Stop-Deploy "Missing required value $Name in $configPath"
    }
    return $Values[$Name]
}

function Quote-Remote([string]$Value) {
    if ($Value.Contains("'")) {
        Stop-Deploy 'Remote configuration values may not contain single quotes.'
    }
    return "'$Value'"
}

function Invoke-NativeChecked([string]$Label, [scriptblock]$Command) {
    & $Command
    if ($LASTEXITCODE -ne 0) {
        Stop-Deploy "$Label failed with exit code $LASTEXITCODE."
    }
}

function Invoke-SshCapture([string]$Command) {
    $previousErrorAction = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & ssh @script:sshOptions $script:remoteTarget $Command 2>&1
        $sshExitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousErrorAction
    }
    if ($sshExitCode -ne 0) {
        Stop-Deploy "Remote read-only command failed with exit code $sshExitCode; connection details were suppressed."
    }
    return ($output -join "`n").Trim()
}

function Assert-Ignored([string]$Path) {
    & git -C $projectRoot check-ignore --quiet -- $Path
    if ($LASTEXITCODE -ne 0) {
        Stop-Deploy "Private deployment path is not ignored by Git: $Path"
    }
}

function Assert-IgnoredByOwner([string]$Path, [string]$OwnerRoot) {
    $resolvedOwner = (Resolve-Path -LiteralPath $OwnerRoot).Path
    $resolvedPath = (Resolve-Path -LiteralPath $Path).Path
    $ownerPrefix = $resolvedOwner.TrimEnd('\') + '\'
    if (-not $resolvedPath.StartsWith($ownerPrefix, [StringComparison]::OrdinalIgnoreCase)) {
        Stop-Deploy 'Configured shared SSH key is outside its declared owner repository.'
    }
    & git -C $resolvedOwner check-ignore --quiet -- $resolvedPath
    if ($LASTEXITCODE -ne 0) {
        Stop-Deploy 'Configured shared SSH key is not ignored by its owner repository.'
    }
}

function Get-PluginVersion {
    $mainFile = Join-Path $pluginRoot "$pluginSlug.php"
    $match = [regex]::Match((Get-Content -Raw -LiteralPath $mainFile), '(?m)^\s*\*\s*Version:\s*([^\s]+)')
    if (-not $match.Success) { Stop-Deploy 'Unable to read the local plugin version.' }
    return $match.Groups[1].Value
}

function New-VerifiedArtifact([string]$Version) {
    $verifyScript = Join-Path $projectRoot 'tools\verify-project.ps1'
    Invoke-NativeChecked 'Local project verification' { & powershell -NoProfile -File $verifyScript | Out-Host }

    $tempRoot = Join-Path $projectRoot '.local\deploy-build'
    New-Item -ItemType Directory -Force -Path $tempRoot | Out-Null
    $first = Join-Path $tempRoot "$pluginSlug-$Version-first.zip"
    $second = Join-Path $tempRoot "$pluginSlug-$Version-second.zip"
    Remove-Item -LiteralPath $first, $second -Force -ErrorAction SilentlyContinue

    $packageScript = Join-Path $projectRoot 'tools\package-plugin.ps1'
    & powershell -NoProfile -File $packageScript -PluginName $pluginSlug -Version $Version -PluginDir $pluginRoot -ZipPath $first | Out-Host
    if ($LASTEXITCODE -ne 0) { Stop-Deploy 'First package build failed.' }
    & powershell -NoProfile -File $packageScript -PluginName $pluginSlug -Version $Version -PluginDir $pluginRoot -ZipPath $second | Out-Host
    if ($LASTEXITCODE -ne 0) { Stop-Deploy 'Second package build failed.' }

    $firstHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $first).Hash
    $secondHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $second).Hash
    if ($firstHash -ne $secondHash) { Stop-Deploy 'Package build is not reproducible; the two SHA-256 hashes differ.' }

    $releaseDir = Join-Path $projectRoot 'build\releases'
    New-Item -ItemType Directory -Force -Path $releaseDir | Out-Null
    $releasePath = Join-Path $releaseDir "$pluginSlug-$Version.zip"
    Copy-Item -LiteralPath $first -Destination $releasePath -Force
    Invoke-NativeChecked 'Artifact verification' { & powershell -NoProfile -File $verifyScript -ZipPath $releasePath | Out-Host }
    return (Resolve-Path -LiteralPath $releasePath).Path
}

Assert-Ignored '.local/'
Assert-Ignored ".local/$configName"

$config = Read-DeployEnv $configPath
$prefix = if ($Target -eq 'Development') { 'IST_DEPLOY_DEV' } else { 'IST_DEPLOY_PRODUCTION' }
$canonicalUrl = Get-ConfigValue $config "${prefix}_CANONICAL_URL"
if ($canonicalUrl.TrimEnd('/') -ne $expectedCanonicalUrl) {
    Stop-Deploy "Canonical URL must be exactly $expectedCanonicalUrl for $Target."
}

$remoteHost = Get-ConfigValue $config "${prefix}_SSH_HOST"
$remoteUser = Get-ConfigValue $config "${prefix}_SSH_USER"
$remotePort = Get-ConfigValue $config "${prefix}_SSH_PORT"
$remoteWpPath = Get-ConfigValue $config "${prefix}_REMOTE_WP_PATH" -Optional
$remoteTmpDir = Get-ConfigValue $config "${prefix}_REMOTE_TMP_DIR"
$sshKeyPath = Get-ConfigValue $config "${prefix}_SSH_KEY"
$sshKeyOwnerRoot = Get-ConfigValue $config "${prefix}_SSH_KEY_OWNER_ROOT"
$knownHostsPath = Get-ConfigValue $config "${prefix}_KNOWN_HOSTS"
$backupReference = Get-ConfigValue $config "${prefix}_BACKUP_REFERENCE" -Optional
$rollbackOwner = Get-ConfigValue $config "${prefix}_ROLLBACK_OWNER" -Optional

if ($remotePort -notmatch '^\d{1,5}$' -or [int]$remotePort -lt 1 -or [int]$remotePort -gt 65535) {
    Stop-Deploy 'SSH port must be an integer from 1 through 65535.'
}
if (-not (Test-Path -LiteralPath $sshKeyPath -PathType Leaf)) {
    Stop-Deploy "Configured SSH key does not exist: $sshKeyPath"
}
Assert-IgnoredByOwner $sshKeyPath $sshKeyOwnerRoot
Assert-Ignored $knownHostsPath
$keyHeader = Get-Content -LiteralPath $sshKeyPath -TotalCount 1
if ($keyHeader -notmatch '^-----BEGIN (OPENSSH |RSA |EC )?PRIVATE KEY-----$') {
    Stop-Deploy 'Configured SSH key is not a recognized OpenSSH private key.'
}
if ($Target -eq 'Production' -and $Live -and (-not $backupReference -or -not $rollbackOwner)) {
    Stop-Deploy 'Production live mode requires a current BACKUP_REFERENCE and ROLLBACK_OWNER in the ignored production config.'
}
if ($Live -and (-not $SkipBuild -or -not $ArtifactPath -or -not $DeploymentId)) {
    Stop-Deploy 'Live mode requires -SkipBuild, -ArtifactPath, and -DeploymentId for the exact reviewed deployment approved in the current session.'
}

foreach ($command in 'ssh', 'scp', 'git', 'php') {
    if (-not (Get-Command $command -ErrorAction SilentlyContinue)) { Stop-Deploy "$command was not found on PATH." }
}

$knownHostsParent = Split-Path -Parent $knownHostsPath
if ($knownHostsParent) { New-Item -ItemType Directory -Force -Path $knownHostsParent | Out-Null }
$strictHostKeyMode = if (Test-Path -LiteralPath $knownHostsPath) { 'yes' } else { 'accept-new' }
$knownHostsSshPath = $knownHostsPath.Replace('\', '/')
$script:sshOptions = @(
    '-o', 'BatchMode=yes', '-o', 'IdentitiesOnly=yes', '-o', 'ConnectTimeout=10',
    '-o', "StrictHostKeyChecking=$strictHostKeyMode", '-o', "UserKnownHostsFile=$knownHostsSshPath",
    '-p', $remotePort, '-i', $sshKeyPath
)
$scpOptions = @(
    '-o', 'BatchMode=yes', '-o', 'IdentitiesOnly=yes', '-o', 'ConnectTimeout=10',
    '-o', "StrictHostKeyChecking=$strictHostKeyMode", '-o', "UserKnownHostsFile=$knownHostsSshPath",
    '-P', $remotePort, '-i', $sshKeyPath
)
$script:remoteTarget = "${remoteUser}@${remoteHost}"

Write-Host "Target environment : $Target"
Write-Host "Canonical URL     : $canonicalUrl"
Write-Host "Config            : $configPath"
Write-Host 'SSH identity      : configured in ignored target-specific config'
Write-Host "Host-key policy   : $strictHostKeyMode with target-specific ignored known_hosts"

$identity = Invoke-SshCapture 'echo SSH_OK'
$identityLines = @($identity -split "`n")
if (-not ($identityLines | Where-Object { $_.Trim() -eq 'SSH_OK' })) {
    Stop-Deploy 'SSH identity probe returned an unexpected response.'
}
Write-Host 'SSH connectivity  : passed'

$remoteHomeOutput = Invoke-SshCapture 'echo $HOME'
$remoteHomeCandidates = @($remoteHomeOutput -split "`n" | Where-Object { $_ -match '^/[A-Za-z0-9._/-]+$' })
if ($remoteHomeCandidates.Count -ne 1) {
    Stop-Deploy 'Remote home path probe returned an unexpected response.'
}
$remoteHome = $remoteHomeCandidates[0].TrimEnd('/')
$remoteTmpResolved = if ($remoteTmpDir.StartsWith('~/')) {
    $remoteHome + '/' + $remoteTmpDir.Substring(2)
} elseif ($remoteTmpDir.StartsWith('/')) {
    $remoteTmpDir
} else {
    Stop-Deploy 'Remote temporary directory must be absolute or start with ~/.'
}

if ($Discover) {
    $discoveryCommand = 'find "$HOME" -maxdepth 7 -type f -name wp-config.php -print 2>/dev/null | head -n 20'
    $candidates = Invoke-SshCapture $discoveryCommand
    if (-not $candidates) { Stop-Deploy 'No WordPress root candidates were found under the remote account home.' }
    Write-Host "WordPress root candidates (wp-config.php):`n$candidates"
    Write-Host 'Discovery is read-only. Add the verified root to the selected ignored config, then rerun preflight.'
    exit 0
}

if (-not $remoteWpPath -or -not $remoteWpPath.StartsWith('/')) {
    Stop-Deploy "${prefix}_REMOTE_WP_PATH must be an absolute path discovered on this target."
}
$quotedWpPath = Quote-Remote $remoteWpPath
$quotedSlug = Quote-Remote $pluginSlug
$quotedVersion = Quote-Remote (Get-PluginVersion)
$quotedExpectedUrl = Quote-Remote $expectedCanonicalUrl
$expectedUri = [Uri]$expectedCanonicalUrl
$quotedWwwUrl = Quote-Remote ("{0}://www.{1}" -f $expectedUri.Scheme, $expectedUri.Host)
$alternateScheme = if ($expectedUri.Scheme -eq 'https') { 'http' } else { 'https' }
$quotedAlternateSchemeUrl = Quote-Remote ("{0}://{1}" -f $alternateScheme, $expectedUri.Host)
$quotedAlternateSchemeWwwUrl = Quote-Remote ("{0}://www.{1}" -f $alternateScheme, $expectedUri.Host)

$remoteCheck = @"
if ! cd $quotedWpPath 2>/dev/null; then printf 'preflight_error=wordpress_root\n'; exit 0; fi
if ! test -f wp-config.php; then printf 'preflight_error=wp_config\n'; exit 0; fi
if ! test -d wp-admin || ! test -d wp-content/plugins; then printf 'preflight_error=wordpress_structure\n'; exit 0; fi
if ! command -v wp >/dev/null 2>&1; then printf 'preflight_error=wp_cli\n'; exit 0; fi
actual_url=`$(WP_CLI_PHP_ARGS='-d error_reporting=0 -d display_errors=0' wp option get siteurl --skip-plugins --skip-themes 2>/dev/null | sed 's:/*`$::')
if test "`$actual_url" = $quotedExpectedUrl; then
  printf 'canonical_url_match=true\n'
  printf 'canonical_url_classification=exact\n'
elif test "`$actual_url" = $quotedWwwUrl; then
  printf 'canonical_url_match=false\n'
  printf 'canonical_url_classification=www_alias\n'
elif test "`$actual_url" = $quotedAlternateSchemeUrl; then
  printf 'canonical_url_match=false\n'
  printf 'canonical_url_classification=scheme_only\n'
elif test "`$actual_url" = $quotedAlternateSchemeWwwUrl; then
  printf 'canonical_url_match=false\n'
  printf 'canonical_url_classification=www_and_scheme\n'
else
  printf 'canonical_url_match=false\n'
  printf 'canonical_url_classification=other\n'
fi
if WP_CLI_PHP_ARGS='-d error_reporting=0 -d display_errors=0' wp plugin is-installed $quotedSlug 2>/dev/null; then
  printf 'plugin_version=%s\n' "`$(WP_CLI_PHP_ARGS='-d error_reporting=0 -d display_errors=0' wp plugin get $quotedSlug --field=version 2>/dev/null)"
  printf 'plugin_status=%s\n' "`$(WP_CLI_PHP_ARGS='-d error_reporting=0 -d display_errors=0' wp plugin get $quotedSlug --field=status 2>/dev/null)"
else
  printf 'plugin_version=not-installed\nplugin_status=not-installed\n'
fi
"@
$remoteState = Invoke-SshCapture $remoteCheck
$preflightError = @($remoteState -split "`n" | Where-Object { $_ -match '^preflight_error=[a-z_]+$' } | Select-Object -First 1)
if ($preflightError.Count) {
    Stop-Deploy "Remote read-only preflight failed at sanitized stage: $($preflightError[0].Substring(16))."
}
$remoteStateLines = @($remoteState -split "`n" | Where-Object { $_ -match '^plugin_(version|status)=' })
if ($remoteStateLines.Count -ne 2) {
    Stop-Deploy 'Remote plugin state did not return the expected bounded fields.'
}
$canonicalState = @($remoteState -split "`n" | Where-Object { $_ -match '^canonical_url_match=(true|false)$' })
if ($canonicalState.Count -ne 1) {
    Stop-Deploy 'Remote canonical URL check did not return the expected bounded field.'
}
$canonicalClassification = @($remoteState -split "`n" | Where-Object { $_ -match '^canonical_url_classification=(exact|www_alias|scheme_only|www_and_scheme|other)$' })
if ($canonicalClassification.Count -ne 1) {
    Stop-Deploy 'Remote canonical URL classification did not return the expected bounded field.'
}
Write-Host "Remote read-only state:`n$($canonicalState[0])`n$($canonicalClassification[0])`n$($remoteStateLines -join "`n")"
if ($canonicalState[0] -ne 'canonical_url_match=true') {
    Stop-Deploy 'Remote WordPress canonical URL does not match the selected target; deployment remains blocked.'
}

if ($ConnectionOnly) {
    Write-Host 'Connection-only preflight passed. No artifact was built and no remote changes were made.'
    exit 0
}

$version = Get-PluginVersion
if ($SkipBuild) {
    if (-not $ArtifactPath) { Stop-Deploy '-ArtifactPath is required with -SkipBuild.' }
    $resolvedArtifact = (Resolve-Path -LiteralPath $ArtifactPath).Path
    Invoke-NativeChecked 'Provided artifact verification' {
        & powershell -NoProfile -File (Join-Path $projectRoot 'tools\verify-project.ps1') -ZipPath $resolvedArtifact
    }
} else {
    $resolvedArtifact = New-VerifiedArtifact $version
}

$artifactHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $resolvedArtifact).Hash
$artifactName = Split-Path -Leaf $resolvedArtifact
$remoteZip = $remoteTmpResolved.TrimEnd('/', '\') + '/' + $artifactName
$remotePluginPath = $remoteWpPath.TrimEnd('/', '\') + "/wp-content/plugins/$pluginSlug"
if (-not $DeploymentId) { $DeploymentId = Get-Date -Format 'yyyyMMdd-HHmmss' }
$backupName = "$pluginSlug-predeploy-$DeploymentId.tar.gz"
$remoteBackup = $remoteTmpResolved.TrimEnd('/', '\') + "/backups/$backupName"
$remoteBackupDisplay = $remoteTmpDir.TrimEnd('/', '\') + "/backups/$backupName"

Write-Host ''
Write-Host 'Release preflight passed'
Write-Host "  Target       : $Target ($canonicalUrl)"
Write-Host "  Artifact     : $resolvedArtifact"
Write-Host "  Version      : $version"
Write-Host "  SHA-256      : $artifactHash"
Write-Host "  WordPress    : $remoteWpPath"
Write-Host "  Plugin path  : $remotePluginPath"
Write-Host "  Rollback     : plugin-only archive will be created at $remoteBackupDisplay"
Write-Host "  Site backup  : $(if ($backupReference) { 'reference configured' } else { 'not configured' })"
Write-Host "  Rollback owner: $(if ($rollbackOwner) { 'configured' } else { 'not configured' })"

if (-not $Live) {
    Write-Host ''
    Write-Host 'No remote changes were made.'
    $approvalCommand = ".\scripts\deploy-plugin-ssh.ps1 -Target $Target -Live -SkipBuild -ArtifactPath `"$resolvedArtifact`" -DeploymentId $DeploymentId"
    if ($Target -eq 'Production') { $approvalCommand += ' -ConfirmProduction' }
    Write-Host "Exact live command: $approvalCommand"
    exit 0
}

if ($Target -eq 'Production' -and -not $ConfirmProduction) {
    Stop-Deploy 'Production deployment also requires -ConfirmProduction.'
}

$quotedTmp = Quote-Remote $remoteTmpResolved
$quotedBackup = Quote-Remote $remoteBackup
$quotedPluginPath = Quote-Remote $remotePluginPath
$quotedRemoteZip = Quote-Remote $remoteZip
$backupCommand = "set -eu; mkdir -p $quotedTmp " + (Quote-Remote ($remoteTmpResolved.TrimEnd('/', '\') + '/backups')) +
    "; if test -d $quotedPluginPath; then tar -czf $quotedBackup -C " + (Quote-Remote ($remoteWpPath.TrimEnd('/', '\') + '/wp-content/plugins')) + " $quotedSlug; fi"
Invoke-NativeChecked 'Remote plugin backup' { ssh @script:sshOptions $script:remoteTarget $backupCommand }
Invoke-NativeChecked 'Artifact upload' { scp @scpOptions $resolvedArtifact "${script:remoteTarget}:$remoteZip" }

$installCommand = "set -eu; cd $quotedWpPath; " +
    "WP_CLI_PHP_ARGS='-d error_reporting=0 -d display_errors=0' wp plugin install $quotedRemoteZip --force --activate; " +
    "installed_version=`$(WP_CLI_PHP_ARGS='-d error_reporting=0 -d display_errors=0' wp plugin get $quotedSlug --field=version 2>/dev/null); " +
    "installed_status=`$(WP_CLI_PHP_ARGS='-d error_reporting=0 -d display_errors=0' wp plugin get $quotedSlug --field=status 2>/dev/null); " +
    "test `"`$installed_version`" = $quotedVersion; test `"`$installed_status`" = 'active'; " +
    "printf 'plugin_version=%s\nplugin_status=%s\n' `"`$installed_version`" `"`$installed_status`""
Invoke-NativeChecked 'Plugin-only deployment' { ssh @script:sshOptions $script:remoteTarget $installCommand }

$restUrl = $canonicalUrl.TrimEnd('/') + '/wp-json/'
try {
    $httpHeaders = @{ 'User-Agent' = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/128 Safari/537.36' }
    $response = Invoke-WebRequest -UseBasicParsing -Uri $restUrl -Method Get -Headers $httpHeaders -TimeoutSec 20
    $restStatus = [int]$response.StatusCode
} catch {
    $restStatus = if ($_.Exception.Response -and $_.Exception.Response.StatusCode) {
        [int]$_.Exception.Response.StatusCode
    } else {
        0
    }
}
if ($restStatus -ne 200) {
    Stop-Deploy "Deployment completed, but the public REST index smoke test returned HTTP $restStatus."
}

Write-Host 'Deployment and bounded smoke checks passed.'
Write-Host "Rollback archive: $remoteBackupDisplay"
Write-Host 'Rollback requires separate approval: remove only the target plugin directory, extract this archive into wp-content/plugins, then verify/activate with WP-CLI.'

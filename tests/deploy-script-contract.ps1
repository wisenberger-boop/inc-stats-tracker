#Requires -Version 5.1
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$scriptPath = Join-Path $root 'scripts\deploy-plugin-ssh.ps1'
$source = Get-Content -Raw -LiteralPath $scriptPath
$configHelper = Get-Content -Raw -LiteralPath (Join-Path $root 'scripts\configure-shared-inc-ssh.ps1')
$failures = [Collections.Generic.List[string]]::new()
$assertionCount = 0

function Assert-Contract([bool]$Condition, [string]$Message) {
    $script:assertionCount++
    if (-not $Condition) { $script:failures.Add($Message) }
}

$tokens = $null
$parseErrors = $null
[Management.Automation.Language.Parser]::ParseFile($scriptPath, [ref]$tokens, [ref]$parseErrors) | Out-Null
Assert-Contract ($parseErrors.Count -eq 0) 'Deployment script must parse without errors.'
Assert-Contract ($source -match "ValidateSet\('Development', 'Production'\)") 'Targets must be an explicit closed set.'
Assert-Contract ($source -match '\[switch\]\$ConnectionOnly') 'A remote-only preflight must be available without building an artifact.'
Assert-Contract ($source -match 'deploy-dev\.env') 'Development must use a separate config.'
Assert-Contract ($source -match 'deploy-production\.env') 'Production must use a separate config.'
Assert-Contract ($source -match 'https://www\.inclusivenetworkingcoalition\.org') 'Production must assert the approved HTTPS www canonical URL.'
Assert-Contract ($source -match 'Assert-IgnoredByOwner \$sshKeyPath \$sshKeyOwnerRoot') 'A shared key must be ignored by its owning repository.'
Assert-Contract ($source -match 'if \(\$Target -eq ''Production'' -and -not \$ConfirmProduction\)') 'Production must have an extra live guard.'
Assert-Contract ($source -match 'Live mode requires -SkipBuild, -ArtifactPath, and -DeploymentId') 'Live mode must require the exact reviewed deployment inputs.'
Assert-Contract ($source -match '\$backupName = "\$pluginSlug-predeploy-\$DeploymentId\.tar\.gz"') 'Dry run and live mode must share the approved rollback archive path.'
Assert-Contract ($source -match '\$verifyScript \| Out-Host') 'Artifact builder diagnostics must not pollute its returned artifact path.'
Assert-Contract ($source -match '\$remoteTmpDir\.StartsWith\(''~/''\)') 'Tilde-based remote paths must be resolved before SSH and SCP use.'
Assert-Contract ($source -match '\$remoteZip = \$remoteTmpResolved') 'SCP destination must use the resolved absolute remote path.'
Assert-Contract ($source -match 'Deployment completed, but the public REST index smoke test returned HTTP') 'Post-deploy HTTP failures must distinguish completed deployment from failed smoke testing.'
Assert-Contract ($source -match 'installed_version.*quotedVersion') 'Live deployment must assert the installed version.'
Assert-Contract ($source -match 'Mozilla/5\.0 \(Windows NT 10\.0; Win64; x64\)') 'Public REST smoke test must use the verified browser-like user agent.'
Assert-Contract ($source -match 'BatchMode=yes') 'SSH must not hide an interactive authentication prompt.'
$sshOptionsBlock = [regex]::Match($source, '(?s)\$script:sshOptions\s*=\s*@\((.*?)\)').Groups[1].Value
$scpOptionsBlock = [regex]::Match($source, '(?s)\$scpOptions\s*=\s*@\((.*?)\)').Groups[1].Value
Assert-Contract ($sshOptionsBlock -match 'IdentitiesOnly=yes') 'SSH must offer only the explicitly configured authorized key.'
Assert-Contract ($scpOptionsBlock -match 'IdentitiesOnly=yes') 'SCP must offer only the explicitly configured authorized key.'
Assert-Contract ($source -match '2>&1') 'Read-only SSH errors must be captured rather than exposing connection details.'
Assert-Contract ($source -match 'UserKnownHostsFile=\$knownHostsSshPath') 'Each target must use its own normalized known-hosts file.'
Assert-Contract ($source.Contains("`$knownHostsSshPath = `$knownHostsPath.Replace('\', '/')")) 'Windows known-host paths must be normalized for OpenSSH.'
Assert-Contract ($source -match "\{ 'yes' \} else \{ 'accept-new' \}") 'Only the first target connection may use trust-on-first-use.'
Assert-Contract ($source -match 'wp plugin install \$quotedRemoteZip --force --activate') 'Deployment must target the plugin ZIP through WP-CLI.'
Assert-Contract ($source -match 'tar -czf') 'Live deployment must create a plugin rollback archive.'
Assert-Contract ($source -notmatch 'wp db (import|reset|query)') 'Deployment must not mutate the database directly.'
Assert-Contract ($source -notmatch 'wp (option|user|role|cap|cron|cache) (update|delete|add|set|run|flush)') 'Deployment must not mutate unrelated WordPress state.'
Assert-Contract ($source -notmatch 'wp core version|PHP_VERSION') 'Preflight must not retrieve unrelated runtime metadata.'
Assert-Contract ($source -match 'Invoke-SshCapture ''echo SSH_OK''') 'Authentication probe must request only its bounded success marker.'
Assert-Contract ($source -match 'Connection-only preflight passed\. No artifact was built and no remote changes were made\.') 'Connection-only mode must stop before artifact work.'
Assert-Contract ($source -match "\^plugin_\(version\|status\)=") 'Displayed remote state must be restricted to plugin version and status.'
Assert-Contract ($source -match 'WP_CLI_PHP_ARGS=') 'WP-CLI preflight must suppress unrelated PHP diagnostic output.'
Assert-Contract ($source -match 'preflight_error=wordpress_root') 'Remote failures must identify only a sanitized validation stage.'
Assert-Contract ($source -match 'canonical_url_match=\(true\|false\)') 'Canonical URL comparison must expose only a bounded match result.'
Assert-Contract ($source -match 'canonical_url_classification=\(exact\|www_alias\|scheme_only\|www_and_scheme\|other\)') 'Canonical URL mismatch must expose only a bounded classification.'
Assert-Contract ($configHelper -match [regex]::Escape('SHA256:mkToJ1gVeXJ8zTaE5LwSJb85kdZn93N9a2otCQjM7mY')) 'Content Manager private-key fingerprint must be verified.'
Assert-Contract ($configHelper -match [regex]::Escape('inclusive-networking-content-manager\.local\deploy-approved-dev-0.4.0.ps1')) 'The verified Content Manager runner must be the source authority.'
Assert-Contract ($configHelper -match [regex]::Escape('inclusive-networking-content-manager\.local\credentials\inc-content-dev-ed25519')) 'The verified Content Manager key must be referenced in place.'
Assert-Contract ($configHelper -match 'Resolve-RunnerConfig') 'The ignored deploy environment must be resolved from the verified runner.'
Assert-Contract ($configHelper -match '\$backupReference = if \(\$existing\.ContainsKey') 'Existing rollback metadata must survive configuration refresh.'
Assert-Contract ($configHelper -match '/home/customer/www/dev\.inclusivenetworkingcoalition\.org/public_html') 'Development root must be explicit.'
Assert-Contract ($configHelper -match '/home/customer/www/inclusivenetworkingcoalition\.org/public_html') 'Production root must be explicit.'
Assert-Contract ($configHelper -notmatch 'inc-mrs-deploy-key') 'The retired Meeting Roles Scheduler key must not be used.'

if ($failures.Count) {
    $failures | ForEach-Object { Write-Host "FAIL: $_" }
    exit 1
}

Write-Host "deploy_script_contract=passed assertions=$assertionCount"

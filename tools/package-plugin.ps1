#Requires -Version 5.1
<#
.SYNOPSIS
    Packages the inc-stats-tracker plugin as a WordPress-compatible ZIP archive.

.DESCRIPTION
    Called by package-plugin.bat. Do not run this script directly.

    ZIP entries are written with explicit forward-slash separators
    (e.g. inc-stats-tracker/admin/class-ist-admin.php).

    PowerShell 5.1's Compress-Archive writes backslash separators, which causes
    PHP's ZipArchive (used by the WordPress plugin installer on Linux servers) to
    extract entries as flat files with literal backslash filenames instead of a
    folder tree. This script uses ZipArchive directly and constructs every entry
    name by hand so the separator is always a forward slash.

.NOTES
    ZipArchive.Dispose() is called in a finally block to ensure the
    end-of-central-directory record is written even if an error occurs mid-pack.
    Without Dispose(), the ZIP exists on disk but is structurally incomplete and
    may fail to extract on strict ZIP parsers (including PHP's ZipArchive).
#>
param (
    [Parameter(Mandatory)][string]$PluginName,
    [Parameter(Mandatory)][string]$Version,
    [Parameter(Mandatory)][string]$PluginDir,
    [Parameter(Mandatory)][string]$ZipPath
)

$ErrorActionPreference = 'Stop'

# Resolve source path — validates the folder exists and normalises any .. segments
$PluginDir = (Resolve-Path -LiteralPath $PluginDir).Path

# Resolve destination path without requiring the file to exist yet
$ZipPath = [System.IO.Path]::GetFullPath($ZipPath)

Write-Host "  Source  : $PluginDir"
Write-Host "  Output  : $ZipPath"
Write-Host ""

Add-Type -Assembly 'System.IO.Compression'
Add-Type -Assembly 'System.IO.Compression.FileSystem'

$zip   = $null
$count = 0

try {
    $zip = [System.IO.Compression.ZipFile]::Open(
        $ZipPath,
        [System.IO.Compression.ZipArchiveMode]::Create
    )

    $srcLen = $PluginDir.Length

    foreach ($file in (Get-ChildItem -LiteralPath $PluginDir -Recurse -File)) {
        # Strip the base path and normalise separators to forward slashes.
        # $srcLen + 1 skips the trailing backslash that precedes the relative path.
        $rel       = $file.FullName.Substring($srcLen + 1).Replace('\', '/')
        $entryName = "$PluginName/$rel"

        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zip,
            $file.FullName,
            $entryName,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null

        $count++
    }

    Write-Host "  Packed $count files."
}
catch {
    Write-Host ""
    Write-Host "  ERROR during ZIP creation: $_"
    # Re-throw so the batch caller sees a non-zero exit code
    throw
}
finally {
    # Always dispose — this writes the ZIP end-of-central-directory record.
    # Skipping Dispose() leaves the ZIP structurally invalid even though the
    # file exists on disk.
    if ($null -ne $zip) {
        $zip.Dispose()
    }
}

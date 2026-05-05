<#
.SYNOPSIS
    Build, version-bump, zip, and deploy WC Superadmin plugins to GitHub.

.DESCRIPTION
    1. Reads current version from each plugin's main PHP file
    2. Bumps the PATCH version (or MINOR/MAJOR if specified)
    3. Updates the version in the PHP file and the update JSON metadata
    4. Creates fresh zip files of each plugin (excluding dev files)
    5. Commits everything and pushes to GitHub

.PARAMETER BumpType
    Which part of the version to increment: patch (default), minor, or major

.PARAMETER Message
    Optional custom git commit message. Defaults to "Release vX.X.X"

.EXAMPLE
    .\build.ps1
    .\build.ps1 -BumpType minor -Message "Add auto-registration feature"
#>

param(
    [ValidateSet("patch", "minor", "major")]
    [string]$BumpType = "patch",
    [string]$Message = ""
)

$Root         = $PSScriptRoot
$HubDir       = Join-Path $Root "wc-superadmin-hub"
$ClientDir    = Join-Path $Root "wc-superadmin-client"
$HubMainFile  = Join-Path $HubDir "wc-superadmin-hub.php"
$ClientMainFile = Join-Path $ClientDir "wc-superadmin-client.php"
$HubJson      = Join-Path $Root "hub-update.json"
$ClientJson   = Join-Path $Root "client-update.json"
$HubZip       = Join-Path $Root "wc-superadmin-hub.zip"
$ClientZip    = Join-Path $Root "wc-superadmin-client.zip"

# ─── Helper: Read version from plugin PHP file ───────────────────────────────
function Get-PluginVersion($file) {
    $content = Get-Content $file -Raw
    if ($content -match '\* Version:\s*([\d]+)\.([\d]+)\.([\d]+)') {
        return @{ Major = [int]$Matches[1]; Minor = [int]$Matches[2]; Patch = [int]$Matches[3] }
    }
    throw "Could not parse version in $file"
}

# ─── Helper: Bump a version hashtable ────────────────────────────────────────
function Bump-Version($v, $type) {
    switch ($type) {
        "major" { return @{ Major = $v.Major + 1; Minor = 0; Patch = 0 } }
        "minor" { return @{ Major = $v.Major; Minor = $v.Minor + 1; Patch = 0 } }
        "patch" { return @{ Major = $v.Major; Minor = $v.Minor; Patch = $v.Patch + 1 } }
    }
}

function Version-String($v) { return "$($v.Major).$($v.Minor).$($v.Patch)" }

# ─── Helper: Update version string in a file ─────────────────────────────────
function Set-FileVersion($file, $oldVersion, $newVersion) {
    $content = Get-Content $file -Raw
    $content = $content -replace [regex]::Escape($oldVersion), $newVersion
    $utf8NoBOM = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($file, $content, $utf8NoBOM)
}

# ─── Helper: Update version in JSON metadata file ────────────────────────────
function Set-JsonVersion($jsonFile, $newVersion) {
    $data = Get-Content $jsonFile -Raw | ConvertFrom-Json
    $data.version = $newVersion
    $content = $data | ConvertTo-Json -Depth 5
    $utf8NoBOM = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($jsonFile, $content, $utf8NoBOM)
}

# ─── Helper: Create a zip of a plugin directory ──────────────────────────────
function Build-PluginZip($sourceDir, $zipPath, $pluginFolderName) {
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

    # Items to exclude from the zip
    $excludeNames = @('.git', '.gitignore', 'node_modules', '*.log')

    # Create a temp staging folder so the zip has the right folder structure
    $tempStage = Join-Path $env:TEMP "wc_superadmin_stage\$pluginFolderName"
    if (Test-Path (Join-Path $env:TEMP "wc_superadmin_stage")) {
        Remove-Item (Join-Path $env:TEMP "wc_superadmin_stage") -Recurse -Force
    }
    New-Item -ItemType Directory -Force -Path $tempStage | Out-Null

    # Copy files, skipping excludes
    Get-ChildItem $sourceDir | Where-Object {
        $excludeNames -notcontains $_.Name -and $_.Name -notmatch '\.log$'
    } | ForEach-Object {
        Copy-Item $_.FullName -Destination $tempStage -Recurse -Force
    }

    Compress-Archive -Path (Join-Path $env:TEMP "wc_superadmin_stage\*") -DestinationPath $zipPath -Force
    Remove-Item (Join-Path $env:TEMP "wc_superadmin_stage") -Recurse -Force
    Write-Host "  Zipped: $zipPath" -ForegroundColor Cyan
}

# ─── MAIN ────────────────────────────────────────────────────────────────────

Write-Host "`n=== WC Superadmin Build Script ===" -ForegroundColor Yellow

# 1. Read & bump versions
$hubOldVer    = Get-PluginVersion $HubMainFile
$clientOldVer = Get-PluginVersion $ClientMainFile

$hubNewVer    = Bump-Version $hubOldVer $BumpType
$clientNewVer = Bump-Version $clientOldVer $BumpType

$hubOldStr    = Version-String $hubOldVer
$hubNewStr    = Version-String $hubNewVer
$clientOldStr = Version-String $clientOldVer
$clientNewStr = Version-String $clientNewVer

Write-Host "`nVersion bumps ($BumpType):"
Write-Host "  Hub:    $hubOldStr -> $hubNewStr"
Write-Host "  Client: $clientOldStr -> $clientNewStr"

# 2. Apply version changes
Write-Host "`nUpdating version strings..." -ForegroundColor Yellow
Set-FileVersion $HubMainFile    $hubOldStr    $hubNewStr
Set-FileVersion $ClientMainFile $clientOldStr $clientNewStr
Set-JsonVersion $HubJson        $hubNewStr
Set-JsonVersion $ClientJson     $clientNewStr

# 3. Build zips
Write-Host "`nBuilding zip files..." -ForegroundColor Yellow
Build-PluginZip $HubDir    $HubZip    "wc-superadmin-hub"
Build-PluginZip $ClientDir $ClientZip "wc-superadmin-client"

# 4. Git commit and push
$commitMsg = if ($Message) { $Message } else { "Release Hub v$hubNewStr / Client v$clientNewStr" }

Write-Host "`nCommitting to git..." -ForegroundColor Yellow
Set-Location $Root
git add .
git commit -m $commitMsg
git push origin main

Write-Host "`n=== Done! ===" -ForegroundColor Green
Write-Host "Hub    : v$hubNewStr"
Write-Host "Client : v$clientNewStr"
Write-Host "Commit : $commitMsg`n"

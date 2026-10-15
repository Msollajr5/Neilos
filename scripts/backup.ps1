# ============================================================
# Neilos Partner Portal — Portable PowerShell Backup Script
# Usage: powershell .\scripts\backup.ps1 [-OutputDir "path/to/backups"]
# ============================================================
param(
    [string]$OutputDir = ""
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectDir = Split-Path -Parent $scriptDir

if ([string]::IsNullOrWhiteSpace($OutputDir)) {
    $OutputDir = Join-Path $projectDir "backups"
}

if (!(Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$timestamp = Get-Date -Format "yyyy-MM-dd_HHmmss"
$targetZip = Join-Path $OutputDir "system-migration-$timestamp.zip"

Write-Host "============================================================"
Write-Host "Neilos Partner Portal — System Migration Backup"
Write-Host "============================================================"
Write-Host "Project Directory: $projectDir"
Write-Host "Target Package:    $targetZip"
Write-Host ""

# Run portable PHP export engine
$phpCmd = "php"
$exportScript = Join-Path $scriptDir "export-system.php"

& $phpCmd $exportScript "--output=$targetZip"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[+] PowerShell backup completed successfully."
} else {
    Write-Host "[-] Backup script returned error code $LASTEXITCODE."
    exit $LASTEXITCODE
}

# Retain only last 7 backups
$oldBackups = Get-ChildItem "$OutputDir\system-migration-*.zip" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-7) }
$oldBackups | ForEach-Object { Remove-Item $_.FullName -Force; Write-Host "[Neilos Backup] Removed old backup: $($_.Name)" }

Write-Host "[Neilos Backup] Completed successfully at $(Get-Date)."

# ============================================================
# SEPJ Gabès — Automated Daily Backup Script
# File: deploy/backup-sepj.ps1
#
# SETUP:
# 1. Edit the variables below (PROJECT_PATH, DB_PASS, BACKUP_ROOT)
# 2. Create the backup directory: New-Item -ItemType Directory -Force "C:\backups\sepj"
# 3. Schedule via Task Scheduler (run as SYSTEM, daily at 02:00):
#    schtasks /create /tn "SEPJ Daily Backup" /tr "powershell -ExecutionPolicy Bypass -File C:\backups\backup-sepj.ps1" /sc daily /st 02:00 /ru SYSTEM
# ============================================================

# ── Configuration — edit these ───────────────────────────────
$PROJECT_PATH = "C:\your\path\to\sepj-gabes"   # <-- change to real path
$BACKUP_ROOT  = "C:\backups\sepj"
$MYSQL_BIN    = "C:\xampp\mysql\bin"            # <-- change if MySQL is elsewhere
$DB_NAME      = "sepj_gabes"
$DB_USER      = "sepj_user"
$DB_PASS      = "CHANGE_ME_STRONG_PASSWORD"     # <-- set your real DB password
$KEEP_DAYS    = 30                              # delete backups older than this
# ─────────────────────────────────────────────────────────────

$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm"
$dest = "$BACKUP_ROOT\$timestamp"

# 1. Create timestamped staging directory
New-Item -ItemType Directory -Force -Path $dest | Out-Null
Write-Host "[1/5] Backup started: $timestamp"

# 2. Database dump (--single-transaction = consistent snapshot without locking)
$dumpFile = "$dest\db-$timestamp.sql"
& "$MYSQL_BIN\mysqldump.exe" `
    "--user=$DB_USER" `
    "--password=$DB_PASS" `
    --single-transaction `
    --routines `
    --triggers `
    --add-drop-table `
    $DB_NAME | Out-File -FilePath $dumpFile -Encoding utf8

if ($LASTEXITCODE -eq 0) {
    $sizeMB = [math]::Round((Get-Item $dumpFile).Length / 1MB, 2)
    Write-Host "[2/5] Database dumped: $sizeMB MB"
} else {
    Write-Error "mysqldump failed! Check credentials and try again."
    exit 1
}

# 3. User uploads (cannot be regenerated from git)
$uploadsLog = "$dest\robocopy-uploads.log"
robocopy "$PROJECT_PATH\public\uploads" "$dest\uploads" /E /NP /R:2 /W:5 /LOG+:"$uploadsLog" | Out-Null
Write-Host "[3/5] Uploads copied."

# 4. Config files (credentials not in git — these MUST be backed up)
$configDir = "$dest\config"
New-Item -ItemType Directory -Force -Path $configDir | Out-Null
Copy-Item "$PROJECT_PATH\app\config\app.php"         "$configDir\" -Force
Copy-Item "$PROJECT_PATH\app\config\database.php"    "$configDir\" -Force
Copy-Item "$PROJECT_PATH\app\config\mail.local.php"  "$configDir\" -ErrorAction SilentlyContinue -Force
Write-Host "[4/5] Config files copied."

# 5. Compress to zip and remove staging dir
$zipFile = "$BACKUP_ROOT\backup-$timestamp.zip"
Compress-Archive -Path $dest -DestinationPath $zipFile -Force
Remove-Item -Recurse -Force $dest
$zipSizeMB = [math]::Round((Get-Item $zipFile).Length / 1MB, 2)
Write-Host "[5/5] Compressed: $zipFile ($zipSizeMB MB)"

# 6. Retention policy — delete zips older than $KEEP_DAYS days
$removed = 0
Get-ChildItem "$BACKUP_ROOT\*.zip" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$KEEP_DAYS) } | ForEach-Object {
    Remove-Item $_.FullName -Force
    $removed++
}
if ($removed -gt 0) { Write-Host "Removed $removed old backup(s) (>${KEEP_DAYS}d)." }

Write-Host "Backup complete."

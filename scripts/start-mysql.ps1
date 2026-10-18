$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$server = Join-Path $root '.runtime\mysql-8.4.11-winx64\bin\mysqld.exe'
$local = Join-Path $root '.local\mysql'
$data = Join-Path $local 'data'
$config = Join-Path $local 'my.ini'
$init = Join-Path $local 'initialize.sql'
if (!(Test-Path -LiteralPath $server)) { throw 'MySQL 8.4.11 ZIP zuerst laut lokaler Anleitung installieren.' }
$listeners = @(Get-NetTCPConnection -State Listen -LocalPort 3307 -ErrorAction SilentlyContinue)
if ($listeners.Count) {
    foreach ($listener in $listeners) {
        $process = Get-CimInstance Win32_Process -Filter "ProcessId=$($listener.OwningProcess)"
        if ($listener.LocalAddress -ne '127.0.0.1' -or $process.ExecutablePath -ne $server -or $process.CommandLine -notlike "*$config*") {
            throw 'Port 3307 wird nicht eindeutig vom eigenen Nova-MySQL verwendet.'
        }
    }
    if (Test-Path -LiteralPath $init) { Remove-Item -LiteralPath $init }
    Write-Host 'Eigenes lokales Nova-MySQL läuft bereits.'
    return
}
New-Item -ItemType Directory -Path $local -Force | Out-Null
$settings = @{}
Get-Content (Join-Path $root '.env') | ForEach-Object {
    if ($_ -match '^(NOVA_MYSQL_ROOT_PASSWORD|NOVA_MYSQL_PASSWORD)=([A-Fa-f0-9]{64})$') { $settings[$Matches[1]] = $Matches[2] }
}
if ($settings.Count -ne 2) { throw 'Zufällige lokale MySQL-Passwörter fehlen oder haben ein ungültiges Format.' }
$base = (Split-Path (Split-Path $server -Parent) -Parent).Replace('\', '/')
$dataPath = $data.Replace('\', '/')
@"
[mysqld]
basedir=$base
datadir=$dataPath
port=3307
bind-address=127.0.0.1
mysqlx=0
skip-name-resolve
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
log-error=$($local.Replace('\', '/'))/error.log
"@ | Set-Content -LiteralPath $config -Encoding utf8
$fresh = !(Test-Path -LiteralPath $data)
if ($fresh) {
    & $server "--defaults-file=$config" --initialize
    if ($LASTEXITCODE -ne 0) { throw 'MySQL-Initialisierung fehlgeschlagen. Eigenes Fehlerprotokoll prüfen; keine Daten löschen.' }
    @"
ALTER USER 'root'@'localhost' IDENTIFIED BY '$($settings.NOVA_MYSQL_ROOT_PASSWORD)';
CREATE DATABASE merlin_nova_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE merlin_nova_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nova'@'127.0.0.1' IDENTIFIED BY '$($settings.NOVA_MYSQL_PASSWORD)';
GRANT ALL PRIVILEGES ON merlin_nova_dev.* TO 'nova'@'127.0.0.1';
GRANT ALL PRIVILEGES ON merlin_nova_test.* TO 'nova'@'127.0.0.1';
"@ | Set-Content -LiteralPath $init -Encoding utf8
}
if ((Test-Path -LiteralPath $init) -and !$fresh) {
    throw 'Unvollständige Ersteinrichtung erkannt. Initialisierung vor einem erneuten Start prüfen.'
}
$arguments = @("--defaults-file=`"$config`"")
if ($fresh) { $arguments += "--init-file=`"$init`"" }
$started = Start-Process -FilePath $server -ArgumentList $arguments -WindowStyle Hidden -PassThru
for ($attempt = 0; $attempt -lt 60; $attempt++) {
    Start-Sleep -Seconds 1
    if ($started.HasExited) { throw 'Eigenes MySQL wurde beendet. .local/mysql/error.log prüfen.' }
    $listener = Get-NetTCPConnection -State Listen -LocalAddress 127.0.0.1 -LocalPort 3307 -ErrorAction SilentlyContinue
    $owner = if ($listener) { Get-CimInstance Win32_Process -Filter "ProcessId=$($listener.OwningProcess)" }
    if ($owner -and $owner.ExecutablePath -eq $server -and $owner.CommandLine -like "*$config*") {
        if ($fresh) { Remove-Item -LiteralPath $init }
        Write-Host 'Nova-MySQL läuft ausschließlich auf 127.0.0.1:3307.'
        return
    }
}
throw 'Nova-MySQL wurde nicht rechtzeitig bereit. Eigenes Fehlerprotokoll prüfen.'

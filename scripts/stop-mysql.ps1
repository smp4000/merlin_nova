$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$server = Join-Path $root '.runtime\mysql-8.4.11-winx64\bin\mysqld.exe'
$config = Join-Path $root '.local\mysql\my.ini'
$listener = Get-NetTCPConnection -State Listen -LocalAddress 127.0.0.1 -LocalPort 3307 -ErrorAction SilentlyContinue
if (!$listener) { Write-Host 'Eigenes Nova-MySQL läuft nicht.'; return }
$owner = Get-CimInstance Win32_Process -Filter "ProcessId=$($listener.OwningProcess)"
if ($owner.ExecutablePath -ne $server -or $owner.CommandLine -notlike "*$config*") {
    throw 'Kein eindeutig eigenes Nova-MySQL. Es wird nichts beendet.'
}
$monitor = Get-CimInstance Win32_Process -Filter "ProcessId=$($owner.ParentProcessId)"
if ($monitor.ExecutablePath -ne $server -or $monitor.CommandLine -notlike "*$config*") {
    throw 'Eigener MySQL-Monitor nicht eindeutig identifiziert.'
}
$event = [Threading.EventWaitHandle]::OpenExisting("mysqld$($monitor.ProcessId)_shutdown")
try {
    $null = $event.Set()
} finally { $event.Dispose() }
for ($attempt = 0; $attempt -lt 30; $attempt++) {
    if (!(Get-Process -Id $owner.ProcessId -ErrorAction SilentlyContinue)) {
        Write-Host 'Eigenes Nova-MySQL geordnet beendet.'
        return
    }
    Start-Sleep -Seconds 1
}
throw 'Geordnetes MySQL-Shutdown noch nicht abgeschlossen; Prozess nicht gewaltsam beenden.'

param([switch]$NoBrowser)

# Ein-Klick-Start ausschließlich der vorhandenen lokalen Nova-Umgebung.
# Keine Installation/Migration und keine Änderung an Diensten oder Konfiguration.
# Laufende eigene Prozesse werden wiederverwendet, fremde Portbelegungen abgewiesen.
$ErrorActionPreference = 'Stop'
$novaRoot = Split-Path $PSScriptRoot -Parent
$novaPhp = Join-Path $novaRoot '.runtime/php/php.exe'
$novaIni = Join-Path $novaRoot '.runtime/php/php.ini'
$novaWeb = Join-Path $novaRoot 'web'
$novaLogs = Join-Path $novaRoot '.local/launcher'
$novaMutex = [Threading.Mutex]::new($false, 'Local\MerlinNovaLocalLauncher')
$novaOwnsMutex = $false

function Get-NovaWebListener {
    $listeners = @(Get-NetTCPConnection -State Listen -LocalPort 8095 -ErrorAction SilentlyContinue)
    foreach ($listener in $listeners) {
        $owner = Get-CimInstance Win32_Process -Filter "ProcessId=$($listener.OwningProcess)"
        if ($listener.LocalAddress -ne '127.0.0.1' -or
            $owner.ExecutablePath -ne $novaPhp -or
            $owner.CommandLine -notlike "*$novaWeb*" -or
            $owner.CommandLine -notlike '*127.0.0.1:8095*') {
            throw 'Port 8095 wird von einem anderen Prozess verwendet. Es wurde nichts beendet.'
        }
    }
    return $listeners.Count -gt 0
}

try {
    $novaOwnsMutex = $novaMutex.WaitOne(0)
    if (-not $novaOwnsMutex) { return }
    if (-not (Test-Path -LiteralPath $novaPhp) -or -not (Test-Path -LiteralPath $novaIni)) {
        throw 'Die eigene Nova-PHP-Laufzeit fehlt. Bitte die lokale Einrichtung prüfen.'
    }
    # Start-MySQL soll hier ausschließlich bestehende Daten starten, keine neue
    # Umgebung initialisieren oder bei fehlenden Dateien Konfiguration erzeugen.
    foreach ($relative in @('.local/mysql/data', '.local/mysql/my.ini')) {
        if (-not (Test-Path -LiteralPath (Join-Path $novaRoot $relative))) {
            throw 'Die vorhandene Nova-Datenbankumgebung fehlt. Bitte die lokale Einrichtung prüfen.'
        }
    }
    & (Join-Path $PSScriptRoot 'start-mysql.ps1')
    if (-not (Get-NovaWebListener)) {
        New-Item -ItemType Directory -Path $novaLogs -Force | Out-Null
        $env:PHPRC = Split-Path $novaIni -Parent
        $env:PHP_INI_SCAN_DIR = Join-Path $env:PHPRC 'nova-empty-scan'
        $server = Start-Process -FilePath $novaPhp -WorkingDirectory $novaWeb -WindowStyle Hidden -PassThru `
            -ArgumentList @('-c', "`"$novaIni`"", 'artisan', 'serve', '--host=127.0.0.1', '--port=8095', '--tries=1', '--no-reload') `
            -RedirectStandardOutput (Join-Path $novaLogs 'web-output.log') `
            -RedirectStandardError (Join-Path $novaLogs 'web-error.log')
        $ready = $false
        for ($attempt = 0; $attempt -lt 30; $attempt++) {
            if ($server.HasExited) { throw 'Der Nova-Webserver wurde beendet. Siehe .local/launcher/web-error.log.' }
            if (Get-NovaWebListener) { $ready = $true; break }
            Start-Sleep -Milliseconds 500
        }
        if (-not $ready) { throw 'Der Nova-Webserver wurde nicht rechtzeitig bereit.' }
    }
    $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8095/' -TimeoutSec 15
    if ($response.StatusCode -ne 200) { throw 'Die Nova-Startseite konnte nicht geladen werden.' }
    if (-not $NoBrowser) { Start-Process 'http://127.0.0.1:8095/' }
    Write-Output 'Merlin Nova ist bereit: http://127.0.0.1:8095/'
} catch {
    if ($NoBrowser) { throw }
    Add-Type -AssemblyName System.Windows.Forms
    [void][Windows.Forms.MessageBox]::Show($_.Exception.Message, 'Merlin Nova starten', 'OK', 'Error')
} finally {
    if ($novaOwnsMutex) { $novaMutex.ReleaseMutex() }
    $novaMutex.Dispose()
}

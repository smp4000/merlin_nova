<#
Reproduzierbare Einrichtung ausschließlich im neuen lokalen Projektordner.
Vorhandene Konfiguration wird nicht überschrieben; MySQL bleibt ein eigener
Prozess. Filament-Assets werden aus den gepinnten Composer-Paketen erzeugt,
damit generierte Dateien nicht als zweite Versionsquelle in Git landen.
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$runtime = Join-Path $root '.runtime\php'
if (Test-Path (Join-Path $runtime 'php.exe')) {
    $env:PHPRC = $runtime
    $env:PATH = "$runtime;$env:PATH"
    $env:PHP_INI_SCAN_DIR = Join-Path $runtime 'nova-empty-scan'
}
& php -r 'exit(PHP_VERSION_ID >= 80401 && extension_loaded("pdo_mysql") && extension_loaded("intl") && extension_loaded("zip") ? 0 : 1);'
if ($LASTEXITCODE -ne 0) { throw 'PHP ab 8.4.1 mit pdo_mysql, intl und zip erforderlich.' }
$rootEnv = Join-Path $root '.env'
$webEnv = Join-Path $root 'web\.env'
if ((Test-Path $rootEnv) -xor (Test-Path $webEnv)) {
    throw 'Nur eine lokale Konfiguration vorhanden. Zugangsdaten zunächst manuell abgleichen.'
}
if (!(Test-Path $rootEnv)) {
    $rootPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    $appPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    $appKey = [Convert]::ToBase64String([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    "NOVA_MYSQL_ROOT_PASSWORD=$rootPassword`nNOVA_MYSQL_PASSWORD=$appPassword" | Set-Content $rootEnv -Encoding utf8
    (Get-Content (Join-Path $root 'web\.env.example') -Raw).Replace('APP_KEY=', "APP_KEY=base64:$appKey").Replace('DB_PASSWORD=', "DB_PASSWORD=$appPassword") | Set-Content $webEnv -Encoding utf8
}
Push-Location $root
try {
    & (Join-Path $PSScriptRoot 'install-mysql.ps1')
    & (Join-Path $PSScriptRoot 'start-mysql.ps1')
    Push-Location (Join-Path $root 'web')
    try {
        foreach ($directory in @('storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/private')) {
            New-Item -ItemType Directory -Path $directory -Force | Out-Null
        }
        & composer install --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Composer-Installation fehlgeschlagen.' }
        & php artisan filament:assets
        if ($LASTEXITCODE -ne 0) { throw 'Lokale Filament-Assets konnten nicht erzeugt werden.' }
        & php artisan migrate
        if ($LASTEXITCODE -ne 0) { throw 'Lokale Migration fehlgeschlagen.' }
        & npm ci --ignore-scripts
        if ($LASTEXITCODE -ne 0) { throw 'Frontend-Installation fehlgeschlagen.' }
        & npm run build
        if ($LASTEXITCODE -ne 0) { throw 'Frontend-Build fehlgeschlagen.' }
    } finally { Pop-Location }
} finally { Pop-Location }

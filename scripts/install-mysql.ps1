$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$runtime = Join-Path $root '.runtime'
$package = Join-Path $runtime 'downloads\mysql-8.4.11-winx64.zip'
$server = Join-Path $runtime 'mysql-8.4.11-winx64\bin\mysqld.exe'
if (Test-Path -LiteralPath $server) {
    $signature = Get-AuthenticodeSignature -LiteralPath $server
    if ($signature.Status -ne 'Valid' -or $signature.SignerCertificate.Subject -notlike '*Oracle America*') { throw 'MySQL-Herstellersignatur ungültig.' }
    return
}
New-Item -ItemType Directory -Path (Split-Path $package -Parent) -Force | Out-Null
if (!(Test-Path -LiteralPath $package)) {
    Invoke-WebRequest -Uri 'https://cdn.mysql.com/Downloads/MySQL-8.4/mysql-8.4.11-winx64.zip' -OutFile $package -TimeoutSec 300
}
if ((Get-FileHash -LiteralPath $package -Algorithm SHA256).Hash -ne 'A492371D687D2BAB088B0062581144A0044B8964BAEFDF4FAA579292B423D25C') {
    throw 'MySQL-Paket hat eine abweichende Prüfsumme; keine Installation.'
}
Expand-Archive -LiteralPath $package -DestinationPath $runtime
$signature = Get-AuthenticodeSignature -LiteralPath $server
if ($signature.Status -ne 'Valid' -or $signature.SignerCertificate.Subject -notlike '*Oracle America*') {
    throw 'MySQL-Paket hat keine gültige Oracle-Herstellersignatur.'
}
Write-Host 'Eigenständige MySQL-Laufzeit installiert; kein Windows-Dienst angelegt.'

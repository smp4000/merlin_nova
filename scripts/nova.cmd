@echo off
rem Nutzt nur die eigene Nova-PHP-Version und deren Konfiguration.
rem setlocal und pushd verhindern dauerhafte Umstellungen im Benutzerterminal.
rem Funktioniert auch aus Windows PowerShell ohne installiertes pwsh.
setlocal
set "NOVA_PHP=%~dp0..\.runtime\php\php.exe"
set "NOVA_INI=%~dp0..\.runtime\php\php.ini"
if not exist "%NOVA_PHP%" exit /b 1
if not exist "%NOVA_INI%" exit /b 1
set "PHP_INI_SCAN_DIR=%~dp0..\.runtime\php\nova-empty-scan"
pushd "%~dp0..\web" || exit /b 1
"%NOVA_PHP%" -c "%NOVA_INI%" %*
set "NOVA_RESULT=%errorlevel%"
popd
exit /b %NOVA_RESULT%

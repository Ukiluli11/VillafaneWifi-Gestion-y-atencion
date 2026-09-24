@echo off
setlocal
set "LAUNCHER="
for /d %%D in ("%~dp0Codificaci?n del Sistema") do set "LAUNCHER=%%~fD\tools\windows\launcher-villafane.ps1"

if not defined LAUNCHER (
    echo No se encontro la carpeta de codificacion del sistema.
    pause
    exit /b 1
)

if not exist "%LAUNCHER%" (
    echo No se encontro el launcher en:
    echo %LAUNCHER%
    pause
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -STA -File "%LAUNCHER%"

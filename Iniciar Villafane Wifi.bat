@echo off
setlocal
set "LAUNCHER=%~dp0sistema\tools\windows\launcher-villafane.ps1"

if not exist "%LAUNCHER%" (
    echo No se encontro el launcher en:
    echo %LAUNCHER%
    pause
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -STA -File "%LAUNCHER%"

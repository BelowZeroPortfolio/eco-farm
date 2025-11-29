@echo off
REM Start Arduino Bridge + ngrok Tunnel
REM Similar to YOLO setup

echo ============================================================
echo Arduino Sensor + ngrok Startup Script
echo ============================================================
echo.

cd /d "%~dp0"

echo Checking Python...
python --version
if errorlevel 1 (
    echo ERROR: Python not installed
    pause
    exit /b 1
)

echo Checking ngrok...
ngrok version
if errorlevel 1 (
    echo ERROR: ngrok not installed
    echo Install: winget install ngrok.ngrok
    pause
    exit /b 1
)

echo.
echo Stopping any existing services...
taskkill /FI "WINDOWTITLE eq Arduino Bridge*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq Arduino Push*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq ngrok*Arduino*" /F >nul 2>&1
timeout /t 2 >nul

echo.
echo Starting Arduino Bridge Service (port 5001)...
start "Arduino Bridge Service" python arduino_bridge.py

echo Waiting 5 seconds for Arduino bridge to start...
timeout /t 5 >nul

echo.
echo Testing Arduino bridge...
curl -s http://127.0.0.1:5001/health
if errorlevel 1 (
    echo WARNING: Arduino bridge may not have started
    echo Check the "Arduino Bridge Service" window
    pause
)

echo.
echo Starting ngrok tunnel for Arduino (port 5001)...
start "ngrok Tunnel - Arduino" ngrok http 5001

echo Waiting 3 seconds for ngrok to start...
timeout /t 3 >nul

echo.
echo Starting Arduino Push Service...
start "Arduino Push Service" php arduino_push_service.php

echo.
echo ============================================================
echo SUCCESS! Services are running
echo ============================================================
echo.
echo You should see 3 new windows:
echo   1. "Arduino Bridge Service" - Reads from Arduino
echo   2. "ngrok Tunnel - Arduino" - Public tunnel
echo   3. "Arduino Push Service" - Pushes to InfinityFree
echo.
echo ============================================================
echo NEXT STEPS:
echo ============================================================
echo.
echo 1. Look at the "ngrok Tunnel - Arduino" window
echo 2. Find the HTTPS URL (e.g., https://fredda-unprecisive-unashamedly.ngrok-free.dev)
echo 3. Update config/env.php with:
echo    'ARDUINO_SENSOR_HOST' =^> 'fredda-unprecisive-unashamedly.ngrok-free.dev'
echo 4. Upload config/env.php to InfinityFree
echo.
echo ============================================================
echo.
echo Keep all windows open while monitoring!
echo.
pause

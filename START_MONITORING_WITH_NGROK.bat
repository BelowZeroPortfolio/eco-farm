@echo off
echo ============================================================
echo    SAGAYE ECO FARM - MONITORING WITH NGROK
echo ============================================================
echo.
echo NOTE: InfinityFree antibot blocks direct uploads.
echo       Website will PULL data from your ngrok tunnel instead!
echo.

REM Check Python
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python not installed
    pause
    exit /b 1
)

REM Check PHP
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP not installed
    pause
    exit /b 1
)

REM Check ngrok
ngrok version >nul 2>&1
if errorlevel 1 (
    echo ERROR: ngrok not installed
    echo Install: winget install ngrok.ngrok
    pause
    exit /b 1
)

echo Stopping any existing services...
taskkill /FI "WINDOWTITLE eq Arduino Bridge*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq Local Sensor*" /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq ngrok*" /F >nul 2>&1
timeout /t 2 /nobreak >nul

echo.
echo [1/3] Starting Arduino Bridge Service (Port 5001)...
start "Arduino Bridge - Port 5001" cmd /k "python arduino_bridge.py"
timeout /t 5 /nobreak >nul

echo [2/3] Starting ngrok Tunnel for Arduino...
start "ngrok Tunnel - Arduino" cmd /k "ngrok http 5001"
timeout /t 5 /nobreak >nul

echo [3/3] Starting Local Database Sync...
start "Local Sensor Sync" cmd /k "php local_sensor_sync.php"
timeout /t 2 /nobreak >nul

echo.
echo ============================================================
echo   GETTING YOUR NGROK URL...
echo ============================================================
timeout /t 3 /nobreak >nul

REM Try to get ngrok URL automatically
curl -s http://127.0.0.1:4040/api/tunnels > "%TEMP%\ngrok_info.json" 2>nul
if exist "%TEMP%\ngrok_info.json" (
    echo.
    echo Your ngrok tunnel info:
    type "%TEMP%\ngrok_info.json"
    echo.
)

echo.
echo ============================================================
echo                    SERVICES RUNNING
echo ============================================================
echo   Arduino Bridge:  http://127.0.0.1:5001/data
echo   ngrok Tunnel:    Check ngrok window for public URL
echo   Local Sync:      Saving readings to local database
echo   Website:         https://sagayecofarm.infinityfreeapp.com
echo ============================================================
echo.
echo HOW IT WORKS (bypasses InfinityFree antibot):
echo   1. Arduino data is exposed via ngrok tunnel
echo   2. InfinityFree website PULLS data from your ngrok URL
echo   3. Data is saved to InfinityFree DB when dashboard loads
echo.
echo IMPORTANT - UPDATE NGROK URL:
echo   1. Check ngrok window for URL (e.g. https://abc123.ngrok-free.dev)
echo   2. Update ARDUINO_SENSOR_HOST in config/env.php on InfinityFree
echo      Example: 'ARDUINO_SENSOR_HOST' =^> 'abc123.ngrok-free.dev'
echo   3. Upload updated config/env.php to InfinityFree
echo.
echo FILES TO UPLOAD TO INFINITYFREE:
echo   - config/env.php (with your ngrok URL)
echo   - api/sync_from_ngrok.php
echo   - api/get_sensor_data_ngrok.php
echo   - dashboard.php
echo.
echo Keep Arduino Bridge and ngrok windows open!
echo.
pause

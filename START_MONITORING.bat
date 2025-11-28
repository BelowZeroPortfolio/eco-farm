@echo off
echo ╔════════════════════════════════════════════════════════════╗
echo ║     SAGAYE ECO FARM - LIVE MONITORING SYSTEM              ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Starting services for DEFENSE PRESENTATION...
echo.

REM Check if Python is available
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.x first
    pause
    exit /b 1
)

REM Check if PHP is available
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP first
    pause
    exit /b 1
)

echo [1/2] Starting Arduino Bridge Service (Port 5001)...
start "Arduino Bridge - Port 5001" cmd /k "python arduino_bridge.py"
timeout /t 3 /nobreak >nul

echo [2/2] Starting Online Sync Service (InfinityFree)...
start "Online Sync - InfinityFree" cmd /k "php sync_sensors_online.php"
timeout /t 2 /nobreak >nul

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║                    SERVICES RUNNING                        ║
echo ╠════════════════════════════════════════════════════════════╣
echo ║  ✓ Arduino Bridge:  http://127.0.0.1:5001/data            ║
echo ║  ✓ Online Sync:     Uploading to InfinityFree             ║
echo ║  ✓ Website:         https://sagayecofarm.infinityfreeapp.com/sensors.php
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo DEFENSE READY! Panelists can now view live sensor data.
echo.
echo IMPORTANT:
echo - Keep both terminal windows open during defense
echo - Data updates every 1 minute (configurable in .env)
echo - Close terminal windows to stop services
echo.
pause

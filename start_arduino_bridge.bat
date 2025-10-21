@echo off
echo ============================================================
echo Arduino Bridge Service - IoT Farm Monitoring System
echo ============================================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ and add it to your PATH
    pause
    exit /b 1
)

REM Check if required packages are installed
echo Checking Python packages...
python -c "import flask, serial" >nul 2>&1
if errorlevel 1 (
    echo Installing required Python packages...
    pip install flask pyserial
    if errorlevel 1 (
        echo ERROR: Failed to install required packages
        pause
        exit /b 1
    )
)

echo Starting Arduino Bridge Service...
echo.
echo Service will run on: http://127.0.0.1:5000
echo Press Ctrl+C to stop the service
echo.

REM Start the Python service
python arduino_bridge.py

pause
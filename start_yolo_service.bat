@echo off
REM Start YOLO Detection Service (Flask Version)
REM Windows Batch Script

echo ========================================
echo YOLO Pest Detection Service Starter
echo ========================================
echo.

REM Check if Python is available
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ and try again
    pause
    exit /b 1
)

REM Check if Flask is installed
python -c "import flask" >nul 2>&1
if errorlevel 1 (
    echo ERROR: Flask is not installed
    echo Installing Flask...
    pip install flask
    if errorlevel 1 (
        echo ERROR: Failed to install Flask
        pause
        exit /b 1
    )
)

REM Check if ultralytics is installed
python -c "import ultralytics" >nul 2>&1
if errorlevel 1 (
    echo ERROR: Ultralytics is not installed
    echo Please install: pip install ultralytics
    pause
    exit /b 1
)

REM Check if model file exists
if not exist "best.pt" (
    echo ERROR: Model file 'best.pt' not found
    echo Please ensure best.pt is in the current directory
    pause
    exit /b 1
)

REM Check if service is already running
tasklist /FI "WINDOWTITLE eq YOLO Service*" 2>NUL | find /I /N "python.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo WARNING: YOLO service appears to be already running
    echo.
    choice /C YN /M "Do you want to restart it"
    if errorlevel 2 goto :skip_kill
    echo Stopping existing service...
    taskkill /FI "WINDOWTITLE eq YOLO Service*" /F >nul 2>&1
    timeout /t 2 >nul
)

:skip_kill

echo.
echo Starting YOLO Detection Service...
echo Service will run on: http://127.0.0.1:5000
echo.
echo Press Ctrl+C to stop the service
echo ========================================
echo.

REM Start the service in a new window with a title
start "YOLO Service - Flask" python yolo_detect2.py

REM Wait a moment for service to start
timeout /t 3 >nul

REM Test if service is responding
echo Testing service health...
curl -s http://127.0.0.1:5000/health >nul 2>&1
if errorlevel 1 (
    echo WARNING: Service may not have started correctly
    echo Check the YOLO Service window for errors
) else (
    echo.
    echo ========================================
    echo SUCCESS: YOLO service is running!
    echo ========================================
    echo.
    echo Service URL: http://127.0.0.1:5000
    echo Health Check: http://127.0.0.1:5000/health
    echo.
    echo The service is running in a separate window.
    echo Close that window to stop the service.
)

echo.
pause

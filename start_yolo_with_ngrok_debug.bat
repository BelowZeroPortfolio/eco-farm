@echo off
REM Debug version - stays open to show errors

echo ============================================================
echo YOLO + ngrok Startup Script (DEBUG MODE)
echo ============================================================
echo.

REM Change to script directory
cd /d "%~dp0"
echo Current directory: %CD%
echo.

echo Checking Python...
python --version
if errorlevel 1 (
    echo.
    echo ERROR: Python is not installed or not in PATH
    echo.
    echo Please install Python from: https://www.python.org/downloads/
    echo Make sure to check "Add Python to PATH" during installation
    echo.
    goto :error_exit
)
echo Python OK!
echo.

echo Checking ngrok...
ngrok version
if errorlevel 1 (
    echo.
    echo ERROR: ngrok is not installed or not in PATH
    echo.
    echo Install ngrok:
    echo   Option 1: winget install ngrok.ngrok
    echo   Option 2: Download from https://ngrok.com/download
    echo.
    echo After installing, configure your authtoken:
    echo   ngrok config add-authtoken YOUR_TOKEN
    echo.
    goto :error_exit
)
echo ngrok OK!
echo.

echo Checking Flask...
python -c "import flask"
if errorlevel 1 (
    echo.
    echo ERROR: Flask is not installed
    echo.
    echo Installing Flask now...
    pip install flask
    if errorlevel 1 (
        echo Failed to install Flask
        goto :error_exit
    )
)
echo Flask OK!
echo.

echo Checking Ultralytics (YOLO)...
python -c "import ultralytics"
if errorlevel 1 (
    echo.
    echo ERROR: Ultralytics is not installed
    echo.
    echo Installing Ultralytics now...
    pip install ultralytics
    if errorlevel 1 (
        echo Failed to install Ultralytics
        goto :error_exit
    )
)
echo Ultralytics OK!
echo.

echo Checking model file (best.pt)...
if not exist "best.pt" (
    echo.
    echo ERROR: Model file 'best.pt' not found
    echo.
    echo Please make sure best.pt is in this directory:
    echo %CD%
    echo.
    goto :error_exit
)
echo Model file found!
echo.

echo Checking if yolo_detect2.py exists...
if not exist "yolo_detect2.py" (
    echo.
    echo ERROR: yolo_detect2.py not found
    echo.
    echo Please make sure yolo_detect2.py is in this directory:
    echo %CD%
    echo.
    goto :error_exit
)
echo yolo_detect2.py found!
echo.

echo ============================================================
echo All checks passed! Starting services...
echo ============================================================
echo.

REM Stop any existing services
echo Stopping any existing services...
taskkill /FI "WINDOWTITLE eq YOLO Service*" /F >nul 2>&1
taskkill /IM ngrok.exe /F >nul 2>&1
timeout /t 2 >nul

echo.
echo Starting Flask YOLO Service...
start "YOLO Service - Flask" python yolo_detect2.py

echo Waiting 5 seconds for Flask to start...
timeout /t 5 >nul

echo.
echo Testing Flask service...
curl -s http://127.0.0.1:5000/health
if errorlevel 1 (
    echo.
    echo WARNING: Flask service may not have started correctly
    echo Check the "YOLO Service - Flask" window for errors
    echo.
    echo Press any key to continue anyway, or close this window to stop
    pause
)
echo Flask service is running!
echo.

echo Starting ngrok tunnel...
start "ngrok Tunnel" ngrok http 5000

echo Waiting 3 seconds for ngrok to start...
timeout /t 3 >nul

echo.
echo ============================================================
echo SUCCESS! Services are running
echo ============================================================
echo.
echo You should see 2 new windows:
echo   1. "YOLO Service - Flask" - AI detection service
echo   2. "ngrok Tunnel" - Public tunnel
echo.
echo ============================================================
echo NEXT STEPS:
echo ============================================================
echo.
echo 1. Look at the "ngrok Tunnel" window
echo 2. Find the HTTPS URL (e.g., https://abc123.ngrok-free.app)
echo 3. Run: get_ngrok_url.bat (to help extract the URL)
echo 4. Update config/env.php with the URL
echo 5. Upload config/env.php to InfinityFree
echo.
echo ============================================================
echo.
echo Keep this window and the 2 service windows open!
echo Close them when you're done using the system.
echo.
goto :success_exit

:error_exit
echo.
echo ============================================================
echo SETUP FAILED - Please fix the errors above
echo ============================================================
echo.
echo Need help? Check START_HERE.md for detailed instructions
echo.
pause
exit /b 1

:success_exit
echo Press any key to close this window (services will keep running)
pause
exit /b 0

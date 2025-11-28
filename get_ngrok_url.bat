@echo off
REM Get ngrok URL Helper Script
REM Extracts the current ngrok public URL

echo ============================================================
echo ngrok URL Extractor
echo ============================================================
echo.

REM Check if ngrok is running
tasklist /FI "IMAGENAME eq ngrok.exe" 2>NUL | find /I /N "ngrok.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo ERROR: ngrok is not running
    echo Please start ngrok first using: start_yolo_with_ngrok.bat
    echo.
    pause
    exit /b 1
)

echo Fetching ngrok tunnel information...
echo.

REM Try to get URL from ngrok API
curl -s http://127.0.0.1:4040/api/tunnels > temp_ngrok.json 2>nul

if errorlevel 1 (
    echo ERROR: Could not connect to ngrok API
    echo.
    echo Please check the ngrok window manually and look for:
    echo    Forwarding    https://xxxxx.ngrok-free.app
    echo.
    pause
    exit /b 1
)

REM Parse JSON to extract HTTPS URL (simple method for Windows)
echo ============================================================
echo Your ngrok URLs:
echo ============================================================
echo.

REM Display the temp file content (user can see the URLs)
type temp_ngrok.json | findstr "public_url"

echo.
echo ============================================================
echo INSTRUCTIONS:
echo ============================================================
echo.
echo 1. Look for the HTTPS URL above (https://xxxxx.ngrok-free.app)
echo 2. Copy the URL WITHOUT "https://" 
echo    Example: abc123.ngrok-free.app
echo.
echo 3. Open config/env.php and update this line:
echo    'YOLO_SERVICE_HOST' =^> 'abc123.ngrok-free.app',
echo.
echo 4. Upload config/env.php to InfinityFree
echo.
echo 5. Test your website:
echo    https://sagayecofarm.infinityfreeapp.com/pest_detection.php
echo.
echo ============================================================
echo.
echo You can also view ngrok dashboard at:
echo    http://127.0.0.1:4040
echo.
echo ============================================================

REM Clean up temp file
del temp_ngrok.json >nul 2>&1

echo.
pause

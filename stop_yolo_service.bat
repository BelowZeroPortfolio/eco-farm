@echo off
REM Stop YOLO Detection Service
REM Windows Batch Script

echo ========================================
echo YOLO Pest Detection Service Stopper
echo ========================================
echo.

REM Check if service is running
tasklist /FI "WINDOWTITLE eq YOLO Service*" 2>NUL | find /I /N "python.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo No YOLO service found running
    echo.
    pause
    exit /b 0
)

echo Stopping YOLO Detection Service...
taskkill /FI "WINDOWTITLE eq YOLO Service*" /F

if errorlevel 1 (
    echo ERROR: Failed to stop service
    echo You may need to close the service window manually
) else (
    echo.
    echo ========================================
    echo SUCCESS: YOLO service stopped
    echo ========================================
)

echo.
pause

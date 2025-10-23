@echo off
echo ============================================================
echo PlatformIO Arduino Setup - IoT Farm Monitoring System
echo ============================================================
echo.

REM Check if PlatformIO is installed
pio --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PlatformIO is not installed
    echo.
    echo Please install PlatformIO first:
    echo 1. Install Python 3.8+ if not already installed
    echo 2. Run: pip install platformio
    echo 3. Or install PlatformIO IDE extension in VS Code
    echo.
    pause
    exit /b 1
)

echo ✅ PlatformIO found
echo.

REM Initialize PlatformIO project if not already done
if not exist ".pio" (
    echo Initializing PlatformIO project...
    pio project init
    if errorlevel 1 (
        echo ERROR: Failed to initialize PlatformIO project
        pause
        exit /b 1
    )
    echo ✅ Project initialized
) else (
    echo ✅ Project already initialized
)

echo.
echo Available commands:
echo.
echo Build for Arduino Mega 2560: pio run -e megaatmega2560
echo Upload to Arduino Mega:     pio run -e megaatmega2560 -t upload
echo.
echo Monitor serial output:     pio device monitor
echo.
echo Clean build files:         pio run -t clean
echo.
echo ============================================================
echo Setup complete! You can now build and upload your code.
echo ============================================================

pause
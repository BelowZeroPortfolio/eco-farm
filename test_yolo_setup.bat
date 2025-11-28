@echo off
REM Test YOLO + ngrok Setup
REM Checks if all required components are installed

echo ============================================================
echo YOLO + ngrok Setup Checker
echo ============================================================
echo.
echo This script will check if you have everything needed:
echo   - Python
echo   - Flask
echo   - Ultralytics (YOLO)
echo   - OpenCV
echo   - Pillow
echo   - ngrok
echo   - best.pt model file
echo.
echo ============================================================
echo.

set ALL_GOOD=1

REM Check Python
echo [1/7] Checking Python...
python --version >nul 2>&1
if errorlevel 1 (
    echo    [X] FAILED - Python is not installed
    echo        Install from: https://www.python.org/downloads/
    set ALL_GOOD=0
) else (
    for /f "tokens=*" %%i in ('python --version') do set PYTHON_VER=%%i
    echo    [OK] %PYTHON_VER%
)
echo.

REM Check Flask
echo [2/7] Checking Flask...
python -c "import flask; print('Flask', flask.__version__)" >nul 2>&1
if errorlevel 1 (
    echo    [X] FAILED - Flask is not installed
    echo        Install: pip install flask
    set ALL_GOOD=0
) else (
    for /f "tokens=*" %%i in ('python -c "import flask; print('Flask', flask.__version__)"') do echo    [OK] %%i
)
echo.

REM Check Ultralytics
echo [3/7] Checking Ultralytics (YOLO)...
python -c "import ultralytics; print('Ultralytics', ultralytics.__version__)" >nul 2>&1
if errorlevel 1 (
    echo    [X] FAILED - Ultralytics is not installed
    echo        Install: pip install ultralytics
    set ALL_GOOD=0
) else (
    for /f "tokens=*" %%i in ('python -c "import ultralytics; print('Ultralytics', ultralytics.__version__)"') do echo    [OK] %%i
)
echo.

REM Check OpenCV
echo [4/7] Checking OpenCV...
python -c "import cv2; print('OpenCV', cv2.__version__)" >nul 2>&1
if errorlevel 1 (
    echo    [X] FAILED - OpenCV is not installed
    echo        Install: pip install opencv-python
    set ALL_GOOD=0
) else (
    for /f "tokens=*" %%i in ('python -c "import cv2; print('OpenCV', cv2.__version__)"') do echo    [OK] %%i
)
echo.

REM Check Pillow
echo [5/7] Checking Pillow...
python -c "import PIL; print('Pillow', PIL.__version__)" >nul 2>&1
if errorlevel 1 (
    echo    [X] FAILED - Pillow is not installed
    echo        Install: pip install pillow
    set ALL_GOOD=0
) else (
    for /f "tokens=*" %%i in ('python -c "import PIL; print('Pillow', PIL.__version__)"') do echo    [OK] %%i
)
echo.

REM Check ngrok
echo [6/7] Checking ngrok...
ngrok version >nul 2>&1
if errorlevel 1 (
    echo    [X] FAILED - ngrok is not installed
    echo        Install: winget install ngrok.ngrok
    echo        Or download from: https://ngrok.com/download
    echo        Then configure: ngrok config add-authtoken YOUR_TOKEN
    set ALL_GOOD=0
) else (
    for /f "tokens=*" %%i in ('ngrok version') do echo    [OK] %%i
)
echo.

REM Check model file
echo [7/7] Checking YOLO model file (best.pt)...
if not exist "best.pt" (
    echo    [X] FAILED - best.pt not found in current directory
    echo        Make sure best.pt is in the same folder as this script
    set ALL_GOOD=0
) else (
    for %%A in ("best.pt") do set MODEL_SIZE=%%~zA
    echo    [OK] best.pt found (Size: %MODEL_SIZE% bytes)
)
echo.

echo ============================================================
echo RESULTS:
echo ============================================================
echo.

if %ALL_GOOD%==1 (
    echo [SUCCESS] All components are installed!
    echo.
    echo You are ready to start the services.
    echo.
    echo Next steps:
    echo   1. Double-click: start_yolo_with_ngrok.bat
    echo   2. Copy the ngrok URL
    echo   3. Update config/env.php
    echo   4. Upload to InfinityFree
    echo.
) else (
    echo [FAILED] Some components are missing.
    echo.
    echo Please install the missing components listed above.
    echo.
    echo Quick install all Python packages:
    echo   pip install flask ultralytics opencv-python pillow
    echo.
)

echo ============================================================
echo.
pause

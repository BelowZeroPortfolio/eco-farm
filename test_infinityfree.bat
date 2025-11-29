@echo off
echo ╔════════════════════════════════════════════════════════════╗
echo ║     TEST INFINITYFREE CONNECTION                           ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Testing if InfinityFree website is accessible...
echo.

echo [1/3] Testing website homepage...
curl -s -o nul -w "HTTP Status: %%{http_code}\n" https://sagayecofarm.infinityfreeapp.com/
echo.

echo [2/3] Testing API endpoint...
curl -s -o nul -w "HTTP Status: %%{http_code}\n" https://sagayecofarm.infinityfreeapp.com/api/upload_sensor.php
echo.

echo [3/3] Testing API with data...
curl -X POST https://sagayecofarm.infinityfreeapp.com/api/upload_sensor.php -d "api_key=sagayeco-farm-2024-secure-key-xyz789&sensor_type=temperature&value=25&unit=C"
echo.
echo.

echo ════════════════════════════════════════════════════════════
echo If you see JSON response above, the API is working!
echo If you see HTML or error, there's a problem with InfinityFree.
echo ════════════════════════════════════════════════════════════
echo.
pause

# Arduino Sensor Data with ngrok - Setup Guide

This guide shows you how to push Arduino sensor data to InfinityFree using ngrok, similar to how YOLO works.

## Architecture

```
Arduino → Python Bridge (port 5001) → ngrok Tunnel → InfinityFree
                                    ↓
                            Push Service (uploads data)
```

## How It Works (Same as YOLO)

1. **Arduino Bridge** runs locally on port 5001 (reads sensor data)
2. **ngrok** creates a public tunnel to port 5001
3. **Push Service** continuously uploads data to InfinityFree
4. **InfinityFree** can also pull data directly through the ngrok tunnel

## Setup Steps

### Step 1: Update config/env.php

Your ngrok URL is: `https://fredda-unprecisive-unashamedly.ngrok-free.dev`

Open `config/env.php` and verify these lines:

```php
'ARDUINO_SENSOR_HOST' => 'fredda-unprecisive-unashamedly.ngrok-free.dev',
'ARDUINO_SENSOR_PORT' => '443',
'ARDUINO_SENSOR_PROTOCOL' => 'https',
'UPLOAD_API_KEY' => 'sagayeco-farm-2024-secure-key-xyz789'
```

### Step 2: Upload Files to InfinityFree

Upload these files to your InfinityFree hosting:

1. `config/env.php` - Contains your ngrok URL
2. `api/upload_sensor.php` - Receives sensor data uploads
3. `api/get_sensor_data_ngrok.php` - Pulls data through ngrok (optional)

### Step 3: Start Services

Run the startup script:

```bash
start_arduino_with_ngrok.bat
```

This will start 3 services:
- **Arduino Bridge Service** (port 5001) - Reads from Arduino
- **ngrok Tunnel** - Creates public URL
- **Arduino Push Service** - Uploads to InfinityFree

### Step 4: Verify It's Working

1. Check the "Arduino Bridge Service" window - should show sensor readings
2. Check the "ngrok Tunnel" window - should show your public URL
3. Check the "Arduino Push Service" window - should show successful uploads

### Step 5: Test from InfinityFree

Visit your InfinityFree dashboard:
```
https://sagayecofarm.infinityfreeapp.com/dashboard.php
```

You should see live sensor data updating!

## Two Ways to Get Sensor Data

### Method 1: Push (Recommended)
- Arduino Push Service uploads data every X seconds
- Data is stored in InfinityFree database
- Works even if ngrok disconnects temporarily
- **This is what the script does automatically**

### Method 2: Pull (Real-time)
- InfinityFree fetches data directly through ngrok
- Real-time data, no delay
- Requires ngrok to be always running
- Use `api/get_sensor_data_ngrok.php` endpoint

## Comparison with YOLO

| Feature | YOLO | Arduino Sensors |
|---------|------|-----------------|
| Local Port | 5000 | 5001 |
| Service | Flask (yolo_detect2.py) | Flask (arduino_bridge.py) |
| ngrok Command | `ngrok http 5000` | `ngrok http 5001` |
| Data Flow | Pull (on-demand) | Push (continuous) |
| Config Key | YOLO_SERVICE_HOST | ARDUINO_SENSOR_HOST |

## Troubleshooting

### Arduino Bridge Not Starting
- Check if Arduino is connected to COM3
- Verify Python and Flask are installed
- Check the "Arduino Bridge Service" window for errors

### ngrok Not Working
- Make sure ngrok is installed: `winget install ngrok.ngrok`
- Configure authtoken: `ngrok config add-authtoken YOUR_TOKEN`
- Check if port 5001 is already in use

### Push Service Failing
- Verify API_KEY matches in both files
- Check InfinityFree URL is correct
- Make sure `api/upload_sensor.php` is uploaded

### No Data on InfinityFree
- Check if uploads are successful in "Arduino Push Service" window
- Verify database connection in InfinityFree
- Check `sensor_readings` table for new entries

## Manual Commands

If you want to run services separately:

```bash
# Terminal 1: Start Arduino Bridge
python arduino_bridge.py

# Terminal 2: Start ngrok
ngrok http 5001

# Terminal 3: Start Push Service
php arduino_push_service.php
```

## Stopping Services

Close all 3 windows:
- Arduino Bridge Service
- ngrok Tunnel - Arduino
- Arduino Push Service

Or use Task Manager to kill:
- python.exe (arduino_bridge.py)
- ngrok.exe
- php.exe (arduino_push_service.php)

## Next Steps

1. Monitor the dashboard to see live sensor data
2. Set up threshold alerts in the plant monitoring system
3. Adjust logging interval in settings
4. Keep services running 24/7 for continuous monitoring

## Notes

- ngrok free tier has session limits (2 hours)
- Consider ngrok paid plan for production use
- Arduino must stay connected to your PC
- PC must stay on for continuous monitoring

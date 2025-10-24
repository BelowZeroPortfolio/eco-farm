# 5-Second Interval System Setup Guide

This guide will help you set up and run the 5-second sensor data logging system.

## Prerequisites

1. **Arduino Bridge Service**: Make sure `arduino_bridge.py` is running
2. **Database**: Ensure your database is set up with the proper schema
3. **PHP**: Web server with PHP support

## Step-by-Step Setup

### 1. Setup Database and Sensors

```bash
# Setup the sensor records in database
php setup_sensors.php
```

This will create/update the Arduino sensor records with proper thresholds.

### 2. Test the Complete System

```bash
# Run the comprehensive system test
php test_5sec_system.php
```

This will:
- Check Arduino connection
- Set 5-second logging interval
- Test sensor data retrieval
- Test database logging (5 cycles)
- Verify data availability for display

### 3. Verify Data Display

```bash
# Check if sensors.php will have data to show
php verify_sensors_display.php
```

This shows what data is available for the sensors.php page.

### 4. Start Background Sync (Choose One)

#### Option A: Simple 5-Second Sync (Recommended for Testing)
```bash
# Run the simplified 5-second sync
php sync_5sec.php
```

#### Option B: Full Background Sync
```bash
# Run the complete background sync service
php arduino_background_sync.php
```

### 5. Configure 5-Second Interval via Web Interface

1. Open your web browser
2. Go to `settings.php`
3. Click on "Sensor Settings" tab
4. Select "5 Seconds" option
5. Click "Save Interval Setting"

## Verification

### Check if System is Working

1. **Real-time Data**: Visit `arduino_sync.php?action=get_all` to see live sensor data
2. **Database Data**: Visit `sensors.php` to see logged data in the table
3. **Recent Readings**: Run `php verify_sensors_display.php` to check database

### Expected Results

- **Arduino Data**: Should show current temperature, humidity, and soil moisture
- **Database Logging**: New records every 5 seconds in `sensor_readings` table
- **Web Display**: `sensors.php` should show recent readings with timestamps

## Troubleshooting

### No Data in sensors.php
```bash
# Check if sensors are set up
php setup_sensors.php

# Test the system
php test_5sec_system.php

# Verify display data
php verify_sensors_display.php
```

### Arduino Connection Issues
1. Make sure `arduino_bridge.py` is running on port 5000
2. Check if Arduino is connected and responding
3. Test with: `curl http://localhost:5000/health`

### Database Issues
1. Check database connection in `config/database.php`
2. Ensure `sensor_readings` table exists
3. Check for recent records: `SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT 10`

### Interval Not Working
1. Check current interval: Visit `arduino_sync.php?action=get_interval_info`
2. Reset interval: Use settings.php web interface
3. Verify with: `php test_5sec_system.php`

## File Descriptions

- `setup_sensors.php` - Creates/updates Arduino sensor records
- `test_5sec_system.php` - Comprehensive system test
- `verify_sensors_display.php` - Checks data availability for display
- `sync_5sec.php` - Simple 5-second background sync
- `arduino_background_sync.php` - Full background sync service
- `settings.php` - Web interface for interval configuration
- `sensors.php` - Data display page

## Quick Start Commands

```bash
# Complete setup and test
php setup_sensors.php
php test_5sec_system.php

# Start background sync
php sync_5sec.php &

# Check results
php verify_sensors_display.php
```

Then visit `sensors.php` in your web browser to see the data table.

## Notes

- The 5-second interval is for testing purposes
- For production, use longer intervals (5+ minutes)
- The system respects the interval setting - it won't log more frequently than configured
- Real-time display (dashboard) always shows current Arduino data
- Historical data (sensors.php) shows database-logged data
# Real-time Sensor System Documentation

## Overview

The IoT Farm Monitoring System uses a dual-layer approach for sensor data:

1. **Real-time Display**: Shows live sensor readings from Arduino (updates every 3-5 seconds)
2. **Database Logging**: Stores sensor readings based on configurable intervals (5 seconds to 24 hours)

## System Architecture

```
Arduino Hardware → Python Bridge → PHP Web Interface → Database
                                      ↓
                              Real-time Display (sensors.php)
                                      ↓
                              Interval-based Logging
```

## Key Components

### 1. Arduino Bridge Service (`arduino_bridge.py`)
- Continuously reads sensors every 3 seconds
- Provides HTTP API for real-time data access
- Must be running for real-time functionality

### 2. Arduino API (`includes/arduino-api.php`)
- PHP interface to Arduino bridge
- Handles database logging with interval checking
- Manages logging interval settings

### 3. Arduino Sync (`arduino_sync.php`)
- AJAX endpoint for real-time data
- Provides sensor status and threshold checking
- Handles manual sync requests

### 4. Settings Page (`settings.php`)
- Configure logging intervals (5 seconds to 24 hours)
- Set sensor thresholds for status calculation
- Manage notification preferences

### 5. Sensors Page (`sensors.php`)
- Displays real-time sensor data
- Shows historical charts from database
- Updates live values every 5 seconds

## Logging Intervals

Available intervals in settings.php:

- **5 seconds** (0.0833 minutes) - Testing only, high database load
- **5 minutes** - High frequency monitoring
- **15 minutes** - Frequent monitoring
- **30 minutes** - Recommended default
- **1 hour** - Standard monitoring
- **2 hours** - Reduced frequency
- **4 hours** - Low frequency

## Real-time vs Database Data

### Real-time Data (Live Display)
- **Source**: Direct from Arduino via HTTP API
- **Update frequency**: Every 3-5 seconds
- **Usage**: Live dashboard, current status
- **Availability**: Only when Arduino bridge is running

### Database Data (Historical)
- **Source**: Stored sensor readings
- **Update frequency**: Based on logging interval setting
- **Usage**: Charts, reports, historical analysis
- **Availability**: Always available once logged

## Status Calculation

Sensor status is calculated based on thresholds:

### Temperature
- **Optimal**: 20-28°C (configurable)
- **Warning**: Outside optimal but not critical
- **Critical**: Below 14°C or above 34°C

### Humidity
- **Optimal**: 60-80% (configurable)
- **Warning**: Outside optimal but not critical
- **Critical**: Below 50% or above 90%

### Soil Moisture
- **Optimal**: 40-60% (configurable)
- **Warning**: Outside optimal but not critical
- **Critical**: Below 30% or above 70%

## Setup Instructions

### 1. Start Arduino Bridge Service
```bash
python arduino_bridge.py
```

### 2. Initialize Sample Data
```bash
php setup_sensor_data.php
```

### 3. Test System
```bash
php test_realtime_sync.php
```

### 4. Test 5-Second Logging
```bash
php test_5sec_logging.php
```

### 5. Optional: Background Sync Service
```bash
php arduino_background_sync.php
```

## Configuration Files

### Database Settings
- `config/database.php` - Database connection
- `database/schema.sql` - Database structure
- `database/insert_sample_sensor_data.sql` - Sample data

### Sensor Settings
- Logging interval stored in `user_settings` table
- Thresholds stored in `sensors` table
- Configurable via settings.php interface

## API Endpoints

### Real-time Data
- `arduino_sync.php?action=get_all` - All sensor data
- `arduino_sync.php?action=get_temperature` - Temperature only
- `arduino_sync.php?action=get_humidity` - Humidity only
- `arduino_sync.php?action=get_soil` - Soil moisture only

### System Info
- `arduino_sync.php?action=get_interval_info` - Current logging interval
- `arduino_sync.php?action=sync_to_db` - Force database sync

## Troubleshooting

### No Real-time Data
1. Check if `arduino_bridge.py` is running
2. Verify Arduino hardware connections
3. Check network connectivity to bridge service

### No Database Logging
1. Verify database connection in `config/database.php`
2. Check logging interval settings in settings.php
3. Ensure sensors exist in database

### Interval Not Working
1. Check `user_settings` table for `sensor_logging_interval`
2. Verify interval calculation in `arduino-api.php`
3. Test with shorter interval (5 seconds) for debugging

## Performance Considerations

### High-Frequency Logging (5 seconds)
- **Pros**: Detailed data, good for testing
- **Cons**: Large database, high I/O load
- **Recommendation**: Use only for testing or critical monitoring

### Standard Logging (30 minutes)
- **Pros**: Balanced data collection, manageable database size
- **Cons**: Less granular data
- **Recommendation**: Default for most applications

### Low-Frequency Logging (4+ hours)
- **Pros**: Minimal database load, long-term trends
- **Cons**: May miss important events
- **Recommendation**: Use for stable environments

## File Structure

```
/
├── arduino_sync.php              # AJAX endpoint for real-time data
├── arduino_background_sync.php   # Background sync service
├── sensors.php                   # Main sensor display page
├── settings.php                  # System configuration
├── setup_sensor_data.php         # Database initialization
├── test_realtime_sync.php        # System testing
├── test_5sec_logging.php         # Interval testing
├── config/
│   └── database.php              # Database configuration
├── includes/
│   └── arduino-api.php           # Arduino bridge interface
└── database/
    ├── schema.sql                # Database structure
    └── insert_sample_sensor_data.sql # Sample data
```

## Best Practices

1. **Always keep Arduino bridge running** for real-time functionality
2. **Use appropriate logging intervals** based on your needs
3. **Monitor database size** with high-frequency logging
4. **Set realistic thresholds** based on your crop requirements
5. **Test thoroughly** before production deployment

## Support

For issues or questions:
1. Check the troubleshooting section
2. Run test scripts to diagnose problems
3. Check log files for error messages
4. Verify hardware connections and services
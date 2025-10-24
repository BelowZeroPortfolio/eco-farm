# 5-Second Interval Testing Guide

## Overview
The IoT Farm Monitoring System now supports a **5-second logging interval** for testing purposes. This allows rapid data collection to quickly verify system functionality.

## ⚠️ Important Warning
**The 5-second interval is for TESTING ONLY!**
- High database load
- Rapid storage consumption
- Not suitable for production use
- Use only for short-term testing

## How to Enable 5-Second Interval

### Method 1: Through Settings Page (Recommended)
1. Login as **admin**
2. Go to **Settings** page
3. Click on **Sensor Settings** tab
4. Select **"5 Seconds"** option (marked with ⚠️ Testing only)
5. Click **"Save Interval Setting"**

### Method 2: Direct API Call
```php
require_once 'includes/arduino-api.php';
$arduino = new ArduinoBridge();
$result = $arduino->setLoggingInterval(0.0833, $adminUserId);
```

### Method 3: Run Test Script
```bash
php test_5sec_interval.php
```

## How It Works

### Technical Details
- **Interval Value**: 0.0833 minutes = 5 seconds
- **Database Field**: `user_settings.setting_value` where `setting_key = 'sensor_logging_interval'`
- **Validation**: System ensures interval is between 0.0833 minutes (5 seconds) and 1440 minutes (24 hours)

### Data Flow
1. Arduino reads sensors every 3 seconds (hardware level)
2. Python bridge serves real-time data via HTTP API
3. PHP checks if logging interval has passed before storing to database
4. Dashboard always shows real-time data regardless of logging interval
5. Historical charts use database-logged data

## Testing Procedure

### Quick Test (5 minutes)
1. Set interval to 5 seconds
2. Monitor dashboard for 1-2 minutes
3. Check database for new entries every 5 seconds
4. Verify charts update with new data
5. Reset to normal interval (15-30 minutes)

### Extended Test (30 minutes)
1. Set interval to 5 seconds
2. Run continuous monitoring
3. Check database growth rate
4. Verify system performance
5. Test chart rendering with high data density
6. Reset to normal interval

## Verification Commands

### Check Current Setting
```php
$arduino = new ArduinoBridge();
$setting = $arduino->getLoggingIntervalSetting();
echo $setting['formatted']; // Should show "5 seconds"
```

### Monitor Database Growth
```sql
SELECT COUNT(*) as total_readings, 
       MAX(recorded_at) as latest_reading,
       MIN(recorded_at) as earliest_reading
FROM sensor_readings 
WHERE recorded_at >= NOW() - INTERVAL 10 MINUTE;
```

### Check Recent Entries
```sql
SELECT s.sensor_type, sr.value, sr.recorded_at
FROM sensor_readings sr
JOIN sensors s ON sr.sensor_id = s.id
ORDER BY sr.recorded_at DESC
LIMIT 20;
```

## Expected Results

### With 5-Second Interval
- New database entry every 5 seconds (when Arduino is connected)
- Rapid chart updates
- High data density in historical views
- Increased database size

### Performance Impact
- **Database**: ~720 entries per hour per sensor type
- **Storage**: ~17,280 entries per day per sensor type
- **Charts**: Dense data points, may need aggregation for display

## Troubleshooting

### No Data Being Logged
1. Check Arduino bridge service: `http://127.0.0.1:5000/health`
2. Verify interval setting in database
3. Check sensor status in sensors table
4. Review error logs

### Too Much Data
1. Immediately change interval to higher value (15+ minutes)
2. Consider data cleanup for test period
3. Monitor database performance

### System Performance Issues
1. Reset interval to 30 minutes immediately
2. Clear browser cache
3. Restart Arduino bridge service
4. Check database server resources

## Cleanup After Testing

### Reset Interval
```php
$arduino->setLoggingInterval(30, $adminUserId); // Back to 30 minutes
```

### Optional: Clean Test Data
```sql
-- Remove test data from last hour (BE CAREFUL!)
DELETE FROM sensor_readings 
WHERE recorded_at >= NOW() - INTERVAL 1 HOUR;
```

## Production Recommendations

### Recommended Intervals
- **Development**: 15 minutes
- **Production**: 30 minutes  
- **Low-activity periods**: 1-2 hours
- **Testing only**: 5 seconds

### Best Practices
1. Always reset to production interval after testing
2. Monitor database size regularly
3. Set up automated cleanup for old data
4. Use real-time dashboard for immediate monitoring
5. Use historical data for trend analysis

## Support

If you encounter issues with the 5-second interval:
1. Check the test script output: `php test_5sec_interval.php`
2. Verify Arduino bridge is running
3. Check database connectivity
4. Review system logs for errors
5. Reset to safe interval (30 minutes) if problems persist

---
**Remember**: The 5-second interval is a powerful testing tool but should be used responsibly to avoid system overload.
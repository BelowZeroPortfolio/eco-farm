-- ============================================================================
-- Insert Sample Sensor Data
-- ============================================================================
-- Run this file AFTER running schema.sql to add sample sensor readings
-- This will populate the database with test data to demonstrate the
-- sensor remarks system with different threshold states
-- ============================================================================

USE farm_database;

-- ============================================================================
-- INSERT SAMPLE SENSORS
-- ============================================================================
-- Note: DHT22 provides both temperature and humidity on same pin
-- Using different pin numbers to avoid unique constraint conflict

-- Check if sensors already exist and delete them first
DELETE FROM sensor_readings WHERE sensor_id IN (SELECT id FROM sensors WHERE sensor_name LIKE 'Arduino%');
DELETE FROM sensors WHERE sensor_name LIKE 'Arduino%';

INSERT INTO sensors (sensor_name, sensor_type, location, arduino_pin, alert_threshold_min, alert_threshold_max, status, last_reading_at) VALUES
('Arduino Temperature DHT22', 'temperature', 'Farm Field', 2, 20.0, 28.0, 'online', NOW()),
('Arduino Humidity DHT22', 'humidity', 'Farm Field', 3, 60.0, 80.0, 'online', NOW()),
('Arduino Soil Moisture', 'soil_moisture', 'Farm Field', 10, 40.0, 60.0, 'online', NOW());

-- ============================================================================
-- INSERT SAMPLE SENSOR READINGS
-- ============================================================================
-- These readings demonstrate different threshold states:
-- Optimal, Warning (High/Low), and Critical states

-- Get sensor IDs dynamically
SET @temp_sensor_id = (SELECT id FROM sensors WHERE sensor_type = 'temperature' AND sensor_name LIKE 'Arduino%' LIMIT 1);
SET @hum_sensor_id = (SELECT id FROM sensors WHERE sensor_type = 'humidity' AND sensor_name LIKE 'Arduino%' LIMIT 1);
SET @soil_sensor_id = (SELECT id FROM sensors WHERE sensor_type = 'soil_moisture' AND sensor_name LIKE 'Arduino%' LIMIT 1);

-- Temperature readings (Optimal: 20-28°C)
INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) VALUES
-- Optimal readings (24-26°C)
(@temp_sensor_id, 24.5, '°C', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(@temp_sensor_id, 25.2, '°C', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(@temp_sensor_id, 24.8, '°C', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
-- Warning High (32°C)
(@temp_sensor_id, 32.0, '°C', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
-- Warning Low (18°C)
(@temp_sensor_id, 18.0, '°C', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
-- Critical High (38°C) - Will trigger CRITICAL warning
(@temp_sensor_id, 38.0, '°C', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
-- Back to Optimal (current reading)
(@temp_sensor_id, 25.5, '°C', NOW());

-- Humidity readings (Optimal: 60-80%)
INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) VALUES
-- Optimal readings (65-75%)
(@hum_sensor_id, 68.2, '%', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(@hum_sensor_id, 72.5, '%', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(@hum_sensor_id, 70.0, '%', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
-- Warning High (85%)
(@hum_sensor_id, 85.0, '%', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
-- Warning Low (55%)
(@hum_sensor_id, 55.0, '%', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
-- Critical High (92%) - Will trigger CRITICAL warning
(@hum_sensor_id, 92.0, '%', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
-- Back to Optimal (current reading)
(@hum_sensor_id, 70.5, '%', NOW());

-- Soil Moisture readings (Optimal: 40-60%)
INSERT INTO sensor_readings (sensor_id, value, unit, recorded_at) VALUES
-- Optimal readings (45-55%)
(@soil_sensor_id, 48.5, '%', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(@soil_sensor_id, 52.0, '%', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(@soil_sensor_id, 50.5, '%', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
-- Warning High (65%)
(@soil_sensor_id, 65.0, '%', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
-- Warning Low (35%)
(@soil_sensor_id, 35.0, '%', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
-- Critical Low (25%) - Will trigger CRITICAL warning
(@soil_sensor_id, 25.0, '%', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
-- Back to Optimal (current reading)
(@soil_sensor_id, 50.0, '%', NOW());

-- ============================================================================
-- INSERT SENSOR SETTINGS
-- ============================================================================

-- Set default logging interval to 30 minutes
-- Check if setting already exists first
INSERT INTO user_settings (user_id, setting_key, setting_value) 
VALUES (1, 'sensor_logging_interval', '30')
ON DUPLICATE KEY UPDATE setting_value = '30';

-- ============================================================================
-- VERIFY INSERTED DATA
-- ============================================================================

SELECT '✅ Sample sensors inserted:' as Info;
SELECT * FROM sensors;

SELECT '✅ Latest sensor readings:' as Info;
SELECT 
    s.sensor_name,
    s.sensor_type,
    sr.value,
    sr.unit,
    CASE 
        WHEN s.sensor_type = 'temperature' THEN
            CASE 
                WHEN sr.value >= 20 AND sr.value <= 28 THEN '✅ OPTIMAL'
                WHEN sr.value > 34 OR sr.value < 14 THEN '🚨 CRITICAL'
                ELSE '⚠️ WARNING'
            END
        WHEN s.sensor_type = 'humidity' THEN
            CASE 
                WHEN sr.value >= 60 AND sr.value <= 80 THEN '✅ OPTIMAL'
                WHEN sr.value > 90 OR sr.value < 50 THEN '🚨 CRITICAL'
                ELSE '⚠️ WARNING'
            END
        WHEN s.sensor_type = 'soil_moisture' THEN
            CASE 
                WHEN sr.value >= 40 AND sr.value <= 60 THEN '✅ OPTIMAL'
                WHEN sr.value > 70 OR sr.value < 30 THEN '🚨 CRITICAL'
                ELSE '⚠️ WARNING'
            END
    END as Status,
    sr.recorded_at
FROM sensors s
JOIN sensor_readings sr ON s.id = sr.sensor_id
WHERE sr.recorded_at = (
    SELECT MAX(recorded_at) 
    FROM sensor_readings 
    WHERE sensor_id = s.id
)
ORDER BY s.sensor_type;

SELECT '✅ Total sensor readings inserted:' as Info;
SELECT 
    s.sensor_type,
    COUNT(*) as reading_count
FROM sensors s
JOIN sensor_readings sr ON s.id = sr.sensor_id
GROUP BY s.sensor_type;

-- ============================================================================
-- EXPECTED RESULTS ON DASHBOARD
-- ============================================================================
-- 
-- After running this script, the dashboard will show:
--
-- 🟢 Temperature: 25.5°C - OPTIMAL
--    "Temperature is optimal at 25.5°C. Perfect for crop growth."
--
-- 🟢 Humidity: 70.5% - OPTIMAL
--    "Humidity is optimal at 70.5%. Good conditions for plant health."
--
-- 🟢 Soil Moisture: 50.0% - OPTIMAL
--    "Soil moisture is optimal at 50.0%. Good water availability for roots."
--
-- To test different states, use the test_sensor_states.sql file
-- or manually update the latest readings:
--
-- Example - Test Critical High Temperature:
-- UPDATE sensor_readings 
-- SET value = 38.0, recorded_at = NOW() 
-- WHERE sensor_id = 1 
-- ORDER BY recorded_at DESC LIMIT 1;
--
-- ============================================================================

SELECT '✅ Sample sensor data inserted successfully!' as Result;
SELECT 'Refresh the dashboard to see sensor remarks in action!' as NextStep;

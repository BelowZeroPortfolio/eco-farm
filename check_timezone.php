<?php
/**
 * Timezone Diagnostic Script
 * Run this to check timezone settings across PHP, MySQL, and system
 * 
 * USAGE: php check_timezone.php
 * Or access via browser: http://localhost/eco-farm/check_timezone.php
 */

header('Content-Type: text/plain');

echo "=" . str_repeat("=", 60) . "\n";
echo "TIMEZONE DIAGNOSTIC REPORT\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// 1. PHP Default Timezone
echo "1. PHP TIMEZONE SETTINGS\n";
echo "-" . str_repeat("-", 40) . "\n";
echo "   Default timezone: " . date_default_timezone_get() . "\n";

// Set to Philippines
date_default_timezone_set('Asia/Manila');
echo "   After setting to Asia/Manila: " . date_default_timezone_get() . "\n";
echo "   PHP date(): " . date('Y-m-d H:i:s') . "\n";
echo "   PHP time(): " . time() . "\n";
echo "\n";

// 2. System Time
echo "2. SYSTEM TIME\n";
echo "-" . str_repeat("-", 40) . "\n";
if (PHP_OS_FAMILY === 'Windows') {
    echo "   Windows system time:\n";
    $output = shell_exec('echo %DATE% %TIME%');
    echo "   " . trim($output) . "\n";
    
    // Get Windows timezone
    $tzOutput = shell_exec('tzutil /g');
    echo "   Windows timezone: " . trim($tzOutput) . "\n";
} else {
    echo "   System date: " . shell_exec('date') . "\n";
}
echo "\n";

// 3. MySQL Timezone
echo "3. MYSQL TIMEZONE SETTINGS\n";
echo "-" . str_repeat("-", 40) . "\n";

try {
    require_once 'config/database.php';
    $pdo = getDatabaseConnection();
    
    // Get MySQL timezone variables
    $stmt = $pdo->query("SELECT @@global.time_zone as global_tz, @@session.time_zone as session_tz, NOW() as mysql_now");
    $result = $stmt->fetch();
    
    echo "   Global timezone: " . $result['global_tz'] . "\n";
    echo "   Session timezone: " . $result['session_tz'] . "\n";
    echo "   MySQL NOW(): " . $result['mysql_now'] . "\n";
    
    // Set session timezone and check again
    $pdo->exec("SET time_zone = '+08:00'");
    $stmt = $pdo->query("SELECT @@session.time_zone as session_tz, NOW() as mysql_now");
    $result = $stmt->fetch();
    echo "   After SET time_zone = '+08:00':\n";
    echo "   Session timezone: " . $result['session_tz'] . "\n";
    echo "   MySQL NOW(): " . $result['mysql_now'] . "\n";
    
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Last Sensor Reading
echo "4. LAST SENSOR READING IN DATABASE\n";
echo "-" . str_repeat("-", 40) . "\n";

try {
    $stmt = $pdo->query("
        SELECT 
            ReadingID,
            ReadingTime,
            TIMESTAMPDIFF(SECOND, ReadingTime, NOW()) as seconds_ago,
            TIMESTAMPDIFF(HOUR, ReadingTime, NOW()) as hours_ago
        FROM sensorreadings 
        ORDER BY ReadingTime DESC 
        LIMIT 1
    ");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "   Last ReadingID: " . $result['ReadingID'] . "\n";
        echo "   Last ReadingTime: " . $result['ReadingTime'] . "\n";
        echo "   Seconds ago (MySQL): " . $result['seconds_ago'] . "\n";
        echo "   Hours ago (MySQL): " . $result['hours_ago'] . "\n";
        
        // PHP calculation
        $lastReadingTime = strtotime($result['ReadingTime']);
        $phpSecondsAgo = time() - $lastReadingTime;
        echo "   Seconds ago (PHP strtotime): " . $phpSecondsAgo . "\n";
        echo "   Hours ago (PHP): " . round($phpSecondsAgo / 3600, 2) . "\n";
        
        // Check for mismatch
        $diff = abs($result['seconds_ago'] - $phpSecondsAgo);
        if ($diff > 60) {
            echo "\n   ⚠️  WARNING: " . round($diff / 3600, 1) . " hour difference between MySQL and PHP!\n";
            echo "   This indicates a TIMEZONE MISMATCH!\n";
        } else {
            echo "\n   ✅ MySQL and PHP times are in sync.\n";
        }
    } else {
        echo "   No sensor readings found in database.\n";
    }
    
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Logging Interval
echo "5. LOGGING INTERVAL SETTING\n";
echo "-" . str_repeat("-", 40) . "\n";

try {
    $stmt = $pdo->query("SELECT setting_value FROM user_settings WHERE setting_key = 'sensor_logging_interval' LIMIT 1");
    $result = $stmt->fetch();
    
    if ($result) {
        $minutes = floatval($result['setting_value']);
        $seconds = $minutes * 60;
        echo "   Interval: " . $minutes . " minutes (" . $seconds . " seconds)\n";
    } else {
        echo "   Interval not set (default: 30 minutes)\n";
    }
    
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Recommendations
echo "6. RECOMMENDATIONS\n";
echo "-" . str_repeat("-", 40) . "\n";
echo "   If you see a timezone mismatch:\n";
echo "   1. Set Windows timezone to 'Singapore Standard Time' (UTC+8)\n";
echo "   2. Or keep using the updated arduino_bridge.py which forces UTC+8\n";
echo "   3. Restart the Arduino bridge service after changes\n";
echo "\n";

echo "=" . str_repeat("=", 60) . "\n";
echo "END OF REPORT\n";
echo "=" . str_repeat("=", 60) . "\n";

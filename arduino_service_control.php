<?php
/**
 * Arduino Bridge Service Control
 * Handles starting and stopping the Arduino bridge service
 */

header('Content-Type: application/json');

// Simple authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

function isServiceRunning()
{
    // Check if the Arduino bridge service is running on port 5000
    $connection = @fsockopen('127.0.0.1', 5000, $errno, $errstr, 1);
    if ($connection) {
        fclose($connection);
        return true;
    }
    return false;
}

function startService()
{
    // Start the Arduino bridge service in background
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - Run .py file directly in hidden/minimized window
        // Get the current directory where the PHP script is located
        $currentDir = __DIR__;
        // /MIN = minimized window
        $command = 'start "Arduino Bridge Service" /MIN /D "' . $currentDir . '" arduino_bridge.py';
        pclose(popen($command, 'r'));
    } else {
        // Linux/Mac
        $command = 'python3 arduino_bridge.py > /dev/null 2>&1 &';
        exec($command);
    }

    // Return success immediately without waiting
    return ['success' => true, 'message' => 'Arduino bridge started'];
}

function stopService()
{
    // Stop the service
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - kill Python processes running arduino_bridge.py
        exec('taskkill /F /FI "WINDOWTITLE eq Arduino Bridge*" 2>nul');
        exec('wmic process where "name=\'python.exe\' and commandline like \'%arduino_bridge.py%\'" delete 2>nul');
    } else {
        // Linux/Mac
        exec('pkill -f arduino_bridge.py');
    }

    // Return success immediately without waiting
    return ['success' => true, 'message' => 'Arduino bridge stopped'];
}

function getServiceStatus()
{
    $isRunning = isServiceRunning();
    $status = $isRunning ? 'running' : 'stopped';

    // Try to get additional info if running
    $info = [];
    if ($isRunning) {
        try {
            $healthCheck = @file_get_contents('http://127.0.0.1:5001/health');
            if ($healthCheck) {
                $healthData = json_decode($healthCheck, true);
                $info = [
                    'arduino_connected' => $healthData['arduino_connected'] ?? false,
                    'port' => $healthData['port'] ?? 'Unknown',
                    'version' => $healthData['version'] ?? '1.0'
                ];
            }
        } catch (Exception $e) {
            // Service running but not responding properly
        }
    }

    return [
        'success' => true,
        'running' => $isRunning,
        'status' => $status,
        'info' => $info,
        'message' => $isRunning ? 'Arduino bridge is running' : 'Arduino bridge is stopped'
    ];
}

// Handle actions
switch ($action) {
    case 'start':
        $result = startService();
        break;
    case 'stop':
        $result = stopService();
        break;
    case 'status':
        $result = getServiceStatus();
        break;
    default:
        $result = ['success' => false, 'message' => 'Invalid action'];
}

echo json_encode($result);

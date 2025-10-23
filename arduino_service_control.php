<?php

/**
 * Arduino Service Control
 * Handles starting and stopping the Python Arduino bridge service
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
    // Check if the Python service is running on port 5000
    $connection = @fsockopen('127.0.0.1', 5000, $errno, $errstr, 1);
    if ($connection) {
        fclose($connection);
        return true;
    }
    return false;
}

function startService()
{
    if (isServiceRunning()) {
        return ['success' => true, 'message' => 'Service is already running'];
    }

    // Start the Python service in background
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $command = 'start /B python arduino_bridge.py > nul 2>&1';
        pclose(popen($command, 'r'));
    } else {
        // Linux/Mac
        $command = 'python3 arduino_bridge.py > /dev/null 2>&1 &';
        exec($command);
    }

    // Wait a moment and check if it started
    sleep(2);

    if (isServiceRunning()) {
        return ['success' => true, 'message' => 'Arduino bridge service started successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to start service. Check if Python and required packages are installed.'];
    }
}

function stopService()
{
    if (!isServiceRunning()) {
        return ['success' => true, 'message' => 'Service is already stopped'];
    }

    // Try to stop the service gracefully
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - kill Python processes running arduino_bridge.py
        exec('taskkill /F /IM python.exe /FI "WINDOWTITLE eq arduino_bridge*" 2>nul');
        exec('wmic process where "name=\'python.exe\' and commandline like \'%arduino_bridge.py%\'" delete 2>nul');
    } else {
        // Linux/Mac
        exec('pkill -f arduino_bridge.py');
    }

    // Wait a moment and check if it stopped
    sleep(1);

    if (!isServiceRunning()) {
        return ['success' => true, 'message' => 'Arduino bridge service stopped successfully'];
    } else {
        return ['success' => false, 'message' => 'Service may still be running. Try again or restart manually.'];
    }
}

function getServiceStatus()
{
    $isRunning = isServiceRunning();
    $status = $isRunning ? 'running' : 'stopped';

    // Try to get additional info if running
    $info = [];
    if ($isRunning) {
        try {
            $healthCheck = @file_get_contents('http://127.0.0.1:5000/health');
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
        'status' => $status,
        'running' => $isRunning,
        'info' => $info
    ];
}

// Handle the requested action
switch ($action) {
    case 'start':
        echo json_encode(startService());
        break;

    case 'stop':
        echo json_encode(stopService());
        break;

    case 'status':
        echo json_encode(getServiceStatus());
        break;

    case 'restart':
        $stopResult = stopService();
        if ($stopResult['success']) {
            sleep(1);
            echo json_encode(startService());
        } else {
            echo json_encode($stopResult);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

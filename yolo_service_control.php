<?php

/**
 * YOLO Service Control
 * Handles starting and stopping the Flask YOLO detection service
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
    // Check if the YOLO Flask service is running on port 5000
    $connection = @fsockopen('127.0.0.1', 5000, $errno, $errstr, 1);
    if ($connection) {
        fclose($connection);
        return true;
    }
    return false;
}

function startService()
{
    // Double-check if service is running
    if (isServiceRunning()) {
        return ['success' => true, 'message' => 'YOLO service is already running'];
    }

    // Check if process is already starting (look for YOLO Service window)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $output = [];
        exec('tasklist /V /FI "WINDOWTITLE eq YOLO Service*" /FO CSV /NH 2>nul', $output);
        if (!empty($output) && count($output) > 0) {
            // YOLO Service window exists, wait for it to start
            sleep(3);
            if (isServiceRunning()) {
                return ['success' => true, 'message' => 'YOLO service is already starting/running'];
            }
        }
    }

    // Start the YOLO service in background
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - Start directly with Python (not via batch file to avoid duplicate checks)
        $command = 'start "YOLO Service - Flask" python yolo_detect2.py';
        pclose(popen($command, 'r'));
    } else {
        // Linux/Mac
        $command = 'python3 yolo_detect2.py > /dev/null 2>&1 &';
        exec($command);
    }

    // Wait a moment and check if it started
    sleep(3);

    if (isServiceRunning()) {
        return ['success' => true, 'message' => 'YOLO detection service started successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to start service. Check if Python, Flask, and ultralytics are installed.'];
    }
}

function stopService()
{
    if (!isServiceRunning()) {
        return ['success' => true, 'message' => 'YOLO service is already stopped'];
    }

    // Try to stop the service gracefully
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - kill Python processes running yolo_detect2.py
        exec('taskkill /F /FI "WINDOWTITLE eq YOLO Service*" 2>nul');
        exec('wmic process where "name=\'python.exe\' and commandline like \'%yolo_detect2.py%\'" delete 2>nul');
    } else {
        // Linux/Mac
        exec('pkill -f yolo_detect2.py');
    }

    // Wait a moment and check if it stopped
    sleep(2);

    if (!isServiceRunning()) {
        return ['success' => true, 'message' => 'YOLO detection service stopped successfully'];
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
                    'model_loaded' => $healthData['model_loaded'] ?? false,
                    'model_path' => $healthData['model_path'] ?? 'Unknown',
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

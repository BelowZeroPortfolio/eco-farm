<?php
// Start session and authentication check
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/security.php';

// Check page access permission
requirePageAccess('pest_detection');

$pageTitle = 'YOLO Service Manager - IoT Farm Monitoring System';
include 'includes/header.php';
?>

<?php include 'includes/navigation.php'; ?>

<div class="p-4 max-w-4xl mx-auto">
    <!-- Service Manager Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-cogs text-blue-600 mr-3"></i>
                YOLO Detection Service Manager
            </h2>
        </div>

        <div class="p-6">
            <!-- Service Status -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Service Status</h3>
                    <button onclick="checkStatus()" class="px-3 py-2 text-sm bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>

                <div id="status-display" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3 text-gray-600 dark:text-gray-400">Checking service status...</span>
                    </div>
                </div>
            </div>

            <!-- Control Buttons -->
            <div class="flex gap-3 mb-6">
                <button id="start-btn" onclick="startService()" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-play mr-2"></i>Start Service
                </button>
                <button id="stop-btn" onclick="stopService()" class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-stop mr-2"></i>Stop Service
                </button>
                <button id="restart-btn" onclick="restartService()" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-redo mr-2"></i>Restart Service
                </button>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h4 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>About YOLO Service
                </h4>
                <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
                    <li>• The YOLO service must be running for pest detection to work</li>
                    <li>• Service runs on http://127.0.0.1:5000</li>
                    <li>• Requires Python, Flask, and ultralytics packages</li>
                    <li>• Model file (best.pt) must be present in the project directory</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let statusCheckInterval = null;

// Check service status
async function checkStatus() {
    try {
        const response = await fetch('yolo_service_control.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=status'
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateStatusDisplay(data);
            updateButtons(data.running);
        } else {
            showError('Failed to check status: ' + data.message);
        }
    } catch (error) {
        showError('Error checking status: ' + error.message);
    }
}

// Update status display
function updateStatusDisplay(data) {
    const statusHtml = `
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-gray-700 dark:text-gray-300 font-medium">Status:</span>
                <span class="px-3 py-1 rounded-full text-sm font-medium ${
                    data.running 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                }">
                    <i class="fas fa-circle text-xs mr-1"></i>
                    ${data.running ? 'RUNNING' : 'STOPPED'}
                </span>
            </div>
            ${data.running && data.info ? `
                <div class="pt-3 border-t border-gray-200 dark:border-gray-600 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Model Loaded:</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            ${data.info.model_loaded ? '✓ Yes' : '✗ No'}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Model Path:</span>
                        <span class="text-gray-900 dark:text-white font-medium">${data.info.model_path || 'N/A'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Version:</span>
                        <span class="text-gray-900 dark:text-white font-medium">${data.info.version || 'N/A'}</span>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('status-display').innerHTML = statusHtml;
}

// Update button states
function updateButtons(isRunning) {
    document.getElementById('start-btn').disabled = isRunning;
    document.getElementById('stop-btn').disabled = !isRunning;
    document.getElementById('restart-btn').disabled = !isRunning;
}

// Start service
async function startService() {
    showLoading('Starting service...');
    
    try {
        const response = await fetch('yolo_service_control.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=start'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(data.message);
            setTimeout(checkStatus, 1000);
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Error starting service: ' + error.message);
    }
}

// Stop service
async function stopService() {
    showLoading('Stopping service...');
    
    try {
        const response = await fetch('yolo_service_control.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=stop'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(data.message);
            setTimeout(checkStatus, 1000);
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Error stopping service: ' + error.message);
    }
}

// Restart service
async function restartService() {
    showLoading('Restarting service...');
    
    try {
        const response = await fetch('yolo_service_control.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=restart'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(data.message);
            setTimeout(checkStatus, 1000);
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Error restarting service: ' + error.message);
    }
}

// Helper functions
function showLoading(message) {
    document.getElementById('status-display').innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600 dark:text-gray-400">${message}</span>
        </div>
    `;
}

function showSuccess(message) {
    alert('✓ ' + message);
}

function showError(message) {
    alert('✗ ' + message);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    checkStatus();
    // Auto-refresh status every 10 seconds
    statusCheckInterval = setInterval(checkStatus, 10000);
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
    }
});
</script>

<?php include 'includes/footer.php'; ?>

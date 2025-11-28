/**
 * YOLO Service Auto-Start Helper
 * Automatically starts the YOLO service when pest detection is accessed
 */

class YOLOServiceManager {
    constructor() {
        this.serviceUrl = 'yolo_service_control.php';
        this.checkInterval = null;
        this.maxRetries = 3;
        this.retryCount = 0;
    }

    /**
     * Check if service is running
     */
    async isRunning() {
        try {
            const response = await fetch(this.serviceUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=status'
            });

            // If 500 error, likely ngrok setup - mark as tunnel mode
            if (response.status === 500) {
                console.log('ℹ Server error (500) detected - using ngrok setup');
                window.yoloUsingTunnel = true;
                return false;
            }

            const data = await response.json();
            
            // Check if response indicates tunnel mode
            if (data.is_tunnel) {
                console.log('ℹ Tunnel mode detected from status check');
                window.yoloUsingTunnel = true;
                return false;
            }
            
            return data.success && data.running;
        } catch (error) {
            console.error('Error checking service status:', error);
            return false;
        }
    }

    /**
     * Start the service
     */
    async start() {
        try {
            const response = await fetch(this.serviceUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=start'
            });

            // Check if response is OK
            if (!response.ok) {
                console.error('Server error:', response.status, response.statusText);
                // If 500 error, likely using ngrok and file needs to be uploaded
                if (response.status === 500) {
                    console.log('ℹ Server error detected - likely using ngrok setup');
                    window.yoloUsingTunnel = true;
                    this.showTunnelInstructions([
                        'Upload yolo_service_control.php to InfinityFree',
                        'Make sure Flask + ngrok are running on your laptop',
                        'Then refresh this page'
                    ]);
                    return 'tunnel';
                }
                return false;
            }

            const data = await response.json();

            // Check if using ngrok/tunnel (auto-start not supported)
            if (!data.success && (data.is_tunnel || data.instructions)) {
                console.log('ℹ Using ngrok - manual start required:');
                if (data.instructions) {
                    data.instructions.forEach(instruction => console.log('  ' + instruction));
                }
                this.showTunnelInstructions(data.instructions || ['Start services on your laptop']);
                // Mark as tunnel mode to skip retries
                window.yoloUsingTunnel = true;
                return 'tunnel'; // Special return value to indicate tunnel mode
            }

            return data.success;
        } catch (error) {
            console.error('Error starting service:', error);
            return false;
        }
    }

    /**
     * Show instructions for manual start with ngrok
     */
    showTunnelInstructions(instructions) {
        const message = 'YOLO service must be started manually on your PC:\n\n' +
            instructions.join('\n') +
            '\n\nSee SIMPLE_STEPS.txt for complete guide.';

        // Only show alert once
        if (!window.yoloTunnelAlertShown) {
            alert(message);
            window.yoloTunnelAlertShown = true;
        }
    }

    /**
     * Ensure service is running, start if needed
     */
    async ensureRunning() {
        const isRunning = await this.isRunning();

        if (isRunning) {
            console.log('✓ YOLO service is already running');
            return true;
        }

        // Check if another instance is already trying to start the service
        if (window.yoloServiceStarting) {
            console.log('⏳ Another instance is starting the service, waiting...');
            // Wait for the other instance to finish
            await new Promise(resolve => setTimeout(resolve, 5000));
            return await this.isRunning();
        }

        // Mark that we're starting the service
        window.yoloServiceStarting = true;

        console.log('⚠ YOLO service is not running, attempting to start...');

        // Show notification to user
        this.showNotification('Starting YOLO detection service...', 'info');

        const started = await this.start();

        // Clear the starting flag
        window.yoloServiceStarting = false;

        // Check if tunnel mode was detected
        if (started === 'tunnel') {
            console.log('ℹ Using ngrok tunnel - service must be started manually on your PC');
            return false;
        }

        if (started) {
            console.log('✓ YOLO service started successfully');
            this.showNotification('YOLO service started successfully!', 'success');
            return true;
        } else {
            console.error('✗ Failed to start YOLO service');
            this.showNotification('Failed to start YOLO service. Please start it manually.', 'error');
            return false;
        }
    }

    /**
     * Auto-start with retry logic
     */
    async autoStart() {
        // Check if using tunnel mode (ngrok) - skip auto-start
        if (window.yoloUsingTunnel) {
            console.log('ℹ Tunnel mode detected - skipping auto-start');
            return false;
        }

        if (this.retryCount >= this.maxRetries) {
            this.showNotification('Unable to start service after multiple attempts. Please check the service manager.', 'error');
            return false;
        }

        const success = await this.ensureRunning();

        // Check if tunnel mode was detected during ensureRunning
        if (window.yoloUsingTunnel) {
            console.log('ℹ Tunnel mode detected - stopping retries');
            return false;
        }

        if (!success) {
            this.retryCount++;
            console.log(`Retry ${this.retryCount}/${this.maxRetries}...`);

            // Wait 2 seconds before retry
            await new Promise(resolve => setTimeout(resolve, 2000));
            return await this.autoStart();
        }

        this.retryCount = 0;
        return true;
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'info') {
        // Check if there's a notification system in place
        if (typeof showNotification === 'function') {
            showNotification(message, type);
            return;
        }

        // Fallback to console
        const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
        console.log(`${icon} ${message}`);

        // Simple alert for critical errors
        if (type === 'error') {
            // Don't alert on first attempt
            if (this.retryCount > 0) {
                alert(message);
            }
        }
    }

    /**
     * Monitor service health
     */
    startMonitoring(interval = 30000) {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }

        this.checkInterval = setInterval(async () => {
            const isRunning = await this.isRunning();

            if (!isRunning) {
                console.warn('⚠ YOLO service stopped unexpectedly');
                this.showNotification('YOLO service stopped. Attempting to restart...', 'warning');
                await this.autoStart();
            }
        }, interval);
    }

    /**
     * Stop monitoring
     */
    stopMonitoring() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }
}

// Create global instance (only if not already created)
if (!window.yoloServiceManager) {
    window.yoloServiceManager = new YOLOServiceManager();
}

// Auto-start on page load (only once)
if (!window.yoloServiceInitialized) {
    window.yoloServiceInitialized = true;

    document.addEventListener('DOMContentLoaded', async function () {
        console.log('Initializing YOLO service...');

        // Wait a moment for page to fully load
        await new Promise(resolve => setTimeout(resolve, 500));

        // Ensure service is running
        const success = await window.yoloServiceManager.autoStart();

        if (success) {
            // Start monitoring service health
            window.yoloServiceManager.startMonitoring();
        }
    });
}

// Cleanup on page unload
window.addEventListener('beforeunload', function () {
    window.yoloServiceManager.stopMonitoring();
});

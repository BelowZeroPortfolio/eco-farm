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
            
            const data = await response.json();
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
            
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Error starting service:', error);
            return false;
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
        if (this.retryCount >= this.maxRetries) {
            this.showNotification('Unable to start service after multiple attempts. Please check the service manager.', 'error');
            return false;
        }

        const success = await this.ensureRunning();
        
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
    
    document.addEventListener('DOMContentLoaded', async function() {
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
window.addEventListener('beforeunload', function() {
    window.yoloServiceManager.stopMonitoring();
});

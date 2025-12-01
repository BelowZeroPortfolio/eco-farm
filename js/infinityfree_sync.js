/**
 * InfinityFree Database Sync via Browser AJAX
 * 
 * This bypasses InfinityFree's anti-bot protection by making requests
 * from the browser (real user session) instead of server-to-server.
 * 
 * Flow:
 * 1. Browser gets sensor data from local Arduino bridge
 * 2. Browser sends data directly to InfinityFree via AJAX
 * 3. InfinityFree sees it as legitimate user activity
 */

const InfinityFreeSync = {
    // Configuration - UPDATE THESE
    config: {
        infinityfreeUrl: 'https://sagayecofarm.infinityfreeapp.com/api/browser_upload.php',
        apiKey: 'sagayeco-farm-2024-secure-key-xyz789',
        localArduinoUrl: '/arduino_sync.php?action=get_all',
        enabled: true,
        debug: true
    },

    // State
    lastSyncTime: 0,
    syncIntervalMs: 0,
    syncTimer: null,
    isOnline: navigator.onLine,

    /**
     * Initialize the sync system
     * @param {number} intervalSeconds - Sync interval in seconds (from PHP settings)
     */
    init: function(intervalSeconds) {
        this.syncIntervalMs = intervalSeconds * 1000;
        
        if (this.config.debug) {
            console.log('🌐 InfinityFree Sync initialized');
            console.log('📊 Sync interval:', intervalSeconds, 'seconds');
            console.log('🔗 Target URL:', this.config.infinityfreeUrl);
        }

        // Listen for online/offline events
        window.addEventListener('online', () => {
            this.isOnline = true;
            console.log('🟢 Browser is online - sync enabled');
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            console.log('🔴 Browser is offline - sync paused');
        });

        // Start sync loop
        this.startSyncLoop();
    },

    /**
     * Start the sync loop based on interval
     */
    startSyncLoop: function() {
        if (this.syncTimer) {
            clearInterval(this.syncTimer);
        }

        // Initial sync after 5 seconds (let page load first)
        setTimeout(() => this.syncToInfinityFree(), 5000);

        // Then sync at the configured interval
        this.syncTimer = setInterval(() => {
            this.syncToInfinityFree();
        }, this.syncIntervalMs);

        console.log('⏱️ Sync loop started - every', this.syncIntervalMs / 1000, 'seconds');
    },

    /**
     * Main sync function - gets local data and pushes to InfinityFree
     */
    async syncToInfinityFree() {
        if (!this.config.enabled) {
            console.log('⏸️ Sync disabled');
            return;
        }

        if (!this.isOnline) {
            console.log('📴 Offline - skipping sync');
            return;
        }

        try {
            // Step 1: Get sensor data from local Arduino bridge
            const sensorData = await this.getLocalSensorData();
            
            if (!sensorData) {
                console.log('⚠️ No sensor data available');
                return;
            }

            if (this.config.debug) {
                console.log('📡 Got local sensor data:', sensorData);
            }

            // Step 2: Send to InfinityFree
            const result = await this.pushToInfinityFree(sensorData);
            
            if (result.success) {
                this.lastSyncTime = Date.now();
                this.updateSyncStatus('success', result.message);
                console.log('✅ Synced to InfinityFree:', result.message);
            } else {
                this.updateSyncStatus('error', result.message);
                console.error('❌ Sync failed:', result.message);
            }

        } catch (error) {
            this.updateSyncStatus('error', error.message);
            console.error('❌ Sync error:', error);
        }
    },

    /**
     * Get sensor data from local Arduino bridge
     */
    async getLocalSensorData() {
        try {
            const response = await fetch(this.config.localArduinoUrl, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const data = await response.json();

            if (data.success && data.data) {
                return {
                    temperature: data.data.temperature?.value ?? data.data.temperature,
                    humidity: data.data.humidity?.value ?? data.data.humidity,
                    soil_moisture: data.data.soil_moisture?.value ?? data.data.soil_moisture,
                    timestamp: new Date().toISOString()
                };
            }

            return null;
        } catch (error) {
            console.error('Failed to get local sensor data:', error);
            return null;
        }
    },

    /**
     * Push sensor data to InfinityFree via AJAX
     * This bypasses anti-bot because it's from a real browser session
     */
    async pushToInfinityFree(sensorData) {
        try {
            // Create form data (more compatible with PHP)
            const formData = new FormData();
            formData.append('api_key', this.config.apiKey);
            formData.append('temperature', sensorData.temperature);
            formData.append('humidity', sensorData.humidity);
            formData.append('soil_moisture', sensorData.soil_moisture);
            formData.append('timestamp', sensorData.timestamp);
            formData.append('source', 'browser_ajax'); // Identify as browser request

            const response = await fetch(this.config.infinityfreeUrl, {
                method: 'POST',
                body: formData,
                // Important: Don't set Content-Type header - let browser set it with boundary
                credentials: 'include', // Include cookies for session
                mode: 'cors'
            });

            // Check if response is OK
            if (!response.ok) {
                // Try to get error message
                const text = await response.text();
                
                // Check for InfinityFree anti-bot page
                if (text.includes('Checking your browser') || text.includes('Please wait')) {
                    return {
                        success: false,
                        message: 'Anti-bot check triggered - try refreshing the InfinityFree page first'
                    };
                }
                
                return {
                    success: false,
                    message: `HTTP ${response.status}: ${text.substring(0, 100)}`
                };
            }

            const result = await response.json();
            return result;

        } catch (error) {
            // Handle CORS or network errors
            if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                return {
                    success: false,
                    message: 'CORS error - check InfinityFree CORS headers'
                };
            }
            
            return {
                success: false,
                message: error.message
            };
        }
    },

    /**
     * Update sync status indicator in UI
     */
    updateSyncStatus(status, message) {
        const indicator = document.getElementById('infinityfree-sync-status');
        if (!indicator) return;

        const statusColors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            pending: 'bg-yellow-500'
        };

        const statusIcons = {
            success: 'fa-cloud-upload-alt',
            error: 'fa-exclamation-triangle',
            pending: 'fa-spinner fa-spin'
        };

        indicator.className = `px-2 py-1 ${statusColors[status]} text-white text-xs rounded-full font-medium`;
        indicator.innerHTML = `<i class="fas ${statusIcons[status]} mr-1"></i>${status === 'success' ? 'Synced' : 'Error'}`;
        indicator.title = message;
    },

    /**
     * Manual sync trigger
     */
    manualSync() {
        console.log('🔄 Manual sync triggered');
        this.syncToInfinityFree();
    },

    /**
     * Stop sync loop
     */
    stop() {
        if (this.syncTimer) {
            clearInterval(this.syncTimer);
            this.syncTimer = null;
        }
        console.log('⏹️ Sync stopped');
    }
};

// Export for use
window.InfinityFreeSync = InfinityFreeSync;

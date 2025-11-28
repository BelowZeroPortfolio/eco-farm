<?php

/**
 * Environment Configuration Loader
 * 
 * Loads and parses .env file for application configuration
 */

class Env
{
    private static $config = [];
    private static $loaded = false;

    /**
     * Load environment variables from hardcoded configuration
     * Compatible with InfinityFree hosting (no .env file support)
     * 
     * @param string $envFile Path to .env file (deprecated, kept for compatibility)
     * @return void
     */
    public static function load($envFile = null)
    {
        if (self::$loaded) {
            return;
        }

        // ============================================
        // HARDCODED CONFIGURATION FOR INFINITYFREE
        // ============================================
        // IMPORTANT: Update these values with your actual InfinityFree database credentials
        // You can find these in your InfinityFree control panel under MySQL Databases
        
        self::$config = [
            // Database Configuration
            'DB_HOST' => 'sql100.infinityfree.com',                    // Usually 'localhost' or 'sqlXXX.infinityfree.com'
            'DB_NAME' => 'if0_40518906_farm_database',                // Your InfinityFree database name (e.g., epiz_12345678_farm)
            'DB_USER' => 'if0_40518906',                         // Your InfinityFree database username (e.g., epiz_12345678)
            'DB_PASS' => '5vgtAfWmavQZH',                             // Your InfinityFree database password
            'DB_CHARSET' => 'utf8mb4',
            
            // Application Configuration
            'APP_NAME' => 'IoT Farm Monitoring System',
            'APP_ENV' => 'production',                   // 'development' or 'production'
            'APP_DEBUG' => 'false',                      // Set to 'false' in production
            'APP_URL' => 'https://sagayecofarm.infinityfreeapp.com/', // Your InfinityFree domain

            'YOLO_SERVICE_HOST' => 'unsmarting-unraving-elinore.ngrok-free.dev',  // Your tunnel subdomain
            'YOLO_SERVICE_PORT' => '443',  // HTTPS port
            'YOLO_SERVICE_PROTOCOL' => 'https',  // Use HTTPS through tunnel
            
            // Session Configuration
            'SESSION_LIFETIME' => '7200',                // 2 hours in seconds
            'SESSION_SECURE' => 'false',                 // Set to 'true' if using HTTPS
            'SESSION_HTTPONLY' => 'true',
            
            // Security Configuration
            'SECURITY_KEY' => 'change-this-to-random-string-' . md5(__DIR__), // Change this!
            'CSRF_ENABLED' => 'true',
            'XSS_PROTECTION' => 'true',
            
            // File Upload Configuration
            'UPLOAD_MAX_SIZE' => '5242880',              // 5MB in bytes
            'ALLOWED_IMAGE_TYPES' => 'jpg,jpeg,png,gif',
            
            // Timezone
            'TIMEZONE' => 'Asia/Manila',
            
            // Email Configuration (if needed)
            'MAIL_HOST' => 'smtp.gmail.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => '',                       // Your email
            'MAIL_PASSWORD' => '',                       // Your email password
            'MAIL_FROM_ADDRESS' => 'noreply@yourdomain.com',
            'MAIL_FROM_NAME' => 'Farm Monitor',
            
            // API Keys (if needed)
            'WEATHER_API_KEY' => '',                     // OpenWeatherMap API key
            
            // Feature Flags
            'ENABLE_NOTIFICATIONS' => 'true',
            'ENABLE_EMAIL_ALERTS' => 'false',            // Email not reliable on free hosting
            'ENABLE_WEATHER_API' => 'false',
            
            // Logging
            'LOG_LEVEL' => 'error',                      // 'debug', 'info', 'warning', 'error'
            'LOG_FILE' => 'logs/app.log',
        ];

        self::$loaded = true;
    }

    /**
     * Get environment variable value
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed Variable value or default
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * Get environment variable as boolean
     * 
     * @param string $key Variable name
     * @param bool $default Default value if not found
     * @return bool Boolean value
     */
    public static function getBool($key, $default = false)
    {
        $value = self::get($key);
        
        if ($value === null) {
            return $default;
        }

        $value = strtolower(trim($value));
        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Get environment variable as integer
     * 
     * @param string $key Variable name
     * @param int $default Default value if not found
     * @return int Integer value
     */
    public static function getInt($key, $default = 0)
    {
        $value = self::get($key);
        
        if ($value === null) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * Get environment variable as float
     * 
     * @param string $key Variable name
     * @param float $default Default value if not found
     * @return float Float value
     */
    public static function getFloat($key, $default = 0.0)
    {
        $value = self::get($key);
        
        if ($value === null) {
            return $default;
        }

        return (float) $value;
    }

    /**
     * Get environment variable as array (comma-separated)
     * 
     * @param string $key Variable name
     * @param array $default Default value if not found
     * @return array Array value
     */
    public static function getArray($key, $default = [])
    {
        $value = self::get($key);
        
        if ($value === null || $value === '') {
            return $default;
        }

        return array_map('trim', explode(',', $value));
    }

    /**
     * Check if environment variable exists
     * 
     * @param string $key Variable name
     * @return bool True if exists
     */
    public static function has($key)
    {
        if (!self::$loaded) {
            self::load();
        }

        return isset(self::$config[$key]);
    }

    /**
     * Get all environment variables
     * 
     * @return array All variables
     */
    public static function all()
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * Reload environment variables
     * 
     * @param string $envFile Path to .env file
     * @return void
     */
    public static function reload($envFile = null)
    {
        self::$config = [];
        self::$loaded = false;
        self::load($envFile);
    }
}

// Auto-load environment variables
Env::load();

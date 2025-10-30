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
     * Load environment variables from .env file
     * 
     * @param string $envFile Path to .env file
     * @return void
     */
    public static function load($envFile = null)
    {
        if (self::$loaded) {
            return;
        }

        if ($envFile === null) {
            $envFile = __DIR__ . '/../.env';
        }

        if (!file_exists($envFile)) {
            error_log("Warning: .env file not found at {$envFile}");
            self::$loaded = true;
            return;
        }

        $envContent = file_get_contents($envFile);
        if ($envContent === false) {
            error_log("Warning: Could not read .env file at {$envFile}");
            self::$loaded = true;
            return;
        }

        // Parse .env file line by line
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }

                self::$config[$key] = $value;
            }
        }

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

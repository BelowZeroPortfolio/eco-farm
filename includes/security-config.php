<?php

/**
 * Security Configuration for IoT Farm Monitoring System
 * 
 * Centralized security settings and policies
 */

// Load environment configuration
require_once __DIR__ . '/../config/env.php';

/**
 * Security Configuration Class
 */
class SecurityConfig
{
    /**
     * Session security settings (loaded from .env)
     */
    public static function getSessionTimeout() {
        return Env::getInt('SESSION_TIMEOUT', 3600);
    }
    
    public static function getSessionRegenerateInterval() {
        return Env::getInt('SESSION_REGENERATE_INTERVAL', 300);
    }
    
    public static function getSessionName() {
        return Env::get('SESSION_NAME', 'FARM_MONITOR_SESSION');
    }

    // Legacy constants for backward compatibility
    const SESSION_TIMEOUT = 3600; // 1 hour
    const SESSION_REGENERATE_INTERVAL = 300; // 5 minutes
    const SESSION_NAME = 'FARM_MONITOR_SESSION';

    /**
     * CSRF protection settings (loaded from .env)
     */
    public static function getCsrfTokenLifetime() {
        return Env::getInt('CSRF_TOKEN_LIFETIME', 3600);
    }
    
    public static function getCsrfTokenLength() {
        return Env::getInt('CSRF_TOKEN_LENGTH', 32);
    }

    // Legacy constants for backward compatibility
    const CSRF_TOKEN_LIFETIME = 3600; // 1 hour
    const CSRF_TOKEN_LENGTH = 32; // bytes

    /**
     * Rate limiting settings (loaded from .env)
     */
    public static function getRateLimitLoginAttempts() {
        return Env::getInt('RATE_LIMIT_LOGIN_ATTEMPTS', 5);
    }
    
    public static function getRateLimitLoginWindow() {
        return Env::getInt('RATE_LIMIT_LOGIN_WINDOW', 300);
    }
    
    public static function getRateLimitPageRequests() {
        return Env::getInt('RATE_LIMIT_PAGE_REQUESTS', 60);
    }
    
    public static function getRateLimitPageWindow() {
        return Env::getInt('RATE_LIMIT_PAGE_WINDOW', 60);
    }
    
    public static function getRateLimitFormSubmissions() {
        return Env::getInt('RATE_LIMIT_FORM_SUBMISSIONS', 10);
    }
    
    public static function getRateLimitFormWindow() {
        return Env::getInt('RATE_LIMIT_FORM_WINDOW', 60);
    }

    // Legacy constants for backward compatibility
    const RATE_LIMIT_LOGIN_ATTEMPTS = 5;
    const RATE_LIMIT_LOGIN_WINDOW = 300; // 5 minutes
    const RATE_LIMIT_PAGE_REQUESTS = 60;
    const RATE_LIMIT_PAGE_WINDOW = 60; // 1 minute
    const RATE_LIMIT_FORM_SUBMISSIONS = 10;
    const RATE_LIMIT_FORM_WINDOW = 60; // 1 minute

    /**
     * File upload security settings (loaded from .env)
     */
    public static function getMaxFileSize() {
        return Env::getInt('MAX_FILE_SIZE', 5242880);
    }
    
    public static function getAllowedFileTypes() {
        return Env::getArray('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'csv', 'xlsx']);
    }

    // Legacy constants for backward compatibility
    const MAX_FILE_SIZE = 5242880; // 5MB
    const ALLOWED_FILE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'csv', 'xlsx'];
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'text/csv',
        'application/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    /**
     * Password security settings (loaded from .env)
     */
    public static function getMinPasswordLength() {
        return Env::getInt('MIN_PASSWORD_LENGTH', 6);
    }
    
    public static function getPasswordHashCost() {
        return Env::getInt('PASSWORD_HASH_COST', 12);
    }
    
    public static function getPasswordHashOptions() {
        return ['cost' => self::getPasswordHashCost()];
    }

    // Legacy constants for backward compatibility
    const MIN_PASSWORD_LENGTH = 6;
    const PASSWORD_HASH_ALGORITHM = PASSWORD_DEFAULT;
    const PASSWORD_HASH_OPTIONS = ['cost' => 12];

    /**
     * Input validation limits (loaded from .env)
     */
    public static function getMaxUsernameLength() {
        return Env::getInt('MAX_USERNAME_LENGTH', 50);
    }
    
    public static function getMinUsernameLength() {
        return Env::getInt('MIN_USERNAME_LENGTH', 3);
    }
    
    public static function getMaxEmailLength() {
        return Env::getInt('MAX_EMAIL_LENGTH', 100);
    }
    
    public static function getMaxInputLength() {
        return Env::getInt('MAX_INPUT_LENGTH', 1000);
    }
    
    public static function getMaxTextareaLength() {
        return Env::getInt('MAX_TEXTAREA_LENGTH', 5000);
    }

    // Legacy constants for backward compatibility
    const MAX_USERNAME_LENGTH = 50;
    const MIN_USERNAME_LENGTH = 3;
    const MAX_EMAIL_LENGTH = 100;
    const MAX_INPUT_LENGTH = 1000;
    const MAX_TEXTAREA_LENGTH = 5000;

    /**
     * Security headers configuration
     */
    public static function getSecurityHeaders()
    {
        return [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(self)',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
        ];
    }

    /**
     * Content Security Policy configuration
     */
    public static function getCSPDirectives()
    {
        return [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https://cdn.jsdelivr.net",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
    }

    /**
     * Page access permissions
     */
    public static function getPagePermissions()
    {
        return [
            'dashboard.php' => ['admin', 'farmer', 'student'],
            'sensors.php' => ['admin', 'farmer', 'student'],
            'pest_detection.php' => ['admin', 'farmer', 'student'],
            'profile.php' => ['admin', 'farmer', 'student'],
            'notifications.php' => ['admin', 'farmer', 'student'],
            'user_management.php' => ['admin'],
            'reports.php' => ['admin'],
            'settings.php' => ['admin'],
            'login.php' => ['*'], // Public access
            'logout.php' => ['*'], // Public access
            'index.php' => ['*'] // Public access
        ];
    }

    /**
     * Feature permissions
     */
    public static function getFeaturePermissions()
    {
        return [
            'user_management' => ['admin'],
            'system_settings' => ['admin'],
            'view_reports' => ['admin'],
            'export_data' => ['admin'],
            'manage_sensors' => ['admin', 'farmer'],
            'view_dashboard' => ['admin', 'farmer', 'student'],
            'view_pest_alerts' => ['admin', 'farmer', 'student'],
            'update_profile' => ['admin', 'farmer', 'student'],
            'change_password' => ['admin', 'farmer', 'student'],
            'view_notifications' => ['admin', 'farmer', 'student']
        ];
    }

    /**
     * Suspicious activity patterns
     */
    public static function getSuspiciousPatterns()
    {
        return [
            // SQL Injection patterns
            '/\b(union|select|insert|update|delete|drop|create|alter|exec)\b/i',
            '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
            '/(\%3D)|(=)[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',

            // XSS patterns
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',

            // Path traversal patterns
            '/\.\.\//i',
            '/\.\.\\\/i',
            '/etc\/passwd/i',
            '/proc\/self\/environ/i',
            '/windows\/system32/i',

            // Command injection patterns
            '/;\s*(ls|cat|pwd|id|whoami|uname)/i',
            '/\|\s*(ls|cat|pwd|id|whoami|uname)/i',
            '/`[^`]*`/i',
            '/\$\([^)]*\)/i',

            // File inclusion patterns
            '/php:\/\/input/i',
            '/php:\/\/filter/i',
            '/data:\/\//i',
            '/file:\/\//i'
        ];
    }

    /**
     * Blocked user agents (bots, scanners, etc.)
     */
    public static function getBlockedUserAgents()
    {
        return [
            '/sqlmap/i',
            '/nikto/i',
            '/nessus/i',
            '/openvas/i',
            '/nmap/i',
            '/masscan/i',
            '/zap/i',
            '/burp/i',
            '/acunetix/i',
            '/w3af/i'
        ];
    }

    /**
     * Trusted IP ranges (for admin access, etc.)
     */
    public static function getTrustedIPRanges()
    {
        return [
            '127.0.0.1', // localhost
            '::1', // localhost IPv6
            // Add your trusted IP ranges here
            // '192.168.1.0/24',
            // '10.0.0.0/8'
        ];
    }

    /**
     * Security event types for logging
     */
    public static function getSecurityEventTypes()
    {
        return [
            'login_success',
            'login_failure',
            'logout',
            'password_change',
            'profile_update',
            'unauthorized_access',
            'suspicious_activity',
            'rate_limit_exceeded',
            'csrf_token_invalid',
            'session_hijack_attempt',
            'file_upload_blocked',
            'sql_injection_attempt',
            'xss_attempt',
            'path_traversal_attempt'
        ];
    }

    /**
     * Get security setting by key
     */
    public static function get($key, $default = null)
    {
        $settings = [
            'session_timeout' => self::SESSION_TIMEOUT,
            'session_regenerate_interval' => self::SESSION_REGENERATE_INTERVAL,
            'csrf_token_lifetime' => self::CSRF_TOKEN_LIFETIME,
            'max_file_size' => self::MAX_FILE_SIZE,
            'min_password_length' => self::MIN_PASSWORD_LENGTH,
            'max_username_length' => self::MAX_USERNAME_LENGTH,
            'min_username_length' => self::MIN_USERNAME_LENGTH,
            'max_email_length' => self::MAX_EMAIL_LENGTH
        ];

        return $settings[$key] ?? $default;
    }

    /**
     * Check if IP is in trusted range
     */
    public static function isTrustedIP($ip)
    {
        $trustedRanges = self::getTrustedIPRanges();

        foreach ($trustedRanges as $range) {
            if (self::ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in range
     */
    private static function ipInRange($ip, $range)
    {
        if (strpos($range, '/') === false) {
            // Single IP
            return $ip === $range;
        }

        // CIDR range
        list($subnet, $mask) = explode('/', $range);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6 - simplified check
            return strpos($ip, substr($subnet, 0, strpos($subnet, '::', -4))) === 0;
        }

        return false;
    }

    /**
     * Validate security configuration
     */
    public static function validateConfig()
    {
        $errors = [];

        // Check session settings
        if (self::SESSION_TIMEOUT < 300) {
            $errors[] = 'Session timeout too short (minimum 5 minutes)';
        }

        if (self::SESSION_REGENERATE_INTERVAL > self::SESSION_TIMEOUT) {
            $errors[] = 'Session regenerate interval cannot be longer than session timeout';
        }

        // Check password settings
        if (self::MIN_PASSWORD_LENGTH < 6) {
            $errors[] = 'Minimum password length too short (minimum 6 characters)';
        }

        // Check file upload settings
        if (self::MAX_FILE_SIZE > 10485760) { // 10MB
            $errors[] = 'Maximum file size too large (maximum 10MB recommended)';
        }

        return $errors;
    }
}

// Validate configuration on load
$configErrors = SecurityConfig::validateConfig();
if (!empty($configErrors)) {
    error_log('Security configuration errors: ' . implode(', ', $configErrors));
}

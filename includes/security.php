<?php

/**
 * Security Utilities for IoT Farm Monitoring System
 * 
 * Provides comprehensive security measures including CSRF protection,
 * input sanitization, session security, and access control middleware
 */

require_once __DIR__ . '/security-config.php';

/**
 * CSRF Protection Class
 * 
 * Handles Cross-Site Request Forgery protection for all forms
 */
class CSRFProtection
{
    private static $tokenName = 'csrf_token';
    private static $sessionKey = 'csrf_tokens';
    
    /**
     * Generate a new CSRF token
     * 
     * @param string $formId Optional form identifier for multiple forms
     * @return string Generated token
     */
    public static function generateToken($formId = 'default')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize tokens array if not exists
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        
        // Generate cryptographically secure token
        $token = bin2hex(random_bytes(32));
        
        // Store token with timestamp for expiration
        $_SESSION[self::$sessionKey][$formId] = [
            'token' => $token,
            'timestamp' => time(),
            'used' => false
        ];
        
        // Clean old tokens (older than 1 hour)
        self::cleanExpiredTokens();
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @param string $formId Form identifier
     * @param bool $singleUse Whether token should be invalidated after use
     * @return bool True if token is valid
     */
    public static function validateToken($token, $formId = 'default', $singleUse = true)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if tokens exist
        if (!isset($_SESSION[self::$sessionKey][$formId])) {
            return false;
        }
        
        $storedData = $_SESSION[self::$sessionKey][$formId];
        
        // Check if token was already used (for single-use tokens)
        if ($singleUse && $storedData['used']) {
            return false;
        }
        
        // Check token expiration (1 hour)
        if (time() - $storedData['timestamp'] > 3600) {
            unset($_SESSION[self::$sessionKey][$formId]);
            return false;
        }
        
        // Validate token using timing-safe comparison
        if (!hash_equals($storedData['token'], $token)) {
            return false;
        }
        
        // Mark token as used if single-use
        if ($singleUse) {
            $_SESSION[self::$sessionKey][$formId]['used'] = true;
        }
        
        return true;
    }
    
    /**
     * Get CSRF token for form
     * 
     * @param string $formId Form identifier
     * @return string|null Token or null if not found
     */
    public static function getToken($formId = 'default')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION[self::$sessionKey][$formId]['token'] ?? null;
    }
    
    /**
     * Generate CSRF hidden input field
     * 
     * @param string $formId Form identifier
     * @return string HTML input field
     */
    public static function getHiddenField($formId = 'default')
    {
        $token = self::generateToken($formId);
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Clean expired tokens from session
     */
    private static function cleanExpiredTokens()
    {
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }
        
        $currentTime = time();
        foreach ($_SESSION[self::$sessionKey] as $formId => $data) {
            if ($currentTime - $data['timestamp'] > 3600) {
                unset($_SESSION[self::$sessionKey][$formId]);
            }
        }
    }
    
    /**
     * Clear all CSRF tokens
     */
    public static function clearAllTokens()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION[self::$sessionKey] = [];
    }
}

/**
 * Input Sanitization and Validation Class
 * 
 * Enhanced input sanitization with context-aware filtering
 */
class SecuritySanitizer
{
    /**
     * Sanitize input based on context
     * 
     * @param mixed $input Input to sanitize
     * @param string $context Context type (html, sql, url, filename, etc.)
     * @return mixed Sanitized input
     */
    public static function sanitize($input, $context = 'html')
    {
        if (is_array($input)) {
            return array_map(function($item) use ($context) {
                return self::sanitize($item, $context);
            }, $input);
        }
        
        if (!is_string($input)) {
            return $input;
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        switch ($context) {
            case 'html':
                return self::sanitizeHTML($input);
            case 'attribute':
                return self::sanitizeHTMLAttribute($input);
            case 'url':
                return self::sanitizeURL($input);
            case 'filename':
                return self::sanitizeFilename($input);
            case 'email':
                return self::sanitizeEmail($input);
            case 'username':
                return self::sanitizeUsername($input);
            case 'sql':
                return self::sanitizeSQL($input);
            case 'json':
                return self::sanitizeJSON($input);
            default:
                return self::sanitizeGeneral($input);
        }
    }
    
    /**
     * Sanitize HTML content
     */
    private static function sanitizeHTML($input)
    {
        // Remove dangerous tags and attributes
        $input = strip_tags($input);
        
        // Convert special characters to HTML entities
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize HTML attributes
     */
    private static function sanitizeHTMLAttribute($input)
    {
        // More aggressive sanitization for attributes
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize URLs
     */
    private static function sanitizeURL($input)
    {
        $input = trim($input);
        
        // Validate URL format
        if (!filter_var($input, FILTER_VALIDATE_URL)) {
            return '';
        }
        
        // Parse URL to check components
        $parsed = parse_url($input);
        
        // Only allow http and https schemes
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return '';
        }
        
        return filter_var($input, FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitize filenames
     */
    private static function sanitizeFilename($input)
    {
        // Remove path traversal attempts
        $input = basename($input);
        
        // Remove dangerous characters
        $input = preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
        
        // Limit length
        return substr($input, 0, 255);
    }
    
    /**
     * Sanitize email addresses
     */
    private static function sanitizeEmail($input)
    {
        return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize usernames
     */
    private static function sanitizeUsername($input)
    {
        $input = trim($input);
        
        // Only allow alphanumeric, underscore, and hyphen
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
    }
    
    /**
     * Sanitize for SQL context (additional layer beyond prepared statements)
     */
    private static function sanitizeSQL($input)
    {
        // Remove SQL injection patterns
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/i',
            '/[\'";\\\\]/',
            '/--/',
            '/\/\*.*?\*\//'
        ];
        
        foreach ($patterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }
        
        return trim($input);
    }
    
    /**
     * Sanitize JSON input
     */
    private static function sanitizeJSON($input)
    {
        // Decode and re-encode to ensure valid JSON
        $decoded = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }
        
        return json_encode($decoded, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    /**
     * General sanitization
     */
    private static function sanitizeGeneral($input)
    {
        $input = trim($input);
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validate and sanitize file uploads
     * 
     * @param array $file $_FILES array element
     * @param array $options Upload options
     * @return array Validation result
     */
    public static function validateFileUpload($file, $options = [])
    {
        $defaults = [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'csv'],
            'allowed_mimes' => [
                'image/jpeg', 'image/png', 'image/gif',
                'application/pdf', 'text/csv', 'application/csv'
            ],
            'check_content' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded or upload failed'];
        }
        
        // Check file size
        if ($file['size'] > $options['max_size']) {
            $maxSizeMB = round($options['max_size'] / (1024 * 1024), 2);
            return ['valid' => false, 'error' => "File size exceeds {$maxSizeMB}MB limit"];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $options['allowed_types'])) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        // Check MIME type
        if ($options['check_content']) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $options['allowed_mimes'])) {
                return ['valid' => false, 'error' => 'File content type not allowed'];
            }
        }
        
        // Sanitize filename
        $sanitizedName = self::sanitizeFilename($file['name']);
        
        return [
            'valid' => true,
            'sanitized_name' => $sanitizedName,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'tmp_name' => $file['tmp_name']
        ];
    }
}

/**
 * Session Security Manager
 * 
 * Enhanced session security with regeneration, timeout, and fingerprinting
 */
class SessionSecurity
{
    private static $sessionTimeout = SecurityConfig::SESSION_TIMEOUT;
    private static $regenerateInterval = SecurityConfig::SESSION_REGENERATE_INTERVAL;
    
    /**
     * Initialize secure session
     */
    public static function initializeSecureSession()
    {
        // Configure secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        
        // Set session name
        session_name(SecurityConfig::SESSION_NAME);
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize session security
        self::validateSession();
        self::regenerateSessionId();
        self::setSessionFingerprint();
    }
    
    /**
     * Validate current session
     */
    public static function validateSession()
    {
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::$sessionTimeout) {
                self::destroySession();
                return false;
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Validate session fingerprint
        if (!self::validateSessionFingerprint()) {
            self::destroySession();
            return false;
        }
        
        return true;
    }
    
    /**
     * Regenerate session ID periodically
     */
    public static function regenerateSessionId()
    {
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
        
        if (time() - $_SESSION['last_regeneration'] > self::$regenerateInterval) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Set session fingerprint for additional security
     */
    private static function setSessionFingerprint()
    {
        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = self::generateFingerprint();
        }
    }
    
    /**
     * Validate session fingerprint
     */
    private static function validateSessionFingerprint()
    {
        if (!isset($_SESSION['fingerprint'])) {
            return false;
        }
        
        return hash_equals($_SESSION['fingerprint'], self::generateFingerprint());
    }
    
    /**
     * Generate session fingerprint
     */
    private static function generateFingerprint()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }
    
    /**
     * Destroy session securely
     */
    public static function destroySession()
    {
        // Clear session variables
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Set session timeout
     */
    public static function setSessionTimeout($timeout)
    {
        self::$sessionTimeout = $timeout;
    }
    
    /**
     * Get remaining session time
     */
    public static function getRemainingTime()
    {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }
        
        return max(0, self::$sessionTimeout - (time() - $_SESSION['last_activity']));
    }
}

/**
 * Access Control Middleware
 * 
 * Role-based access control with page-level permissions
 */
class AccessControlMiddleware
{
    private static $pagePermissions = null;
    
    /**
     * Get page permissions from config
     */
    private static function getPagePermissions()
    {
        if (self::$pagePermissions === null) {
            self::$pagePermissions = SecurityConfig::getPagePermissions();
        }
        return self::$pagePermissions;
    }
    
    /**
     * Check if user has access to current page
     * 
     * @param string $page Page filename
     * @param string $userRole User's role
     * @return bool True if access is allowed
     */
    public static function checkPageAccess($page, $userRole)
    {
        $permissions = self::getPagePermissions();
        
        // Allow access to public pages
        if (isset($permissions[$page]) && in_array('*', $permissions[$page])) {
            return true;
        }
        
        // Check if user is logged in
        if (empty($userRole)) {
            return false;
        }
        
        // Check page permissions
        if (!isset($permissions[$page])) {
            // Default to admin-only for undefined pages
            return $userRole === 'admin';
        }
        
        return in_array($userRole, $permissions[$page]);
    }
    
    /**
     * Enforce access control for current page
     * 
     * @param string $page Current page filename
     * @param string $redirectUrl URL to redirect unauthorized users
     */
    public static function enforceAccess($page, $redirectUrl = 'login.php')
    {
        // Initialize secure session
        SessionSecurity::initializeSecureSession();
        
        // Get current user role
        $userRole = $_SESSION['role'] ?? null;
        
        // Check access
        if (!self::checkPageAccess($page, $userRole)) {
            // Log unauthorized access attempt
            ErrorHandler::logError('Unauthorized access attempt', [
                'page' => $page,
                'user_role' => $userRole,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            // Redirect based on login status
            if (empty($userRole)) {
                // Not logged in - redirect to login
                header('Location: login.php?redirect=' . urlencode($page));
            } else {
                // Logged in but insufficient permissions - redirect to dashboard
                header('Location: dashboard.php?error=access_denied');
            }
            exit;
        }
    }
    
    /**
     * Add custom page permission
     * 
     * @param string $page Page filename
     * @param array $roles Allowed roles
     */
    public static function addPagePermission($page, $roles)
    {
        self::$pagePermissions[$page] = $roles;
    }
    
    /**
     * Get page permissions
     * 
     * @param string $page Page filename
     * @return array Allowed roles
     */
    
    
    /**
     * Check if user has specific permission
     * 
     * @param string $permission Permission name
     * @param string $userRole User's role
     * @return bool True if permission is granted
     */
    public static function hasPermission($permission, $userRole)
    {
        $permissions = SecurityConfig::getFeaturePermissions();
        
        if (!isset($permissions[$permission])) {
            return false;
        }
        
        return in_array($userRole, $permissions[$permission]);
    }
}

/**
 * Rate Limiting Class
 * 
 * Prevents brute force attacks and abuse
 */
class RateLimiter
{
    private static $attempts = [];
    
    /**
     * Check if action is rate limited
     * 
     * @param string $action Action identifier
     * @param string $identifier User identifier (IP, user ID, etc.)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if rate limited
     */
    public static function isRateLimited($action, $identifier, $maxAttempts, $timeWindow)
    {
        $key = $action . '_' . hash('sha256', $identifier);
        $now = time();
        
        // Initialize if not exists
        if (!isset(self::$attempts[$key])) {
            self::$attempts[$key] = [];
        }
        
        // Clean old attempts
        self::$attempts[$key] = array_filter(self::$attempts[$key], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Check if limit exceeded
        if (count(self::$attempts[$key]) >= $maxAttempts) {
            return true;
        }
        
        // Record this attempt
        self::$attempts[$key][] = $now;
        
        return false;
    }
    
    /**
     * Get remaining attempts
     * 
     * @param string $action Action identifier
     * @param string $identifier User identifier
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $timeWindow Time window in seconds
     * @return int Remaining attempts
     */
    public static function getRemainingAttempts($action, $identifier, $maxAttempts, $timeWindow)
    {
        $key = $action . '_' . hash('sha256', $identifier);
        $now = time();
        
        if (!isset(self::$attempts[$key])) {
            return $maxAttempts;
        }
        
        // Clean old attempts
        self::$attempts[$key] = array_filter(self::$attempts[$key], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        return max(0, $maxAttempts - count(self::$attempts[$key]));
    }
    
    /**
     * Reset attempts for identifier
     * 
     * @param string $action Action identifier
     * @param string $identifier User identifier
     */
    public static function resetAttempts($action, $identifier)
    {
        $key = $action . '_' . hash('sha256', $identifier);
        unset(self::$attempts[$key]);
    }
}

/**
 * Security Headers Manager
 * 
 * Sets security-related HTTP headers
 */
class SecurityHeaders
{
    /**
     * Set all security headers
     */
    public static function setSecurityHeaders()
    {
        $headers = SecurityConfig::getSecurityHeaders();
        
        foreach ($headers as $name => $value) {
            // Skip HSTS if not on HTTPS
            if ($name === 'Strict-Transport-Security' && 
                (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
                continue;
            }
            
            header("{$name}: {$value}");
        }
        
        // Content Security Policy
        self::setContentSecurityPolicy();
    }
    
    /**
     * Set Content Security Policy
     */
    private static function setContentSecurityPolicy()
    {
        $csp = SecurityConfig::getCSPDirectives();
        header('Content-Security-Policy: ' . implode('; ', $csp));
    }
}

// Initialize security headers for all requests
SecurityHeaders::setSecurityHeaders();
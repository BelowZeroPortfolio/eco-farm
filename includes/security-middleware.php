<?php

/**
 * Security Middleware for IoT Farm Monitoring System
 * 
 * This file should be included at the top of every protected page
 * to enforce security measures and access control
 */

// Include security utilities
require_once __DIR__ . '/security.php';

/**
 * Initialize page security
 * 
 * @param string $requiredRole Optional specific role requirement
 * @param array $allowedRoles Optional array of allowed roles
 */
function initializePageSecurity($requiredRole = null, $allowedRoles = null)
{
    // Get current page filename
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    // Set security headers
    SecurityHeaders::setSecurityHeaders();
    
    // Initialize secure session
    SessionSecurity::initializeSecureSession();
    
    // Enforce access control
    if ($requiredRole) {
        // Specific role required
        requireRole($requiredRole);
    } elseif ($allowedRoles) {
        // Multiple roles allowed
        requireRole($allowedRoles);
    } else {
        // Use default page-based access control
        AccessControlMiddleware::enforceAccess($currentPage);
    }
    
    // Additional security checks
    performSecurityChecks();
}

/**
 * Perform additional security checks
 */
function performSecurityChecks()
{
    // Check for suspicious activity
    detectSuspiciousActivity();
    
    // Validate session integrity
    if (!SessionSecurity::validateSession()) {
        redirectToLogin('Session expired or invalid');
    }
    
    // Check for concurrent sessions (optional)
    checkConcurrentSessions();
}

/**
 * Detect suspicious activity patterns
 */
function detectSuspiciousActivity()
{
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Check for blocked user agents
    $blockedAgents = SecurityConfig::getBlockedUserAgents();
    foreach ($blockedAgents as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            logSecurityEvent('blocked_user_agent', [
                'user_agent' => $userAgent,
                'ip' => $clientIP
            ]);
            
            http_response_code(403);
            die('Access denied');
        }
    }
    
    // Check for suspicious patterns in request
    $suspiciousPatterns = SecurityConfig::getSuspiciousPatterns();
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $requestUri) || preg_match($pattern, $userAgent)) {
            // Log suspicious activity
            logSecurityEvent('suspicious_activity', [
                'pattern' => $pattern,
                'ip' => $clientIP,
                'user_agent' => $userAgent,
                'request_uri' => $requestUri,
                'user_id' => getUserId()
            ]);
            
            // Block request
            http_response_code(403);
            die('Access denied');
        }
    }
    
    // Check for rapid requests (simple DDoS protection)
    if (RateLimiter::isRateLimited('page_request', $clientIP, 
        SecurityConfig::RATE_LIMIT_PAGE_REQUESTS, 
        SecurityConfig::RATE_LIMIT_PAGE_WINDOW)) {
        
        logSecurityEvent('rate_limit_exceeded', [
            'ip' => $clientIP,
            'user_agent' => $userAgent,
            'limit_type' => 'page_request'
        ]);
        
        http_response_code(429);
        die('Too many requests');
    }
}

/**
 * Check for concurrent sessions (optional security measure)
 */
function checkConcurrentSessions()
{
    // This is a basic implementation - in production you might want
    // to store session info in database for better tracking
    
    if (!isLoggedIn()) {
        return;
    }
    
    $userId = getUserId();
    $currentSessionId = session_id();
    
    // Store current session info
    if (!isset($_SESSION['session_tracking'])) {
        $_SESSION['session_tracking'] = [
            'user_id' => $userId,
            'session_id' => $currentSessionId,
            'created_at' => time(),
            'last_activity' => time()
        ];
    }
    
    // Update last activity
    $_SESSION['session_tracking']['last_activity'] = time();
}

/**
 * Redirect to login with message
 */
function redirectToLogin($message = null)
{
    $currentPage = $_SERVER['REQUEST_URI'] ?? '';
    $redirectParam = !empty($currentPage) ? '?redirect=' . urlencode($currentPage) : '';
    
    if ($message) {
        $redirectParam .= (!empty($redirectParam) ? '&' : '?') . 'message=' . urlencode($message);
    }
    
    header('Location: login.php' . $redirectParam);
    exit;
}

/**
 * Validate form submission with CSRF protection
 * 
 * @param string $formId Form identifier
 * @param array $requiredFields Required form fields
 * @return array Validation result
 */
function validateFormSubmission($formId, $requiredFields = [])
{
    $result = [
        'valid' => false,
        'errors' => [],
        'data' => []
    ];
    
    // Check if POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $result['errors']['general'] = 'Invalid request method';
        return $result;
    }
    
    // Validate CSRF token
    if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '', $formId)) {
        $result['errors']['csrf'] = 'Security token validation failed. Please refresh and try again.';
        return $result;
    }
    
    // Sanitize input data
    $sanitizedData = SecuritySanitizer::sanitize($_POST, 'html');
    
    // Check required fields
    foreach ($requiredFields as $field) {
        if (empty(trim($sanitizedData[$field] ?? ''))) {
            $result['errors'][$field] = ucfirst($field) . ' is required';
        }
    }
    
    $result['valid'] = empty($result['errors']);
    $result['data'] = $sanitizedData;
    
    return $result;
}

/**
 * Generate secure form with CSRF protection
 * 
 * @param string $formId Form identifier
 * @param string $action Form action URL
 * @param string $method Form method (default: POST)
 * @param array $attributes Additional form attributes
 * @return string Form opening tag with CSRF token
 */
function generateSecureForm($formId, $action = '', $method = 'POST', $attributes = [])
{
    $attributeString = '';
    foreach ($attributes as $key => $value) {
        $attributeString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    $form = '<form method="' . htmlspecialchars($method) . '" action="' . htmlspecialchars($action) . '"' . $attributeString . '>';
    $form .= CSRFProtection::getHiddenField($formId);
    
    return $form;
}

/**
 * Secure file upload handler
 * 
 * @param string $fieldName File input field name
 * @param array $options Upload options
 * @return array Upload result
 */
function handleSecureFileUpload($fieldName, $options = [])
{
    if (!isset($_FILES[$fieldName])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    $file = $_FILES[$fieldName];
    $validation = SecuritySanitizer::validateFileUpload($file, $options);
    
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    // Generate secure filename
    $extension = pathinfo($validation['sanitized_name'], PATHINFO_EXTENSION);
    $secureFilename = uniqid('upload_', true) . '.' . $extension;
    
    return [
        'success' => true,
        'original_name' => $validation['original_name'],
        'secure_filename' => $secureFilename,
        'size' => $validation['size'],
        'tmp_name' => $validation['tmp_name']
    ];
}

/**
 * Log security event
 * 
 * @param string $event Event type
 * @param array $details Event details
 */
function logSecurityEvent($event, $details = [])
{
    $securityDetails = array_merge($details, [
        'event_type' => $event,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => getUserId(),
        'session_id' => session_id()
    ]);
    
    ErrorHandler::logError("Security Event: {$event}", $securityDetails);
}

/**
 * Check if current request is AJAX
 * 
 * @return bool True if AJAX request
 */
function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send secure JSON response
 * 
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 */
function sendSecureJsonResponse($data, $statusCode = 200)
{
    // Set security headers for JSON response
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    http_response_code($statusCode);
    
    // Sanitize data for JSON output
    $sanitizedData = SecuritySanitizer::sanitize($data, 'json');
    
    echo json_encode($sanitizedData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    exit;
}

/**
 * Validate and sanitize redirect URL
 * 
 * @param string $url URL to validate
 * @param string $default Default URL if validation fails
 * @return string Safe redirect URL
 */
function validateRedirectUrl($url, $default = 'dashboard.php')
{
    if (empty($url)) {
        return $default;
    }
    
    // Sanitize URL
    $sanitizedUrl = SecuritySanitizer::sanitize($url, 'url');
    
    if (empty($sanitizedUrl)) {
        return $default;
    }
    
    // Parse URL to check components
    $parsed = parse_url($sanitizedUrl);
    
    // Only allow relative URLs or same-host URLs
    if (isset($parsed['host']) && $parsed['host'] !== $_SERVER['HTTP_HOST']) {
        return $default;
    }
    
    // Prevent javascript: and data: URLs
    if (isset($parsed['scheme']) && !in_array($parsed['scheme'], ['http', 'https'])) {
        return $default;
    }
    
    return $sanitizedUrl;
}

/**
 * Generate nonce for inline scripts/styles
 * 
 * @return string Generated nonce
 */
function generateNonce()
{
    if (!isset($_SESSION['csp_nonce'])) {
        $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
    }
    
    return $_SESSION['csp_nonce'];
}

// Auto-initialize security for pages that include this file
// This can be overridden by calling initializePageSecurity() manually
if (!defined('SECURITY_MANUAL_INIT')) {
    initializePageSecurity();
}
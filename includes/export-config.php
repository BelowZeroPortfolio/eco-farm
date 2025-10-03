<?php

/**
 * Export Security Configuration
 * 
 * Defines security settings and limits for export functionality
 */

// Export security settings
define('EXPORT_MAX_ROWS', 10000);
define('EXPORT_MAX_DATE_RANGE_DAYS', 365);
define('EXPORT_ALLOWED_FORMATS', ['csv', 'pdf']);
define('EXPORT_ALLOWED_REPORT_TYPES', ['sensor', 'pest']);

// File security settings
define('EXPORT_MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('EXPORT_TEMP_DIR', sys_get_temp_dir());
define('EXPORT_LOG_FILE', 'logs/export_activity.log');

// Rate limiting settings (per user per hour)
define('EXPORT_RATE_LIMIT', 50);
define('EXPORT_RATE_WINDOW', 3600); // 1 hour in seconds

// User role permissions
define('EXPORT_PERMISSIONS', [
    'admin' => ['csv', 'pdf', 'unlimited_rows'],
    'student' => ['csv'],
    'farmer' => ['csv', 'pdf']
]);

/**
 * Check if user has permission for export format
 */
function hasExportPermission($userRole, $format)
{
    $permissions = EXPORT_PERMISSIONS[$userRole] ?? [];
    return in_array($format, $permissions);
}

/**
 * Get max rows allowed for user role
 */
function getMaxRowsForUser($userRole)
{
    $permissions = EXPORT_PERMISSIONS[$userRole] ?? [];
    if (in_array('unlimited_rows', $permissions)) {
        return PHP_INT_MAX;
    }
    return EXPORT_MAX_ROWS;
}

/**
 * Check export rate limit for user
 */
function checkExportRateLimit($userId)
{
    $logFile = EXPORT_LOG_FILE;
    if (!file_exists($logFile)) {
        return true; // No log file, allow export
    }
    
    $currentTime = time();
    $windowStart = $currentTime - EXPORT_RATE_WINDOW;
    $exportCount = 0;
    
    // Read log file and count exports in time window
    $handle = fopen($logFile, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (strpos($line, "User: {$userId}") !== false && 
                strpos($line, 'Success: YES') !== false) {
                
                // Extract timestamp from log line
                if (preg_match('/Time: (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime >= $windowStart) {
                        $exportCount++;
                    }
                }
            }
        }
        fclose($handle);
    }
    
    return $exportCount < EXPORT_RATE_LIMIT;
}

/**
 * Sanitize filename for security
 */
function sanitizeExportFilename($filename)
{
    // Remove any path traversal attempts
    $filename = basename($filename);
    
    // Remove or replace dangerous characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Limit length
    if (strlen($filename) > 100) {
        $filename = substr($filename, 0, 100);
    }
    
    return $filename;
}

/**
 * Validate export request parameters
 */
function validateExportSecurity($userId, $userRole, $format, $reportType, $startDate, $endDate)
{
    $errors = [];
    
    // Check user permissions
    if (!hasExportPermission($userRole, $format)) {
        $errors[] = "You don't have permission to export in {$format} format.";
    }
    
    // Check rate limiting
    if (!checkExportRateLimit($userId)) {
        $errors[] = "Export rate limit exceeded. Please try again later.";
    }
    
    // Validate format
    if (!in_array($format, EXPORT_ALLOWED_FORMATS)) {
        $errors[] = "Invalid export format.";
    }
    
    // Validate report type
    if (!in_array($reportType, EXPORT_ALLOWED_REPORT_TYPES)) {
        $errors[] = "Invalid report type.";
    }
    
    // Validate date range
    $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
    if ($daysDiff > EXPORT_MAX_DATE_RANGE_DAYS) {
        $errors[] = "Date range cannot exceed " . EXPORT_MAX_DATE_RANGE_DAYS . " days.";
    }
    
    return $errors;
}
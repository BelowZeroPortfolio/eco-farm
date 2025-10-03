<?php

/**
 * Error Handler and Validation Utilities
 * 
 * Provides centralized error handling, logging, and validation functions
 * for the IoT Farm Monitoring System
 */

/**
 * Error Handler Class
 * 
 * Handles application errors, logging, and user-friendly error display
 */
class ErrorHandler
{
    private static $logFile = 'logs/application.log';
    private static $errorCounts = [];

    /**
     * Initialize error handler
     */
    public static function init()
    {
        // Ensure logs directory exists
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);

        // Register shutdown function for fatal errors
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    /**
     * Handle PHP errors
     */
    public static function handleError($severity, $message, $file, $line)
    {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorType = self::getErrorType($severity);
        $context = [
            'file' => $file,
            'line' => $line,
            'severity' => $severity,
            'type' => $errorType
        ];

        self::logError($message, $context);

        // For development, show errors; for production, log only
        if (self::isDevelopmentMode()) {
            echo "<div class='alert alert-danger'>Error: {$message} in {$file} on line {$line}</div>";
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception)
    {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'type' => 'Exception'
        ];

        self::logError($exception->getMessage(), $context);

        if (self::isDevelopmentMode()) {
            echo "<div class='alert alert-danger'>Exception: " . $exception->getMessage() . "</div>";
        } else {
            // Show generic error message in production
            self::showUserFriendlyError('system_error');
        }
    }

    /**
     * Handle fatal errors
     */
    public static function handleFatalError()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $context = [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => 'Fatal Error'
            ];

            self::logError($error['message'], $context);

            if (!self::isDevelopmentMode()) {
                self::showUserFriendlyError('fatal_error');
            }
        }
    }

    /**
     * Log error to file
     */
    public static function logError($message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $userId = function_exists('getUserId') ? (getUserId() ?? 'anonymous') : 'anonymous';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';

        $logEntry = [
            'timestamp' => $timestamp,
            'user_id' => $userId,
            'ip' => $ip,
            'request_uri' => $requestUri,
            'message' => $message,
            'context' => $context,
            'user_agent' => $userAgent
        ];

        $logLine = json_encode($logEntry) . PHP_EOL;

        // Attempt to write to log file
        if (@file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX) === false) {
            // Fallback to error_log if file write fails
            error_log("Application Error: {$message} | Context: " . json_encode($context));
        }

        // Track error frequency
        $errorKey = md5($message);
        self::$errorCounts[$errorKey] = (self::$errorCounts[$errorKey] ?? 0) + 1;
    }

    /**
     * Show user-friendly error message
     */
    public static function showUserFriendlyError($errorType, $details = null)
    {
        $messages = [
            'database_error' => 'We\'re experiencing database connectivity issues. Please try again in a few moments.',
            'validation_error' => 'Please check your input and try again.',
            'authentication_error' => 'Authentication failed. Please check your credentials.',
            'authorization_error' => 'You don\'t have permission to access this resource.',
            'file_error' => 'File operation failed. Please try again.',
            'network_error' => 'Network connectivity issue. Please check your connection.',
            'system_error' => 'A system error occurred. Our team has been notified.',
            'fatal_error' => 'A critical error occurred. Please contact support if this persists.',
            'session_error' => 'Your session has expired. Please log in again.'
        ];

        $message = $messages[$errorType] ?? 'An unexpected error occurred.';

        if ($details && self::isDevelopmentMode()) {
            $message .= " Details: {$details}";
        }

        return $message;
    }

    /**
     * Get error type string from severity level
     */
    private static function getErrorType($severity)
    {
        $types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        return $types[$severity] ?? 'Unknown Error';
    }

    /**
     * Check if in development mode
     */
    private static function isDevelopmentMode()
    {
        return defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true;
    }

    /**
     * Get error statistics
     */
    public static function getErrorStats()
    {
        return [
            'total_errors' => array_sum(self::$errorCounts),
            'unique_errors' => count(self::$errorCounts),
            'most_frequent' => self::$errorCounts ? max(self::$errorCounts) : 0
        ];
    }
}



/**
 * Database Error Handler
 * 
 * Handles database-specific errors and provides fallbacks
 */
class DatabaseErrorHandler
{
    /**
     * Handle database connection errors
     */
    public static function handleConnectionError($exception)
    {
        ErrorHandler::logError('Database connection failed: ' . $exception->getMessage(), [
            'type' => 'database_connection',
            'code' => $exception->getCode()
        ]);

        return [
            'success' => false,
            'message' => ErrorHandler::showUserFriendlyError('database_error'),
            'fallback_data' => self::getFallbackData()
        ];
    }

    /**
     * Handle database query errors
     */
    public static function handleQueryError($exception, $query = null)
    {
        ErrorHandler::logError('Database query failed: ' . $exception->getMessage(), [
            'type' => 'database_query',
            'query' => $query,
            'code' => $exception->getCode()
        ]);

        return [
            'success' => false,
            'message' => ErrorHandler::showUserFriendlyError('database_error'),
            'fallback_data' => []
        ];
    }

    /**
     * Get fallback data when database is unavailable
     */
    private static function getFallbackData()
    {
        return [
            'sensors' => [
                ['id' => 1, 'name' => 'Temperature Sensor 1', 'status' => 'offline', 'value' => 'N/A'],
                ['id' => 2, 'name' => 'Humidity Sensor 1', 'status' => 'offline', 'value' => 'N/A']
            ],
            'alerts' => [
                ['id' => 1, 'type' => 'System', 'message' => 'Database connectivity issue', 'severity' => 'high']
            ]
        ];
    }

    /**
     * Check database health
     */
    public static function checkDatabaseHealth()
    {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            ErrorHandler::logError('Database health check failed: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Form Error Display Helper
 * 
 * Provides consistent error display across forms
 */
class FormErrorDisplay
{
    /**
     * Display field error
     */
    public static function fieldError($errors, $field)
    {
        if (isset($errors[$field])) {
            return '<div class="text-red-600 text-sm mt-1">' . htmlspecialchars($errors[$field]) . '</div>';
        }
        return '';
    }

    /**
     * Display general error alert
     */
    public static function errorAlert($message, $type = 'error')
    {
        $alertClass = $type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
        $icon = $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

        return '<div class="mb-6 ' . $alertClass . ' border rounded-lg p-4 animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas ' . $icon . ' mr-3"></i>
                        <span>' . htmlspecialchars($message) . '</span>
                    </div>
                </div>';
    }

    /**
     * Display validation summary
     */
    public static function validationSummary($errors)
    {
        if (empty($errors)) {
            return '';
        }

        $html = '<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-3 mt-1"></i>
                        <div>
                            <h4 class="text-red-800 font-medium mb-2">Please correct the following errors:</h4>
                            <ul class="text-red-700 text-sm space-y-1">';

        foreach ($errors as $field => $error) {
            $html .= '<li>• ' . htmlspecialchars($error) . '</li>';
        }

        $html .= '</ul></div></div></div>';

        return $html;
    }
}

// Initialize error handler
ErrorHandler::init();

// Define development mode (set to false in production)
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', true);
}



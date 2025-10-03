<?php

/**
 * Validation Rules for IoT Farm Monitoring System
 * 
 * Defines validation rules for different forms and data types
 * used throughout the application
 */

/**
 * Form validation rules configuration
 */
class ValidationRules
{
    /**
     * Get validation rules for login form
     */
    public static function getLoginRules()
    {
        return [
            'username' => [
                'required' => 'Username is required',
                'min_length' => ['length' => 3, 'message' => 'Username must be at least 3 characters'],
                'max_length' => ['length' => 50, 'message' => 'Username cannot exceed 50 characters'],
                'username' => 'Username format is invalid'
            ],
            'password' => [
                'required' => 'Password is required',
                'min_length' => ['length' => 1, 'message' => 'Password cannot be empty']
            ]
        ];
    }

    /**
     * Get validation rules for user profile update
     */
    public static function getProfileUpdateRules()
    {
        return [
            'username' => [
                'required' => 'Username is required',
                'username' => 'Username format is invalid',
                'min_length' => ['length' => 3, 'message' => 'Username must be at least 3 characters'],
                'max_length' => ['length' => 50, 'message' => 'Username cannot exceed 50 characters']
            ],
            'email' => [
                'required' => 'Email is required',
                'email' => 'Please enter a valid email address',
                'max_length' => ['length' => 100, 'message' => 'Email cannot exceed 100 characters']
            ]
        ];
    }

    /**
     * Get validation rules for password change
     */
    public static function getPasswordChangeRules()
    {
        return [
            'current_password' => [
                'required' => 'Current password is required'
            ],
            'new_password' => [
                'required' => 'New password is required',
                'password' => ['min_length' => 6, 'message' => 'New password must be at least 6 characters long']
            ],
            'confirm_password' => [
                'required' => 'Please confirm your new password'
            ]
        ];
    }

    /**
     * Get validation rules for user creation (admin)
     */
    public static function getUserCreationRules()
    {
        return [
            'username' => [
                'required' => 'Username is required',
                'username' => 'Username format is invalid',
                'min_length' => ['length' => 3, 'message' => 'Username must be at least 3 characters'],
                'max_length' => ['length' => 50, 'message' => 'Username cannot exceed 50 characters']
            ],
            'email' => [
                'required' => 'Email is required',
                'email' => 'Please enter a valid email address',
                'max_length' => ['length' => 100, 'message' => 'Email cannot exceed 100 characters']
            ],
            'password' => [
                'required' => 'Password is required',
                'password' => ['min_length' => 6, 'message' => 'Password must be at least 6 characters long']
            ],
            'role' => [
                'required' => 'Role is required',
                'in' => ['values' => ['admin', 'farmer', 'student'], 'message' => 'Invalid role selected']
            ]
        ];
    }

    /**
     * Get validation rules for settings forms
     */
    public static function getAppearanceSettingsRules()
    {
        return [
            'theme' => [
                'in' => ['values' => ['light', 'dark'], 'message' => 'Invalid theme selected']
            ],
            'dashboard_layout' => [
                'in' => ['values' => ['grid', 'list', 'compact'], 'message' => 'Invalid layout selected']
            ],
            'chart_style' => [
                'in' => ['values' => ['modern', 'classic', 'minimal'], 'message' => 'Invalid chart style selected']
            ]
        ];
    }

    /**
     * Get validation rules for notification settings
     */
    public static function getNotificationSettingsRules()
    {
        return [
            'alert_frequency' => [
                'in' => ['values' => ['immediate', 'hourly', 'daily', 'weekly'], 'message' => 'Invalid alert frequency']
            ],
            'quiet_hours_start' => [
                'pattern' => ['pattern' => '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', 'message' => 'Invalid time format for start time']
            ],
            'quiet_hours_end' => [
                'pattern' => ['pattern' => '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', 'message' => 'Invalid time format for end time']
            ]
        ];
    }

    /**
     * Get validation rules for pest alert creation
     */
    public static function getPestAlertRules()
    {
        return [
            'pest_type' => [
                'required' => 'Pest type is required',
                'max_length' => ['length' => 100, 'message' => 'Pest type cannot exceed 100 characters']
            ],
            'location' => [
                'required' => 'Location is required',
                'max_length' => ['length' => 100, 'message' => 'Location cannot exceed 100 characters']
            ],
            'severity' => [
                'required' => 'Severity is required',
                'in' => ['values' => ['low', 'medium', 'high', 'critical'], 'message' => 'Invalid severity level']
            ],
            'description' => [
                'max_length' => ['length' => 1000, 'message' => 'Description cannot exceed 1000 characters']
            ]
        ];
    }

    /**
     * Get validation rules for sensor data
     */
    public static function getSensorDataRules()
    {
        return [
            'sensor_name' => [
                'required' => 'Sensor name is required',
                'max_length' => ['length' => 100, 'message' => 'Sensor name cannot exceed 100 characters']
            ],
            'sensor_type' => [
                'required' => 'Sensor type is required',
                'in' => ['values' => ['temperature', 'humidity', 'soil_moisture'], 'message' => 'Invalid sensor type']
            ],
            'location' => [
                'required' => 'Location is required',
                'max_length' => ['length' => 100, 'message' => 'Location cannot exceed 100 characters']
            ],
            'value' => [
                'required' => 'Sensor value is required',
                'numeric' => 'Sensor value must be numeric'
            ],
            'unit' => [
                'required' => 'Unit is required',
                'max_length' => ['length' => 20, 'message' => 'Unit cannot exceed 20 characters']
            ]
        ];
    }

    /**
     * Get validation rules for report generation
     */
    public static function getReportGenerationRules()
    {
        return [
            'report_type' => [
                'required' => 'Report type is required',
                'in' => ['values' => ['sensor', 'pest', 'combined'], 'message' => 'Invalid report type']
            ],
            'date_from' => [
                'required' => 'Start date is required',
                'pattern' => ['pattern' => '/^\d{4}-\d{2}-\d{2}$/', 'message' => 'Invalid date format (YYYY-MM-DD)']
            ],
            'date_to' => [
                'required' => 'End date is required',
                'pattern' => ['pattern' => '/^\d{4}-\d{2}-\d{2}$/', 'message' => 'Invalid date format (YYYY-MM-DD)']
            ],
            'format' => [
                'in' => ['values' => ['html', 'csv', 'pdf'], 'message' => 'Invalid export format']
            ]
        ];
    }

    /**
     * Get validation rules for search and filtering
     */
    public static function getSearchRules()
    {
        return [
            'search_term' => [
                'max_length' => ['length' => 100, 'message' => 'Search term cannot exceed 100 characters']
            ],
            'filter_type' => [
                'in' => ['values' => ['all', 'sensors', 'alerts', 'users'], 'message' => 'Invalid filter type']
            ],
            'sort_by' => [
                'in' => ['values' => ['date', 'name', 'type', 'status'], 'message' => 'Invalid sort field']
            ],
            'sort_order' => [
                'in' => ['values' => ['asc', 'desc'], 'message' => 'Invalid sort order']
            ]
        ];
    }

    /**
     * Validate file upload
     */
    public static function getFileUploadRules()
    {
        return [
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'csv', 'xlsx'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'required' => false
        ];
    }

    /**
     * Get common sanitization rules
     */
    public static function getSanitizationRules()
    {
        return [
            'strip_tags' => ['username', 'email', 'sensor_name', 'location'],
            'trim' => ['username', 'email', 'sensor_name', 'location', 'pest_type'],
            'lowercase' => ['email'],
            'uppercase' => [],
            'numeric_only' => ['sensor_value'],
            'alpha_numeric' => ['username']
        ];
    }

    /**
     * Get security validation rules
     */
    public static function getSecurityRules()
    {
        return [
            'csrf_required' => true,
            'rate_limiting' => [
                'login' => ['attempts' => 5, 'window' => 300], // 5 attempts per 5 minutes
                'password_reset' => ['attempts' => 3, 'window' => 3600], // 3 attempts per hour
                'form_submission' => ['attempts' => 10, 'window' => 60] // 10 submissions per minute
            ],
            'input_length_limits' => [
                'max_input_vars' => 1000,
                'max_post_size' => '10M',
                'max_execution_time' => 30
            ]
        ];
    }
}

/**
 * Custom validation functions for specific business logic
 */
class CustomValidators
{
    /**
     * Validate sensor value range based on sensor type
     */
    public static function validateSensorValue($value, $sensorType)
    {
        $ranges = [
            'temperature' => ['min' => -50, 'max' => 100],
            'humidity' => ['min' => 0, 'max' => 100],
            'soil_moisture' => ['min' => 0, 'max' => 100]
        ];

        if (!isset($ranges[$sensorType])) {
            return ['valid' => false, 'message' => 'Unknown sensor type'];
        }

        $range = $ranges[$sensorType];
        if ($value < $range['min'] || $value > $range['max']) {
            return [
                'valid' => false, 
                'message' => "Value must be between {$range['min']} and {$range['max']} for {$sensorType} sensors"
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate date range
     */
    public static function validateDateRange($startDate, $endDate)
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $now = time();

        if ($start === false || $end === false) {
            return ['valid' => false, 'message' => 'Invalid date format'];
        }

        if ($start > $end) {
            return ['valid' => false, 'message' => 'Start date must be before end date'];
        }

        if ($start > $now) {
            return ['valid' => false, 'message' => 'Start date cannot be in the future'];
        }

        // Limit range to 2 years
        $maxRange = 2 * 365 * 24 * 60 * 60; // 2 years in seconds
        if (($end - $start) > $maxRange) {
            return ['valid' => false, 'message' => 'Date range cannot exceed 2 years'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate pest severity based on type
     */
    public static function validatePestSeverity($pestType, $severity)
    {
        $criticalPests = ['locust', 'army_worm', 'blight'];
        
        if (in_array(strtolower($pestType), $criticalPests) && $severity === 'low') {
            return [
                'valid' => false, 
                'message' => 'This pest type typically requires medium or higher severity rating'
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate user role assignment
     */
    public static function validateRoleAssignment($currentUserRole, $targetRole)
    {
        // Only admins can assign admin role
        if ($targetRole === 'admin' && $currentUserRole !== 'admin') {
            return ['valid' => false, 'message' => 'Only administrators can assign admin role'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $rules)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            if ($rules['required']) {
                return ['valid' => false, 'message' => 'File upload is required'];
            }
            return ['valid' => true, 'message' => ''];
        }

        // Check file size
        if ($file['size'] > $rules['max_size']) {
            $maxSizeMB = round($rules['max_size'] / (1024 * 1024), 2);
            return ['valid' => false, 'message' => "File size cannot exceed {$maxSizeMB}MB"];
        }

        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $rules['allowed_types'])) {
            $allowedTypes = implode(', ', $rules['allowed_types']);
            return ['valid' => false, 'message' => "File type not allowed. Allowed types: {$allowedTypes}"];
        }

        // Check for malicious content (basic check)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'text/csv', 'application/vnd.ms-excel'
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            return ['valid' => false, 'message' => 'File content type not allowed'];
        }

        return ['valid' => true, 'message' => ''];
    }
}

/**
 * Rate limiting utility
 */

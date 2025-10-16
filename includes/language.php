<?php

/**
 * Centralized Language System
 * 
 * Provides multilingual support across the entire application
 */

// Get current user language preference
function getCurrentLanguage()
{
    return $_SESSION['user_language'] ?? 'en';
}

// Set user language preference
function setUserLanguage($language)
{
    $_SESSION['user_language'] = $language;
}

// Get all available languages
function getAvailableLanguages()
{
    return [
        'en' => 'English',
        'tl' => 'Tagalog'
    ];
}

// Translation dictionary
function getTranslations()
{
    return [
        'en' => [
            // Navigation
            'dashboard' => 'Dashboard',
            'sensors' => 'Sensors',
            'pest_detection' => 'Pest Detection',
            'camera_management' => 'Camera Management',
            'notifications' => 'Notifications',
            'reports' => 'Reports',
            'settings' => 'Settings',
            'profile' => 'Profile',
            'user_management' => 'User Management',

            // Common UI Elements
            'save' => 'Save',
            'cancel' => 'Cancel',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'add' => 'Add',
            'search' => 'Search',
            'filter' => 'Filter',
            'export' => 'Export',
            'import' => 'Import',
            'refresh' => 'Refresh',
            'loading' => 'Loading...',
            'no_data' => 'No data available',
            'error' => 'Error',
            'success' => 'Success',
            'warning' => 'Warning',
            'info' => 'Information',

            // Dashboard
            'welcome' => 'Welcome',
            'overview' => 'Overview',
            'quick_actions' => 'Quick Actions',
            'recent_activity' => 'Recent Activity',
            'system_health' => 'System Health',
            'live_conditions' => 'Live Conditions',
            'temperature' => 'Temperature',
            'humidity' => 'Humidity',
            'soil_moisture' => 'Soil Moisture',
            'sensors_online' => 'Sensors Online',
            'cameras_active' => 'Cameras Active',
            'alerts_today' => 'Alerts Today',

            // Sensors
            'sensor_data' => 'Sensor Data',
            'real_time_monitoring' => 'Real-time Monitoring',
            'sensor_readings' => 'Sensor Readings',
            'data_collection' => 'Data Collection',
            'sensor_status' => 'Sensor Status',
            'online' => 'Online',
            'offline' => 'Offline',
            'last_reading' => 'Last Reading',
            'average_value' => 'Average Value',

            // Pest Detection
            'ai_detection' => 'AI Detection',
            'upload_image' => 'Upload Image',
            'analyze_image' => 'Analyze Image',
            'detection_results' => 'Detection Results',
            'no_pests_detected' => 'No pests detected',
            'pest_found' => 'Pest detected',
            'confidence_level' => 'Confidence Level',
            'recommended_action' => 'Recommended Action',

            // Camera Management
            'camera_feeds' => 'Camera Feeds',
            'live_stream' => 'Live Stream',
            'camera_settings' => 'Camera Settings',
            'recording' => 'Recording',
            'motion_detection' => 'Motion Detection',
            'camera_status' => 'Camera Status',

            // Notifications
            'notification_settings' => 'Notification Settings',
            'email_notifications' => 'Email Notifications',
            'system_alerts' => 'System Alerts',
            'daily_reports' => 'Daily Reports',
            'pest_alerts' => 'Pest Alerts',
            'alert_thresholds' => 'Alert Thresholds',
            'min_value' => 'Min Value',
            'max_value' => 'Max Value',

            // Settings & Preferences
            'user_preferences' => 'User Preferences',
            'theme_display' => 'Theme & Display',
            'dark_mode' => 'Dark Mode',
            'compact_view' => 'Compact View',
            'language_region' => 'Language & Region',
            'language' => 'Language',
            'timezone' => 'Timezone',
            'save_preferences' => 'Save Preferences',
            'english' => 'English',
            'tagalog' => 'Tagalog',

            // Profile
            'profile_overview' => 'Profile Overview',
            'account_settings' => 'Account Settings',
            'security' => 'Security',
            'change_password' => 'Change Password',
            'current_password' => 'Current Password',
            'new_password' => 'New Password',
            'confirm_password' => 'Confirm Password',
            'update_profile' => 'Update Profile',
            'username' => 'Username',
            'email' => 'Email',
            'role' => 'Role',
            'member_since' => 'Member Since',
            'last_login' => 'Last Login',
            'account_status' => 'Account Status',
            'active' => 'Active',
            'inactive' => 'Inactive',

            // Reports
            'generate_report' => 'Generate Report',
            'report_type' => 'Report Type',
            'date_range' => 'Date Range',
            'from_date' => 'From Date',
            'to_date' => 'To Date',
            'download_pdf' => 'Download PDF',
            'download_csv' => 'Download CSV',
            'sensor_report' => 'Sensor Report',
            'pest_report' => 'Pest Report',
            'system_report' => 'System Report',

            // Time & Dates
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',

            // Status Messages
            'data_updated' => 'Data updated successfully',
            'settings_saved' => 'Settings saved successfully',
            'profile_updated' => 'Profile updated successfully',
            'password_changed' => 'Password changed successfully',
            'operation_failed' => 'Operation failed',
            'invalid_input' => 'Invalid input provided',
            'access_denied' => 'Access denied',
            'session_expired' => 'Session expired',
        ],

        'tl' => [
            // Navigation
            'dashboard' => 'Dashboard',
            'sensors' => 'Mga Sensor',
            'pest_detection' => 'Paghahanap ng Peste',
            'camera_management' => 'Pamamahala ng Camera',
            'notifications' => 'Mga Abiso',
            'reports' => 'Mga Ulat',
            'settings' => 'Mga Setting',
            'profile' => 'Profile',
            'user_management' => 'Pamamahala ng User',

            // Common UI Elements
            'save' => 'I-save',
            'cancel' => 'Kanselahin',
            'edit' => 'I-edit',
            'delete' => 'Tanggalin',
            'add' => 'Magdagdag',
            'search' => 'Maghanap',
            'filter' => 'I-filter',
            'export' => 'I-export',
            'import' => 'I-import',
            'refresh' => 'I-refresh',
            'loading' => 'Naglo-load...',
            'no_data' => 'Walang available na data',
            'error' => 'Error',
            'success' => 'Tagumpay',
            'warning' => 'Babala',
            'info' => 'Impormasyon',

            // Dashboard
            'welcome' => 'Maligayang pagdating',
            'overview' => 'Pangkalahatang Tingin',
            'quick_actions' => 'Mabibiling Aksyon',
            'recent_activity' => 'Kamakailang Aktibidad',
            'system_health' => 'Kalusugan ng Sistema',
            'live_conditions' => 'Live na Kondisyon',
            'temperature' => 'Temperatura',
            'humidity' => 'Humidity',
            'soil_moisture' => 'Kahalumigmigan ng Lupa',
            'sensors_online' => 'Mga Sensor na Online',
            'cameras_active' => 'Mga Camera na Aktibo',
            'alerts_today' => 'Mga Babala Ngayong Araw',

            // Sensors
            'sensor_data' => 'Data ng Sensor',
            'real_time_monitoring' => 'Real-time na Pagsubaybay',
            'sensor_readings' => 'Mga Reading ng Sensor',
            'data_collection' => 'Pagkolekta ng Data',
            'sensor_status' => 'Status ng Sensor',
            'online' => 'Online',
            'offline' => 'Offline',
            'last_reading' => 'Huling Reading',
            'average_value' => 'Average na Halaga',

            // Pest Detection
            'ai_detection' => 'AI Detection',
            'upload_image' => 'Mag-upload ng Larawan',
            'analyze_image' => 'Suriin ang Larawan',
            'detection_results' => 'Mga Resulta ng Detection',
            'no_pests_detected' => 'Walang nahanap na peste',
            'pest_found' => 'May nahanap na peste',
            'confidence_level' => 'Level ng Kumpiyansa',
            'recommended_action' => 'Inirerekomendang Aksyon',

            // Camera Management
            'camera_feeds' => 'Mga Camera Feed',
            'live_stream' => 'Live Stream',
            'camera_settings' => 'Mga Setting ng Camera',
            'recording' => 'Nag-rerecord',
            'motion_detection' => 'Detection ng Paggalaw',
            'camera_status' => 'Status ng Camera',

            // Notifications
            'notification_settings' => 'Mga Setting ng Abiso',
            'email_notifications' => 'Mga Abiso sa Email',
            'system_alerts' => 'Mga Babala ng Sistema',
            'daily_reports' => 'Araw-araw na Ulat',
            'pest_alerts' => 'Mga Babala sa Peste',
            'alert_thresholds' => 'Mga Threshold ng Babala',
            'min_value' => 'Pinakamababang Halaga',
            'max_value' => 'Pinakamataas na Halaga',

            // Settings & Preferences
            'user_preferences' => 'Mga Kagustuhan ng User',
            'theme_display' => 'Theme at Display',
            'dark_mode' => 'Dark Mode',
            'compact_view' => 'Compact na View',
            'language_region' => 'Wika at Rehiyon',
            'language' => 'Wika',
            'timezone' => 'Timezone',
            'save_preferences' => 'I-save ang mga Kagustuhan',
            'english' => 'Ingles',
            'tagalog' => 'Tagalog',

            // Profile
            'profile_overview' => 'Pangkalahatang Tingin sa Profile',
            'account_settings' => 'Mga Setting ng Account',
            'security' => 'Seguridad',
            'change_password' => 'Palitan ang Password',
            'current_password' => 'Kasalukuyang Password',
            'new_password' => 'Bagong Password',
            'confirm_password' => 'Kumpirmahin ang Password',
            'update_profile' => 'I-update ang Profile',
            'username' => 'Username',
            'email' => 'Email',
            'role' => 'Tungkulin',
            'member_since' => 'Miyembro Simula',
            'last_login' => 'Huling Pag-login',
            'account_status' => 'Status ng Account',
            'active' => 'Aktibo',
            'inactive' => 'Hindi Aktibo',

            // Reports
            'generate_report' => 'Gumawa ng Ulat',
            'report_type' => 'Uri ng Ulat',
            'date_range' => 'Hanay ng Petsa',
            'from_date' => 'Mula sa Petsa',
            'to_date' => 'Hanggang sa Petsa',
            'download_pdf' => 'I-download ang PDF',
            'download_csv' => 'I-download ang CSV',
            'sensor_report' => 'Ulat ng Sensor',
            'pest_report' => 'Ulat ng Peste',
            'system_report' => 'Ulat ng Sistema',

            // Time & Dates
            'today' => 'Ngayong Araw',
            'yesterday' => 'Kahapon',
            'this_week' => 'Ngayong Linggo',
            'this_month' => 'Ngayong Buwan',
            'last_7_days' => 'Nakaraang 7 Araw',
            'last_30_days' => 'Nakaraang 30 Araw',

            // Status Messages
            'data_updated' => 'Matagumpay na na-update ang data',
            'settings_saved' => 'Matagumpay na na-save ang mga setting',
            'profile_updated' => 'Matagumpay na na-update ang profile',
            'password_changed' => 'Matagumpay na napalitan ang password',
            'operation_failed' => 'Nabigo ang operasyon',
            'invalid_input' => 'Hindi wastong input',
            'access_denied' => 'Ipinagkakaila ang access',
            'session_expired' => 'Nag-expire na ang session',
        ]
    ];
}

// Get translated text
function translate($key, $language = null)
{
    if ($language === null) {
        $language = getCurrentLanguage();
    }

    $translations = getTranslations();

    if (isset($translations[$language][$key])) {
        return $translations[$language][$key];
    }

    // Fallback to English if translation not found
    if ($language !== 'en' && isset($translations['en'][$key])) {
        return $translations['en'][$key];
    }

    // Return the key itself if no translation found
    return $key;
}

// Shorthand function for translation
function t($key, $language = null)
{
    return translate($key, $language);
}

// Generate JavaScript translations for frontend
function generateJSTranslations($language = null)
{
    if ($language === null) {
        $language = getCurrentLanguage();
    }

    $translations = getTranslations();
    $jsTranslations = isset($translations[$language]) ? $translations[$language] : $translations['en'];

    return json_encode($jsTranslations);
}

// Generate language selector HTML
function generateLanguageSelector($currentLanguage = null)
{
    if ($currentLanguage === null) {
        $currentLanguage = getCurrentLanguage();
    }

    $languages = getAvailableLanguages();
    $html = '<select name="language" id="language-select" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">';

    foreach ($languages as $code => $name) {
        $selected = ($code === $currentLanguage) ? 'selected' : '';
        $html .= "<option value=\"{$code}\" {$selected}>{$name}</option>";
    }

    $html .= '</select>';
    return $html;
}

<?php

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'farm_database');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection using PDO with enhanced error handling
 * 
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDatabaseConnection()
{
    static $pdo = null;
    static $connectionAttempts = 0;
    static $lastAttemptTime = 0;

    if ($pdo === null) {
        $currentTime = time();

        // Implement connection retry logic with backoff
        if ($connectionAttempts > 0 && ($currentTime - $lastAttemptTime) < 30) {
            throw new PDOException("Database connection temporarily unavailable. Please try again later.");
        }

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10, // 10 second timeout
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $connectionAttempts = 0; // Reset on successful connection
        } catch (PDOException $e) {
            $connectionAttempts++;
            $lastAttemptTime = $currentTime;

            ErrorHandler::logError("Database connection failed (attempt {$connectionAttempts}): " . $e->getMessage(), [
                'host' => DB_HOST,
                'database' => DB_NAME,
                'error_code' => $e->getCode()
            ]);

            // Provide user-friendly error message
            throw new PDOException(ErrorHandler::showUserFriendlyError('database_error'));
        }
    }

    return $pdo;
}

/**
 * Execute a SQL file
 * 
 * @param string $filename Path to SQL file
 * @return bool Success status
 */
function executeSqlFile($filename)
{
    try {
        $pdo = getDatabaseConnection();
        $sql = file_get_contents($filename);

        if ($sql === false) {
            throw new Exception("Could not read SQL file: " . $filename);
        }

        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function ($stmt) {
                return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
            }
        );

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }

        return true;
    } catch (Exception $e) {
        error_log("SQL execution failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize database with schema and sample data
 * 
 * @return bool Success status
 */
function initializeDatabase()
{
    $schemaFile = __DIR__ . '/../database/schema.sql';
    $sampleDataFile = __DIR__ . '/../database/sample_data.sql';

    try {
        // Execute schema first
        if (!executeSqlFile($schemaFile)) {
            throw new Exception("Failed to execute schema file");
        }

        // Then execute sample data
        if (!executeSqlFile($sampleDataFile)) {
            throw new Exception("Failed to execute sample data file");
        }

        return true;
    } catch (Exception $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if database tables exist
 * 
 * @return bool True if all required tables exist
 */
function checkDatabaseTables()
{
    try {
        $pdo = getDatabaseConnection();
        $requiredTables = ['users', 'sensors', 'sensor_readings', 'pest_alerts', 'user_settings'];

        foreach ($requiredTables as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);

            if ($stmt->rowCount() === 0) {
                return false;
            }
        }

        return true;
    } catch (Exception $e) {
        error_log("Database check failed: " . $e->getMessage());
        return false;
    }
}


// Create global PDO connection for backward compatibility
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    // Handle connection error gracefully
    error_log("Failed to establish database connection: " . $e->getMessage());
    $pdo = null;
}

/**
 * Authentication and Role Management Functions
 */

/**
 * Authenticate user with username and password
 * 
 * @param string $username User's username
 * @param string $password User's password (plain text)
 * @return array|false User data array on success, false on failure
 */
function authenticateUser($username, $password)
{
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare("
            SELECT id, username, password, email, role, status, last_login 
            FROM users 
            WHERE username = ? AND status = 'active'
        ");
        $stmt->execute([trim($username)]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Update last login timestamp
            updateLastLogin($user['id']);

            // Remove password from returned data
            unset($user['password']);
            return $user;
        }

        return false;
    } catch (Exception $e) {
        error_log("Authentication failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user's last login timestamp
 * 
 * @param int $userId User ID
 * @return bool Success status
 */
function updateLastLogin($userId)
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Failed to update last login: " . $e->getMessage());
        return false;
    }
}

/**
 * Start user session after successful authentication
 * 
 * @param array $userData User data from authentication
 * @return void
 */
function startUserSession($userData)
{
    // Clear any existing session data
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['role'] = $userData['role'];
    $_SESSION['status'] = $userData['status'] ?? 'active';
    $_SESSION['last_login'] = $userData['last_login'];
    $_SESSION['login_time'] = time();
    
    error_log("startUserSession - User ID: " . $userData['id'] . ", Status: " . ($_SESSION['status'] ?? 'NULL'));
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

/**
 * Get current user's role
 * 
 * @return string|null User role or null if not logged in
 */
function getUserRole()
{
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user's ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's username
 * 
 * @return string|null Username or null if not logged in
 */
function getUsername()
{
    return $_SESSION['username'] ?? null;
}

/**
 * Get current user's email
 * 
 * @return string|null Email or null if not logged in
 */
function getUserEmail()
{
    return $_SESSION['email'] ?? null;
}

/**
 * Get current user's status
 * 
 * @return string|null Status or null if not logged in
 */
function getUserStatus()
{
    return $_SESSION['status'] ?? null;
}

/**
 * Check if current user has specific role
 * 
 * @param string $role Role to check ('admin', 'farmer', 'student')
 * @return bool True if user has the specified role
 */
function hasRole($role)
{
    return isLoggedIn() && getUserRole() === $role;
}

/**
 * Check if current user is admin
 * 
 * @return bool True if user is admin
 */
function isAdmin()
{
    return hasRole('admin');
}

/**
 * Check if current user is farmer
 * 
 * @return bool True if user is farmer
 */
function isFarmer()
{
    return hasRole('farmer');
}

/**
 * Check if current user is student
 * 
 * @return bool True if user is student
 */
function isStudent()
{
    return hasRole('student');
}

/**
 * Check if user has permission to access a specific page
 * 
 * @param string $page Page name to check access for
 * @return bool True if user has access
 */
function hasPageAccess($page)
{
    if (!isLoggedIn()) {
        return false;
    }

    $role = getUserRole();

    // Define page access permissions by role
    $permissions = [
        'admin' => [
            'dashboard',
            'sensors',
            'pest_detection',
            'user_management',
            'reports',
            'settings',
            'profile'
        ],
        'farmer' => [
            'dashboard',
            'sensors',
            'pest_detection',
            'profile'
        ],
        'student' => [
            'dashboard',
            'sensors',
            'pest_detection',
            'profile'
        ]
    ];

    return isset($permissions[$role]) && in_array($page, $permissions[$role]);
}

/**
 * Require user to be logged in, redirect to login if not
 * 
 * @param string $redirectTo URL to redirect to after login
 * @return void
 */
function requireLogin($redirectTo = null)
{
    if (!isLoggedIn()) {
        $redirect = $redirectTo ? '?redirect=' . urlencode($redirectTo) : '';
        header('Location: login.php' . $redirect);
        exit;
    }
}

/**
 * Require specific role, redirect or show error if not authorized
 * 
 * @param string|array $requiredRoles Single role or array of roles
 * @param string $errorPage Page to redirect to on access denied
 * @return void
 */
function requireRole($requiredRoles, $errorPage = 'dashboard.php')
{
    requireLogin();

    $userRole = getUserRole();
    $roles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];

    if (!in_array($userRole, $roles)) {
        header('Location: ' . $errorPage . '?error=access_denied');
        exit;
    }
}

/**
 * Require admin access
 * 
 * @param string $errorPage Page to redirect to on access denied
 * @return void
 */
function requireAdmin($errorPage = 'dashboard.php')
{
    requireRole('admin', $errorPage);
}

/**
 * Require page access permission
 * 
 * @param string $page Page name to check
 * @param string $errorPage Page to redirect to on access denied
 * @return void
 */
function requirePageAccess($page, $errorPage = 'dashboard.php')
{
    requireLogin();

    if (!hasPageAccess($page)) {
        header('Location: ' . $errorPage . '?error=access_denied');
        exit;
    }
}

/**
 * Logout user and destroy session
 * 
 * @return void
 */
function logoutUser()
{
    // Log logout event
    if (isLoggedIn()) {
        ErrorHandler::logError('User logged out', [
            'user_id' => getUserId(),
            'username' => getUsername(),
            'session_duration' => time() - ($_SESSION['login_time'] ?? time())
        ]);
    }

    // Clear CSRF tokens
    CSRFProtection::clearAllTokens();

    // Use secure session destruction
    SessionSecurity::destroySession();
}

/**
 * Get user data by ID
 * 
 * @param int $userId User ID
 * @param bool $updateSession Whether to update session data with fetched user info
 * @return array|false User data or false if not found
 */
function getUserById($userId, $updateSession = true)
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT id, username, email, role, status, last_login, created_at, updated_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        
        // Update session data if requested and user is found
        if ($userData && $updateSession && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['username'] = $userData['username'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['role'] = $userData['role'];
            $_SESSION['status'] = $userData['status'];
            
            error_log("getUserById - Updated session for user ID: " . $userId . " with status: " . $userData['status']);
        }
        
        return $userData;
    } catch (Exception $e) {
        error_log("Failed to get user by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all users (admin only)
 * 
 * @return array Array of user data
 */
function getAllUsers()
{
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT id, username, email, role, status, last_login, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get all users: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a new user (admin only)
 * 
 * @param array $userData User data array
 * @return array Result with success status and message
 */
function createUserAccount($userData)
{
    try {
        $pdo = getDatabaseConnection();

        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                return ['success' => false, 'message' => ucfirst($field) . ' is required.'];
            }
        }

        // Validate email format
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        // Validate role
        if (!in_array($userData['role'], ['admin', 'farmer', 'student'])) {
            return ['success' => false, 'message' => 'Invalid role specified.'];
        }

        // Check for duplicate username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$userData['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        // Check for duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");

        if ($stmt->execute([$userData['username'], $userData['email'], $hashedPassword, $userData['role']])) {
            return ['success' => true, 'message' => 'User created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create user.'];
        }
    } catch (Exception $e) {
        error_log("Create user account failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

/**
 * Update user account (admin only)
 * 
 * @param int $userId User ID to update
 * @param array $userData Updated user data
 * @return array Result with success status and message
 */
function updateUserAccount($userId, $userData)
{
    try {
        $pdo = getDatabaseConnection();

        // Validate user ID
        if (!is_numeric($userId) || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Validate required fields
        if (empty($userData['username']) || empty($userData['email'])) {
            return ['success' => false, 'message' => 'Username and email are required.'];
        }

        // Validate email format
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        // Validate role
        if (!in_array($userData['role'], ['admin', 'farmer', 'student'])) {
            return ['success' => false, 'message' => 'Invalid role specified.'];
        }

        // Check for duplicate username (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$userData['username'], $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        // Check for duplicate email (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$userData['email'], $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Update user with or without password change
        if (!empty($userData['password'])) {
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, password = ?, role = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$userData['username'], $userData['email'], $hashedPassword, $userData['role'], $userId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, role = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$userData['username'], $userData['email'], $userData['role'], $userId]);
        }

        if ($result) {
            return ['success' => true, 'message' => 'User updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user.'];
        }
    } catch (Exception $e) {
        error_log("Update user account failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

/**
 * Delete user account (admin only)
 * 
 * @param int $userId User ID to delete
 * @return array Result with success status and message
 */
function deleteUserAccount($userId)
{
    try {
        $pdo = getDatabaseConnection();

        // Validate user ID
        if (!is_numeric($userId) || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        // Prevent deleting current user
        if ($userId == getUserId()) {
            return ['success' => false, 'message' => 'You cannot delete your own account.'];
        }

        // Get user info before deletion
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Delete user (foreign key constraints will handle related data)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");

        if ($stmt->execute([$userId])) {
            return ['success' => true, 'message' => 'User "' . $user['username'] . '" deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete user.'];
        }
    } catch (Exception $e) {
        error_log("Delete user account failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

/**
 * Toggle user status between active and inactive (admin only)
 * 
 * @param int $userId User ID to toggle
 * @return array Result with success status and message
 */
function toggleUserAccountStatus($userId)
{
    try {
        $pdo = getDatabaseConnection();

        // Validate user ID
        if (!is_numeric($userId) || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        // Prevent deactivating current user
        if ($userId == getUserId()) {
            return ['success' => false, 'message' => 'You cannot deactivate your own account.'];
        }

        // Get current user status
        $stmt = $pdo->prepare("SELECT username, status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Toggle status
        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");

        if ($stmt->execute([$newStatus, $userId])) {
            $action = $newStatus === 'active' ? 'activated' : 'deactivated';
            return ['success' => true, 'message' => 'User "' . $user['username'] . '" ' . $action . ' successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user status.'];
        }
    } catch (Exception $e) {
        error_log("Toggle user status failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

/**
 * Get user statistics for admin dashboard
 * 
 * @return array User statistics
 */
function getUserStatistics()
{
    try {
        $pdo = getDatabaseConnection();

        $stats = [];

        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $stats['total'] = $stmt->fetch()['total'];

        // Active users
        $stmt = $pdo->query("SELECT COUNT(*) as active FROM users WHERE status = 'active'");
        $stats['active'] = $stmt->fetch()['active'];

        // Users by role
        $stmt = $pdo->query("
            SELECT role, COUNT(*) as count 
            FROM users 
            GROUP BY role 
            ORDER BY role
        ");
        $stats['by_role'] = $stmt->fetchAll();

        // Recent users (last 30 days)
        $stmt = $pdo->query("
            SELECT COUNT(*) as recent 
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['recent'] = $stmt->fetch()['recent'];

        return $stats;
    } catch (Exception $e) {
        error_log("Get user statistics failed: " . $e->getMessage());
        return [
            'total' => 0,
            'active' => 0,
            'by_role' => [],
            'recent' => 0
        ];
    }
}

/**
 * Update user profile information
 * 
 * @param int $userId User ID to update
 * @param array $profileData Profile data to update
 * @return array Result with success status and message
 */
function updateUserProfile($userId, $profileData)
{
    try {
        $pdo = getDatabaseConnection();

        // Validate user ID
        if (!is_numeric($userId) || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        // Validate required fields
        if (empty($profileData['username']) || empty($profileData['email'])) {
            return ['success' => false, 'message' => 'Username and email are required.'];
        }

        // Validate email format
        if (!filter_var($profileData['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        // Validate username length
        if (strlen($profileData['username']) < 3 || strlen($profileData['username']) > 50) {
            return ['success' => false, 'message' => 'Username must be between 3 and 50 characters.'];
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Check for duplicate username (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$profileData['username'], $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        // Check for duplicate email (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$profileData['email'], $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Update user profile
        $stmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, email = ?, updated_at = NOW() 
            WHERE id = ?
        ");

        if ($stmt->execute([$profileData['username'], $profileData['email'], $userId])) {
            return ['success' => true, 'message' => 'Profile updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile.'];
        }
    } catch (Exception $e) {
        error_log("Update user profile failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

/**
 * Change user password
 * 
 * @param int $userId User ID
 * @param string $currentPassword Current password (plain text)
 * @param string $newPassword New password (plain text)
 * @return array Result with success status and message
 */
function changeUserPassword($userId, $currentPassword, $newPassword)
{
    try {
        $pdo = getDatabaseConnection();

        // Validate user ID
        if (!is_numeric($userId) || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        // Validate passwords
        if (empty($currentPassword) || empty($newPassword)) {
            return ['success' => false, 'message' => 'Both current and new passwords are required.'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters long.'];
        }

        if ($currentPassword === $newPassword) {
            return ['success' => false, 'message' => 'New password must be different from current password.'];
        }

        // Get current password hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, updated_at = NOW() 
            WHERE id = ?
        ");

        if ($stmt->execute([$hashedPassword, $userId])) {
            return ['success' => true, 'message' => 'Password changed successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password.'];
        }
    } catch (Exception $e) {
        error_log("Change user password failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return array Validation result with strength score and suggestions
 */
function validatePasswordStrength($password)
{
    $score = 0;
    $suggestions = [];

    // Length check
    if (strlen($password) >= 8) {
        $score += 2;
    } elseif (strlen($password) >= 6) {
        $score += 1;
        $suggestions[] = 'Consider using at least 8 characters for better security';
    } else {
        $suggestions[] = 'Password must be at least 6 characters long';
    }

    // Character variety checks
    if (preg_match('/[a-z]/', $password)) {
        $score += 1;
    } else {
        $suggestions[] = 'Include lowercase letters';
    }

    if (preg_match('/[A-Z]/', $password)) {
        $score += 1;
    } else {
        $suggestions[] = 'Include uppercase letters';
    }

    if (preg_match('/[0-9]/', $password)) {
        $score += 1;
    } else {
        $suggestions[] = 'Include numbers';
    }

    if (preg_match('/[^a-zA-Z0-9]/', $password)) {
        $score += 1;
    } else {
        $suggestions[] = 'Include special characters (!@#$%^&*)';
    }

    // Determine strength level
    if ($score >= 5) {
        $strength = 'strong';
    } elseif ($score >= 3) {
        $strength = 'medium';
    } else {
        $strength = 'weak';
    }

    return [
        'score' => $score,
        'strength' => $strength,
        'suggestions' => $suggestions,
        'is_valid' => $score >= 2 // Minimum acceptable strength
    ];
}

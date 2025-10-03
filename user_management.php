<?php

/**
 * User Management System - Admin Only
 * 
 * Provides comprehensive user account management functionality
 * including CRUD operations, role assignment, and status management
 */

// Start session
session_start();

// Simple authentication check - admin only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Set page title for header component
$pageTitle = 'User Management - IoT Farm Monitoring System';

// Initialize variables
$error = '';
$success = '';
$users = [];
$editUser = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                $result = createUser($_POST);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;

            case 'update':
                $result = updateUser($_POST);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;

            case 'delete':
                $result = deleteUser($_POST['user_id']);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;

            case 'toggle_status':
                $result = toggleUserStatus($_POST['user_id']);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
}
}

// Handle edit user request
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editUser = getUserById($_GET['edit']);
    if (!$editUser) {
        $error = 'User not found.';
    }
}

// Get all users for display
$users = getAllUsers();

/**
 * Create new user
 */
function createUser($data)
{
    try {
        // Validate input
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'student';
        
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        if (!in_array($role, ['admin', 'farmer', 'student'])) {
            return ['success' => false, 'message' => 'Invalid role selected.'];
        }

        $pdo = getDatabaseConnection();

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");

        if ($stmt->execute([$username, $email, $hashedPassword, $role])) {
            return ['success' => true, 'message' => 'User created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create user.'];
        }
        
    } catch (Exception $e) {
        error_log("Create user failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while creating the user.'];
    }
}

/**
 * Update existing user
 */
function updateUser($data)
{
    try {
        $userId = $data['user_id'] ?? 0;
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $role = $data['role'] ?? 'student';
        $password = $data['password'] ?? '';

        if (!is_numeric($userId) || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        if (empty($username) || empty($email)) {
            return ['success' => false, 'message' => 'Username and email are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        if (!in_array($role, ['admin', 'farmer', 'student'])) {
            return ['success' => false, 'message' => 'Invalid role selected.'];
        }

        $pdo = getDatabaseConnection();

        // Check if username exists for other users
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        // Check if email exists for other users
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Update user
        if (!empty($password)) {
            // Update with new password
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
            }
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, password = ?, role = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$username, $email, $hashedPassword, $role, $userId]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, role = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$username, $email, $role, $userId]);
        }

        if ($result) {
            return ['success' => true, 'message' => 'User updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user.'];
        }
        
    } catch (Exception $e) {
        error_log("Update user failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the user.'];
    }
}

/**
 * Delete user
 */
function deleteUser($userId)
{
    try {
        if (!is_numeric($userId) || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        // Prevent deleting current user
        if ($userId == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'You cannot delete your own account.'];
        }

        $pdo = getDatabaseConnection();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Delete user (cascade will handle related records)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");

        if ($stmt->execute([$userId])) {
            return ['success' => true, 'message' => 'User "' . $user['username'] . '" deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete user.'];
        }
        
    } catch (Exception $e) {
        error_log("Delete user failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting the user.'];
    }
}

/**
 * Toggle user status (active/inactive)
 */
function toggleUserStatus($userId)
{
    try {
        if (!is_numeric($userId) || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID.'];
        }

        // Prevent deactivating current user
        if ($userId == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'You cannot deactivate your own account.'];
        }

        $pdo = getDatabaseConnection();

        // Get current status
        $stmt = $pdo->prepare("SELECT username, status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Toggle status
        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");

        if ($stmt->execute([$newStatus, $userId])) {
            $action = $newStatus === 'active' ? 'activated' : 'deactivated';
            return ['success' => true, 'message' => 'User "' . $user['username'] . '" ' . $action . ' successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user status.'];
        }
        
    } catch (Exception $e) {
        error_log("Toggle user status failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating user status.'];
    }
}

// Include shared header
include 'includes/header.php';

// Include navigation
include 'includes/navigation.php';
?>

<div class="p-4 max-w-7xl mx-auto">
    
    <!-- Page Header -->
    <div class="mb-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-users text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">User Management</h1>
                    <p class="text-gray-600 dark:text-gray-400">Manage user accounts, roles, and permissions</p>
                </div>
            </div>
            <div class="mt-4 lg:mt-0">
                <button type="button"
                    onclick="showCreateUserModal()"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>Add New User
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-4">
        <!-- Total Users -->
        <div class="bg-green-600 text-white rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-white/80 text-xs font-medium">Users</h3>
                <i class="fas fa-users text-xs"></i>
            </div>
            <div class="text-xl font-bold"><?php echo count($users); ?></div>
            <div class="text-white/80 text-xs">Total</div>
        </div>

        <!-- Active Users -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium">Active</h3>
                <i class="fas fa-user-check text-green-600 dark:text-green-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">
                <?php echo count(array_filter($users, function($u) { return $u['status'] === 'active'; })); ?>
            </div>
            <div class="text-green-600 text-xs">Online</div>
        </div>

        <!-- Admins -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium">Admins</h3>
                <i class="fas fa-user-shield text-red-600 dark:text-red-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">
                <?php echo count(array_filter($users, function($u) { return $u['role'] === 'admin'; })); ?>
            </div>
            <div class="text-red-600 text-xs">Managers</div>
        </div>

        <!-- Farmers -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium">Farmers</h3>
                <i class="fas fa-seedling text-green-600 dark:text-green-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">
                <?php echo count(array_filter($users, function($u) { return $u['role'] === 'farmer'; })); ?>
            </div>
            <div class="text-green-600 text-xs">Operators</div>
        </div>

        <!-- Students -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium">Students</h3>
                <i class="fas fa-graduation-cap text-blue-600 dark:text-blue-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">
                <?php echo count(array_filter($users, function($u) { return $u['role'] === 'student'; })); ?>
            </div>
            <div class="text-blue-600 text-xs">Learners</div>
        </div>

        <!-- Live Time -->
        <div class="bg-gray-900 dark:bg-white border border-gray-800 dark:border-gray-200 rounded-xl p-3 relative overflow-hidden">
            <div class="text-center">
                <div class="text-3xl font-bold text-white dark:text-gray-900 leading-none mb-1">
                    <span id="live-time"><?php echo date('H:i'); ?></span>
                </div>
                <div class="text-gray-300 dark:text-gray-600 text-xs font-medium">
                    <span id="live-date"><?php echo date('D, j M'); ?></span>
                </div>
            </div>
        </div>

        <script>
            // Update time and date every second with cool animation
            function updateTimeAndDate() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const dayString = now.toLocaleDateString('en-US', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short'
                });

                const timeElement = document.getElementById('live-time');
                const dateElement = document.getElementById('live-date');

                if (timeElement) {
                    timeElement.style.transform = 'scale(1.1)';
                    timeElement.textContent = timeString;
                    setTimeout(() => {
                        timeElement.style.transform = 'scale(1)';
                    }, 200);
                }

                if (dateElement) {
                    dateElement.textContent = dayString;
                }
            }

            // Update immediately and then every second
            updateTimeAndDate();
            setInterval(updateTimeAndDate, 1000);
        </script>
    </div>

    <!-- Alert Messages -->
    <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 dark:text-red-400 mr-3"></i>
                <span class="text-red-700 dark:text-red-300 font-medium"><?php echo htmlspecialchars($error); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 mb-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-3"></i>
                <span class="text-green-700 dark:text-green-300 font-medium"><?php echo htmlspecialchars($success); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-table text-blue-600 mr-2"></i>
                All Users
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Login</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-users text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p>No users found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                                                <span class="text-white font-semibold text-xs">
                                                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-xs font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                        You
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php
                                    $roleColors = [
                                        'admin' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                        'farmer' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                        'student' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'
                                    ];
                                    $roleColor = $roleColors[$user['role']] ?? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $roleColor; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                            <i class="fas fa-circle text-green-400 mr-1" style="font-size: 6px;"></i>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                            <i class="fas fa-circle text-red-400 mr-1" style="font-size: 6px;"></i>
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    <?php
                                    if ($user['last_login']) {
                                        echo date('M j, g:i A', strtotime($user['last_login']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-medium">
                                    <div class="flex items-center justify-end space-x-1">
                                        <!-- Edit Button -->
                                        <button type="button"
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 p-1 rounded transition-colors duration-200"
                                            title="Edit User">
                                            <i class="fas fa-edit text-xs"></i>
                                        </button>

                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <!-- Toggle Status Button -->
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to <?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?> this user?')">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit"
                                                    class="<?php echo $user['status'] === 'active' ? 'text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300' : 'text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300'; ?> p-1 rounded transition-colors duration-200"
                                                    title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> User">
                                                    <i class="fas fa-<?php echo $user['status'] === 'active' ? 'pause' : 'play'; ?> text-xs"></i>
                                                </button>
                                            </form>

                                            <!-- Delete Button -->
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit"
                                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 p-1 rounded transition-colors duration-200"
                                                    title="Delete User">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit User Modal -->
<div id="userModal" class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl shadow-lg">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white" id="modalTitle">Add New User</h3>
                <button type="button" onclick="closeUserModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="userForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="user_id" id="userId" value="">

                <div>
                    <label for="username" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                    <input type="text"
                        id="username"
                        name="username"
                        required
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                </div>

                <div>
                    <label for="email" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email"
                        id="email"
                        name="email"
                        required
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                </div>

                <div>
                    <label for="role" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                    <select id="role"
                        name="role"
                        required
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                        <option value="student">Student</option>
                        <option value="farmer">Farmer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Password <span id="passwordNote" class="text-gray-500 dark:text-gray-400 text-xs">(minimum 6 characters)</span>
                    </label>
                    <input type="password"
                        id="password"
                        name="password"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <button type="button"
                        onclick="closeUserModal()"
                        class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                        <span id="submitButtonText">Create User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    /**
     * Show create user modal
     */
    function showCreateUserModal() {
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('formAction').value = 'create';
        document.getElementById('userId').value = '';
        document.getElementById('submitButtonText').textContent = 'Create User';
        document.getElementById('passwordNote').textContent = '(minimum 6 characters)';
        document.getElementById('password').required = true;

        // Clear form
        document.getElementById('userForm').reset();

        // Show modal
        document.getElementById('userModal').classList.remove('hidden');
    }

    /**
     * Edit user
     */
    function editUser(user) {
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('formAction').value = 'update';
        document.getElementById('userId').value = user.id;
        document.getElementById('submitButtonText').textContent = 'Update User';
        document.getElementById('passwordNote').textContent = '(leave blank to keep current password)';
        document.getElementById('password').required = false;

        // Populate form
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email;
        document.getElementById('role').value = user.role;
        document.getElementById('password').value = '';

        // Show modal
        document.getElementById('userModal').classList.remove('hidden');
    }

    /**
     * Close user modal
     */
    function closeUserModal() {
        document.getElementById('userModal').classList.add('hidden');
        document.getElementById('userForm').reset();
    }

    // Close modal when clicking outside
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUserModal();
        }
    });
</script>
<?php
// Include shared footer
include 'includes/footer.php';
?>
</main>
</div>
</div>


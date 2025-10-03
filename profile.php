<?php

/**
 * User Profile Management Page
 * 
 * Allows users to view and update their profile information,
 * change passwords, and manage account settings
 */

// Start session
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Get current user data
$currentUserId = $_SESSION['user_id'];
$currentUser = getUserById($currentUserId);

// Fallback if getUserById fails
if (!$currentUser) {
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => date('Y-m-d H:i:s')
    ];
}

$success = '';
$error = '';
$profileError = '';
$passwordError = '';

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {

        // Profile Information Update
        if ($_POST['action'] === 'update_profile') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');

            // Validate input
            if (empty($username)) {
                $profileError = 'Username is required.';
            } elseif (empty($email)) {
                $profileError = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $profileError = 'Please enter a valid email address.';
            } elseif (strlen($username) < 3) {
                $profileError = 'Username must be at least 3 characters long.';
            } elseif (strlen($username) > 50) {
                $profileError = 'Username must be less than 50 characters.';
            } else {
                try {
                    $pdo = getDatabaseConnection();

                    // Check for duplicate username (excluding current user)
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$username, $currentUserId]);
                    if ($stmt->fetch()) {
                        $profileError = 'Username already exists. Please choose a different one.';
                    } else {
                        // Check for duplicate email (excluding current user)
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $stmt->execute([$email, $currentUserId]);
                        if ($stmt->fetch()) {
                            $profileError = 'Email already exists. Please use a different email address.';
                        } else {
                            // Update profile information
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET username = ?, email = ? 
                                WHERE id = ?
                            ");

                            if ($stmt->execute([$username, $email, $currentUserId])) {
                                // Update session data
                                $_SESSION['username'] = $username;
                                $_SESSION['email'] = $email;

                                // Refresh user data
                                $currentUser = getUserById($currentUserId);
                                $success = 'Profile updated successfully!';
                            } else {
                                $profileError = 'Failed to update profile. Please try again.';
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Profile update failed: " . $e->getMessage());
                    $profileError = 'An error occurred while updating your profile.';
                }
            }
        }

        // Password Change
        elseif ($_POST['action'] === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validate input
            if (empty($currentPassword)) {
                $passwordError = 'Current password is required.';
            } elseif (empty($newPassword)) {
                $passwordError = 'New password is required.';
            } elseif (empty($confirmPassword)) {
                $passwordError = 'Please confirm your new password.';
            } elseif (strlen($newPassword) < 6) {
                $passwordError = 'New password must be at least 6 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $passwordError = 'New password and confirmation do not match.';
            } elseif ($currentPassword === $newPassword) {
                $passwordError = 'New password must be different from current password.';
            } else {
                try {
                    $pdo = getDatabaseConnection();

                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$currentUserId]);
                    $user = $stmt->fetch();

                    if (!$user || !password_verify($currentPassword, $user['password'])) {
                        $passwordError = 'Current password is incorrect.';
                    } else {
                        // Update password
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET password = ? 
                            WHERE id = ?
                        ");

                        if ($stmt->execute([$hashedPassword, $currentUserId])) {
                            $success = 'Password changed successfully!';
                            // Clear password fields
                            $_POST = [];
                        } else {
                            $passwordError = 'Failed to change password. Please try again.';
                        }
                    }
                } catch (Exception $e) {
                    error_log("Password change failed: " . $e->getMessage());
                    $passwordError = 'An error occurred while changing your password.';
                }
            }
        }
    }
}

// Set page title and additional CSS
$pageTitle = 'Profile Settings - IoT Farm Monitoring System';
$additionalCSS = [];
$additionalJS = [];

// Include header and navigation
include 'includes/header.php';
include 'includes/navigation.php';
?>

<div class="p-4 max-w-7xl mx-auto">

    <!-- Success Message -->
    <?php if ($success): ?>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 mb-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-3"></i>
                <span class="text-green-700 dark:text-green-300 font-medium"><?php echo htmlspecialchars($success); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl mb-4">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8 px-4" aria-label="Tabs">
                <button onclick="showTab('profile')" id="profile-tab" class="tab-button active border-b-2 border-green-500 py-3 px-1 text-sm font-medium text-green-600 dark:text-green-400">
                    <i class="fas fa-user mr-2"></i>
                    Profile
                </button>
                <button onclick="showTab('account')" id="account-tab" class="tab-button border-b-2 border-transparent py-3 px-1 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                    <i class="fas fa-user-edit mr-2"></i>
                    Account Settings
                </button>
                <button onclick="showTab('security')" id="security-tab" class="tab-button border-b-2 border-transparent py-3 px-1 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                    <i class="fas fa-lock mr-2"></i>
                    Security
                </button>
                <button onclick="showTab('preferences')" id="preferences-tab" class="tab-button border-b-2 border-transparent py-3 px-1 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300">
                    <i class="fas fa-cog mr-2"></i>
                    Preferences
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="space-y-4">

        <!-- Profile Tab -->
        <div id="profile-content" class="tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Profile Overview -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-user text-green-600 mr-2"></i>
                                Profile Overview
                            </h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Your profile information and activity summary</p>
                        </div>
                        <div class="p-4">
                            <!-- Profile Header -->
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-2xl">
                                        <?php echo strtoupper(substr($currentUser['username'] ?? 'U', 0, 2)); ?>
                                    </span>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($currentUser['username']); ?></h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-1
                                        <?php
                                        $userRole = $currentUser['role'] ?? 'unknown';
                                        switch ($userRole) {
                                            case 'admin':
                                                echo 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
                                                break;
                                            case 'farmer':
                                                echo 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
                                                break;
                                            case 'student':
                                                echo 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
                                                break;
                                            default:
                                                echo 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
                                        }
                                        ?>">
                                        <?php echo ucfirst($userRole); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Profile Stats -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        <?php
                                        if (isset($currentUser['created_at']) && $currentUser['created_at']) {
                                            $memberSince = new DateTime($currentUser['created_at']);
                                            $now = new DateTime();
                                            $diff = $now->diff($memberSince);
                                            echo $diff->days;
                                        } else {
                                            echo '0';
                                        }
                                        ?>
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Days Active</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">24</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Sessions</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">12</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Reports</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">98%</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Uptime</div>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Recent Activity</h4>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-sign-in-alt text-green-500 mr-2"></i>
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Logged in</span>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">2 hours ago</span>
                                    </div>
                                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Generated report</span>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">1 day ago</span>
                                    </div>
                                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-user-edit text-purple-500 mr-2"></i>
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Updated profile</span>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">3 days ago</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Sidebar -->
                <div class="space-y-4">
                    <!-- Account Details -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Account Details</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Member Since</span>
                                <span class="text-xs font-medium text-gray-900 dark:text-white">
                                    <?php
                                    if (isset($currentUser['created_at']) && $currentUser['created_at']) {
                                        echo date('M Y', strtotime($currentUser['created_at']));
                                    } else {
                                        echo 'Unknown';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Last Login</span>
                                <span class="text-xs font-medium text-gray-900 dark:text-white">
                                    <?php
                                    if (isset($currentUser['last_login']) && $currentUser['last_login']) {
                                        echo date('M j, g:i A', strtotime($currentUser['last_login']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Status</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    <?php echo ($currentUser['status'] ?? 'inactive') === 'active' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; ?>">
                                    <i class="fas fa-circle text-xs mr-1"></i>
                                    <?php echo ucfirst($currentUser['status'] ?? 'inactive'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-4 space-y-2">
                            <button onclick="showTab('account')" class="w-full flex items-center justify-between p-2 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                <div class="flex items-center">
                                    <i class="fas fa-user-edit text-gray-400 mr-2 text-xs"></i>
                                    <span class="text-xs text-gray-700 dark:text-gray-300">Edit Profile</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            </button>
                            <button onclick="showTab('security')" class="w-full flex items-center justify-between p-2 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                <div class="flex items-center">
                                    <i class="fas fa-lock text-gray-400 mr-2 text-xs"></i>
                                    <span class="text-xs text-gray-700 dark:text-gray-300">Change Password</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            </button>
                            <a href="dashboard.php" class="w-full flex items-center justify-between p-2 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                <div class="flex items-center">
                                    <i class="fas fa-tachometer-alt text-gray-400 mr-2 text-xs"></i>
                                    <span class="text-xs text-gray-700 dark:text-gray-300">Dashboard</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Settings Tab -->
        <div id="account-content" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-user-edit text-green-600 mr-2"></i>
                        Account Settings
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Update your account details and personal information</p>
                </div>

                <div class="p-4">
                    <?php if ($profileError): ?>
                        <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-500 dark:text-red-400 mr-2"></i>
                                <span class="text-red-700 dark:text-red-300 text-sm"><?php echo htmlspecialchars($profileError); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Username Field -->
                            <div>
                                <label for="username" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Username
                                </label>
                                <input type="text"
                                    id="username"
                                    name="username"
                                    value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>"
                                    required
                                    maxlength="50"
                                    title="Username must be 3-50 characters and contain only letters, numbers, underscores, and hyphens"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">3-50 characters, letters, numbers, underscores, and hyphens only</p>
                            </div>

                            <!-- Email Field -->
                            <div>
                                <label for="email" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Email Address
                                </label>
                                <input type="email"
                                    id="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>"
                                    required
                                    maxlength="100"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">We'll use this email for important account notifications</p>
                            </div>
                        </div>

                        <!-- Read-only Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Role Field (Read-only) -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Role
                                </label>
                                <div class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        <?php
                                        $userRole = $currentUser['role'] ?? 'unknown';
                                        switch ($userRole) {
                                            case 'admin':
                                                echo 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
                                                break;
                                            case 'farmer':
                                                echo 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
                                                break;
                                            case 'student':
                                                echo 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
                                                break;
                                            default:
                                                echo 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200';
                                        }
                                        ?>">
                                        <?php echo ucfirst($userRole); ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Contact an administrator to change your role</p>
                            </div>

                            <!-- Account Status (Read-only) -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Account Status
                                </label>
                                <div class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        <?php echo ($currentUser['status'] ?? 'inactive') === 'active' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; ?>">
                                        <i class="fas fa-circle text-xs mr-1"></i>
                                        <?php echo ucfirst($currentUser['status'] ?? 'inactive'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <h4 class="text-xs font-medium text-gray-900 dark:text-white mb-2">Account Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Member since:</span>
                                    <span class="text-gray-900 dark:text-white font-medium ml-2">
                                        <?php
                                        if (isset($currentUser['created_at']) && $currentUser['created_at']) {
                                            echo date('F j, Y', strtotime($currentUser['created_at']));
                                        } else {
                                            echo 'Unknown';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Last login:</span>
                                    <span class="text-gray-900 dark:text-white font-medium ml-2">
                                        <?php
                                        if (isset($currentUser['last_login']) && $currentUser['last_login']) {
                                            echo date('M j, Y g:i A', strtotime($currentUser['last_login']));
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-3 border-t border-gray-200 dark:border-gray-600">
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div id="security-content" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-lock text-yellow-600 mr-2"></i>
                        Security Settings
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Manage your password and security preferences</p>
                </div>

                <div class="p-4">
                    <?php if ($passwordError): ?>
                        <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-500 dark:text-red-400 mr-2"></i>
                                <span class="text-red-700 dark:text-red-300 text-sm"><?php echo htmlspecialchars($passwordError); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">

                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Current Password
                            </label>
                            <input type="password"
                                id="current_password"
                                name="current_password"
                                required
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- New Password -->
                            <div>
                                <label for="new_password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    New Password
                                </label>
                                <input type="password"
                                    id="new_password"
                                    name="new_password"
                                    required
                                    minlength="6"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum 6 characters</p>
                            </div>

                            <!-- Confirm New Password -->
                            <div>
                                <label for="confirm_password" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Confirm New Password
                                </label>
                                <input type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    required
                                    minlength="6"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200">
                            </div>
                        </div>

                        <!-- Password Requirements -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <h4 class="text-xs font-medium text-blue-900 dark:text-blue-200 mb-2">Password Requirements:</h4>
                            <ul class="text-xs text-blue-800 dark:text-blue-300 space-y-1">
                                <li class="flex items-center">
                                    <i class="fas fa-check text-blue-600 dark:text-blue-400 mr-2"></i>
                                    At least 6 characters long
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-blue-600 dark:text-blue-400 mr-2"></i>
                                    Different from your current password
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mr-2"></i>
                                    Consider using a mix of letters, numbers, and symbols for better security
                                </li>
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-3 border-t border-gray-200 dark:border-gray-600">
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                                <i class="fas fa-key mr-2"></i>
                                Change Password
                            </button>
                        </div>
                    </form>

                    <!-- Security Information -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-600">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Security Best Practices</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <ul class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 mt-0.5"></i>
                                    Use a strong, unique password
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 mt-0.5"></i>
                                    Log out when using shared computers
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 mt-0.5"></i>
                                    Keep your email address up to date
                                </li>
                            </ul>
                            <ul class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 mt-0.5"></i>
                                    Report suspicious activity immediately
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 mt-0.5"></i>
                                    Enable two-factor authentication when available
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 mt-0.5"></i>
                                    Regularly review account activity
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preferences Tab -->
        <div id="preferences-content" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-cog text-purple-600 mr-2"></i>
                        User Preferences
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Customize your experience and notification settings</p>
                </div>

                <div class="p-4 space-y-6">
                    <!-- Theme Preferences -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Theme & Display</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white">Dark Mode</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Switch between light and dark themes</p>
                                </div>
                                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white">Compact View</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Use smaller spacing and fonts</p>
                                </div
                                    <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Notifications</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white">Email Notifications</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Receive updates via email</p>
                                </div>
                                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-6"></span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white">Pest Alerts</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Get notified about pest detection</p>
                                </div>
                                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-6"></span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="text-sm text-gray-900 dark:text-white">System Updates</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Notifications about system maintenance</p>
                                </div>
                                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Language & Region -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Language & Region</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Language</label>
                                <select class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <option>English (US)</option>
                                    <option>Spanish</option>
                                    <option>French</option>
                                    <option>German</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                                <select class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <option>UTC-5 (Eastern Time)</option>
                                    <option>UTC-6 (Central Time)</option>
                                    <option>UTC-7 (Mountain Time)</option>
                                    <option>UTC-8 (Pacific Time)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end pt-3 border-t border-gray-200 dark:border-gray-600">
                        <button type="button"
                            class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i>
                            Save Preferences
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab and Form Validation JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active', 'border-green-500', 'text-green-600', 'dark:text-green-400');
                button.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            });

            // Show selected tab content
            const selectedContent = document.getElementById(tabName + '-content');
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
            }

            // Add active class to selected tab button
            const selectedButton = document.getElementById(tabName + '-tab');
            if (selectedButton) {
                selectedButton.classList.add('active', 'border-green-500', 'text-green-600', 'dark:text-green-400');
                selectedButton.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            }
        }

        // Make showTab function globally available
        window.showTab = showTab;

        // Password validation
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        function validatePasswords() {
            if (!newPasswordInput || !confirmPasswordInput) return;

            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (newPassword && confirmPassword) {
                if (newPassword !== confirmPassword) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }
        }

        if (newPasswordInput && confirmPasswordInput) {
            newPasswordInput.addEventListener('input', validatePasswords);
            confirmPasswordInput.addEventListener('input', validatePasswords);
        }

        // Form submission validation
        const passwordForms = document.querySelectorAll('form[method="POST"]');
        passwordForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const actionInput = this.querySelector('input[name="action"]');
                if (actionInput && actionInput.value === 'change_password') {
                    validatePasswords();
                    const confirmInput = this.querySelector('#confirm_password');
                    if (confirmInput && !confirmInput.checkValidity()) {
                        e.preventDefault();
                        confirmInput.reportValidity();
                    }
                }
            });
        });

        // Toggle switches functionality
        const toggleSwitches = document.querySelectorAll('button[class*="inline-flex"][class*="rounded-full"]');
        toggleSwitches.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const span = this.querySelector('span');
                const isActive = this.classList.contains('bg-green-600');

                if (isActive) {
                    this.classList.remove('bg-green-600');
                    this.classList.add('bg-gray-200', 'dark:bg-gray-600');
                    span.classList.remove('translate-x-6');
                    span.classList.add('translate-x-1');
                } else {
                    this.classList.add('bg-green-600');
                    this.classList.remove('bg-gray-200', 'dark:bg-gray-600');
                    span.classList.add('translate-x-6');
                    span.classList.remove('translate-x-1');
                }
            });
        });
    });
</script>

<style>
    .tab-button.active {
        border-bottom-color: #10b981;
        color: #10b981;
    }

    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
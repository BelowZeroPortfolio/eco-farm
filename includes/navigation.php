<?php

/**
 * Professional Sidebar Navigation Component
 * 
 * Modern sidebar navigation with role-based access control,
 * professional design patterns, and enhanced UX
 */

// Include design system and language support
require_once 'includes/design-system.php';
require_once 'includes/language.php';

// Ensure user is logged in before showing navigation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    return;
}

// Get current page name for active highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get current user information
$currentUser = [
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['role'] ?? 'student',
    'email' => $_SESSION['email'] ?? ''
];

// Get role-specific theme
$roleTheme = getRoleTheme($currentUser['role']);

// Define navigation sections with professional grouping
$navigationSections = [
    'overview' => [
        'title' => 'Overview',
        'items' => [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'fas fa-chart-line',
                'url' => 'dashboard.php',
                'roles' => ['admin', 'farmer', 'student'],
                'description' => 'System overview and key metrics'
            ]
        ]
    ],
    'monitoring' => [
        'title' => 'Monitoring',
        'items' => [
            'sensors' => [
                'title' => 'Sensors',
                'icon' => 'fas fa-thermometer-half',
                'url' => 'sensors.php',
                'roles' => ['admin', 'farmer', 'student'],
                'description' => 'Environmental sensor data'
            ],
            'pest_detection' => [
                'title' => 'Pest Detection',
                'icon' => 'fas fa-bug',
                'url' => 'pest_detection.php',
                'roles' => ['admin', 'farmer', 'student'],
                'description' => 'AI-powered pest monitoring'
            ],
            'notifications' => [
                'title' => 'Notifications',
                'icon' => 'fas fa-bell',
                'url' => 'notifications.php',
                'roles' => ['admin', 'farmer', 'student'],
                'description' => 'System alerts and notifications'
            ]
        ]
    ],
    'learning' => [
        'title' => 'Learning & Analysis',
        'items' => [
            'reports' => [
                'title' => 'Reports',
                'icon' => 'fas fa-chart-bar',
                'url' => 'reports.php',
                'roles' => ['admin', 'farmer', 'student'],
                'description' => 'Analytics and reporting'
            ],
            'data_analytics' => [
                'title' => 'Data Analytics',
                'icon' => 'fas fa-chart-line',
                'url' => 'data_analytics.php',
                'roles' => ['admin', 'farmer', 'student'],
                'description' => 'Advanced data visualization and trends'
            ],
            'learning_resources' => [
                'title' => 'Learning Resources',
                'icon' => 'fas fa-graduation-cap',
                'url' => 'learning_resources.php',
                'roles' => ['admin', 'farmer', 'student'],
                'description' => 'Educational materials and guides'
            ]
        ]
    ],
    'management' => [
        'title' => 'Management',
        'items' => [
            'pest_config' => [
                'title' => 'Pest Database',
                'icon' => 'fas fa-database',
                'url' => 'pest_config.php',
                'roles' => ['admin'],
                'description' => 'Manage pest information and severity'
            ],
            'plant_database' => [
                'title' => 'Plant Database',
                'icon' => 'fas fa-seedling',
                'url' => 'plant_database.php',
                'roles' => ['admin'],
                'description' => 'Manage plant profiles and thresholds'
            ],
            'user_management' => [
                'title' => 'Users',
                'icon' => 'fas fa-users',
                'url' => 'user_management.php',
                'roles' => ['admin'],
                'description' => 'User account management'
            ],
            'settings' => [
                'title' => 'Settings',
                'icon' => 'fas fa-cog',
                'url' => 'settings.php',
                'roles' => ['admin'],
                'description' => 'System configuration and preferences'
            ]
        ]
    ],
    'support' => [
        'title' => 'Support',
        'items' => [
            'help' => [
                'title' => 'Help & Documentation',
                'icon' => 'fas fa-question-circle',
                'url' => 'help.php',
                'roles' => ['admin', 'farmer', 'student'],
                'description' => 'User guides and FAQ'
            ]
        ]
    ]
];

/**
 * Check if user has access to a navigation item
 */
function hasNavigationAccess($item, $userRole)
{
    return in_array($userRole, $item['roles']);
}

/**
 * Get CSS classes for navigation item based on active state
 */
function getSidebarItemClasses($itemKey, $currentPage, $isActive = false)
{
    global $THEME;

    if ($isActive || $itemKey === $currentPage) {
        return 'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 bg-white text-gray-900 shadow-sm border-l-4 border-' . getThemeColor('primary', '600');
    } else {
        return 'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 text-gray-300 hover:text-white hover:bg-white/10';
    }
}

/**
 * Get role badge styling
 */
function getRoleBadgeClasses($role)
{
    $roleTheme = getRoleTheme($role);
    return "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$roleTheme['bg']} {$roleTheme['text']}";
}
?>

<!-- Professional Sidebar Layout -->
<div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="hidden lg:flex lg:flex-shrink-0">
        <div class="flex flex-col w-72">
            <!-- Sidebar Header -->
            <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto bg-gradient-to-b from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900 shadow-xl">
                <!-- Logo and Brand -->
                <div class="flex items-center flex-shrink-0 px-6 mb-8">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <img src="includes/sagay.png" alt="Sagay Eco-Farm Logo" class="w-10 h-10 object-contain rounded-lg">
                        </div>
                        <div class="ml-3">
                            <h1 class="text-xl font-bold text-white">Sagay Eco-Farm</h1>
                            <p class="text-xs text-gray-300">IoT Agricultural System</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Sections -->
                <nav class="flex-1 px-4 space-y-6">
                    <?php foreach ($navigationSections as $sectionKey => $section): ?>
                        <?php
                        // Check if user has access to any items in this section
                        $hasAccessToSection = false;
                        foreach ($section['items'] as $item) {
                            if (hasNavigationAccess($item, $currentUser['role'])) {
                                $hasAccessToSection = true;
                                break;
                            }
                        }
                        ?>

                        <?php if ($hasAccessToSection): ?>
                            <div>
                                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                                    <?php echo htmlspecialchars($section['title']); ?>
                                </h3>
                                <div class="space-y-1">
                                    <?php foreach ($section['items'] as $key => $item): ?>
                                        <?php if (hasNavigationAccess($item, $currentUser['role'])): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>"
                                                class="<?php echo getSidebarItemClasses($key, $currentPage); ?>"
                                                title="<?php echo htmlspecialchars($item['description']); ?>">
                                                <i class="<?php echo htmlspecialchars($item['icon']); ?> mr-3 text-lg flex-shrink-0"></i>
                                                <div class="flex-1 min-w-0">
                                                    <span class="truncate"><?php echo htmlspecialchars($item['title']); ?></span>
                                                </div>
                                                <?php if ($key === $currentPage): ?>
                                                    <div class="ml-auto">
                                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>


            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="lg:hidden fixed inset-0 z-40 hidden" id="mobile-sidebar-overlay">
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75" onclick="toggleMobileSidebar()"></div>
        <div class="relative flex flex-col max-w-xs w-full h-full bg-gradient-to-b from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900">
            <!-- Mobile Sidebar Content (same as desktop but with close button) -->
            <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button type="button"
                    class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                    onclick="toggleMobileSidebar()">
                    <i class="fas fa-times text-white text-xl"></i>
                </button>
            </div>

            <!-- Mobile sidebar content (copy from desktop version) -->
            <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
                <!-- Logo and Brand -->
                <div class="flex items-center flex-shrink-0 px-6 mb-8">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <img src="includes/sagay.png" alt="Sagay Eco-Farm Logo" class="w-10 h-10 object-contain rounded-lg">
                        </div>
                        <div class="ml-3">
                            <h1 class="text-xl font-bold text-white">Farm Monitor</h1>
                            <p class="text-xs text-gray-300">IoT Agricultural System</p>
                        </div>
                    </div>
                </div>

                <!-- User Profile Card -->
                <div class="px-6 mb-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm">
                                        <?php echo strtoupper(substr($currentUser['username'], 0, 2)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">
                                    <?php echo htmlspecialchars($currentUser['username']); ?>
                                </p>
                                <div class="flex items-center mt-1">
                                    <span class="<?php echo getRoleBadgeClasses($currentUser['role']); ?>">
                                        <?php echo ucfirst($currentUser['role']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Sections (Mobile) -->
                <nav class="flex-1 px-4 space-y-6">
                    <?php foreach ($navigationSections as $sectionKey => $section): ?>
                        <?php
                        // Check if user has access to any items in this section
                        $hasAccessToSection = false;
                        foreach ($section['items'] as $item) {
                            if (hasNavigationAccess($item, $currentUser['role'])) {
                                $hasAccessToSection = true;
                                break;
                            }
                        }
                        ?>

                        <?php if ($hasAccessToSection): ?>
                            <div>
                                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                                    <?php echo htmlspecialchars($section['title']); ?>
                                </h3>
                                <div class="space-y-1">
                                    <?php foreach ($section['items'] as $key => $item): ?>
                                        <?php if (hasNavigationAccess($item, $currentUser['role'])): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>"
                                                class="<?php echo getSidebarItemClasses($key, $currentPage); ?>"
                                                onclick="toggleMobileSidebar()">
                                                <i class="<?php echo htmlspecialchars($item['icon']); ?> mr-3 text-lg flex-shrink-0"></i>
                                                <div class="flex-1 min-w-0">
                                                    <span class="truncate"><?php echo htmlspecialchars($item['title']); ?></span>
                                                </div>
                                                <?php if ($key === $currentPage): ?>
                                                    <div class="ml-auto">
                                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>

                <!-- Mobile Sidebar Footer - Removed user actions, moved to top header -->
                <div class="flex-shrink-0 px-4 pb-4">
                    <div class="text-center">
                        <div class="text-xs text-gray-400">
                            <i class="fas fa-leaf mr-1"></i>
                            Eco-Friendly Monitoring
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex flex-col flex-1 overflow-hidden">
        <!-- Top Header Bar -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-secondary-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-4 lg:px-6 py-3">
                <!-- Left Side - Mobile Menu + Page Title -->
                <div class="flex items-center">
                    <!-- Mobile Menu Button -->
                    <button type="button"
                        class="lg:hidden text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none mr-4"
                        onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <!-- Page Title (Desktop) -->
                    <div class="hidden lg:block">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                            <?php
                            $pageNames = [
                                'dashboard' => 'Dashboard',
                                'sensors' => 'Sensors',
                                'pest_detection' => 'Pest Detection',
                                'notifications' => 'Notifications',
                                'reports' => 'Reports',
                                'data_analytics' => 'Data Analytics',
                                'learning_resources' => 'Learning Resources',
                                'pest_config' => 'Pest Database',
                                'plant_database' => 'Plant Database',
                                'user_management' => 'User Management',
                                'settings' => 'Settings',
                                'help' => 'Help & Documentation',
                                'profile' => 'Profile',
                                'camera_management' => 'Camera Management'
                            ];
                            echo $pageNames[$currentPage] ?? 'Dashboard';
                            ?>
                        </h1>
                    </div>

                    <!-- Mobile Logo -->
                    <div class="lg:hidden flex items-center">
                        <div class="flex-shrink-0">
                            <img src="includes/sagay.png" alt="Sagay Eco-Farm Logo" class="w-10 h-10 object-contain rounded-lg">
                        </div>
                    </div>
                </div>

                <!-- Right Side - Search, Actions, User Menu -->
                <div class="flex items-center space-x-2 lg:space-x-4">
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-1 lg:space-x-2">

                        <!-- Theme Toggle Button -->
                        <button type="button"
                            id="theme-toggle"
                            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200 flex items-center justify-center"
                            onclick="toggleTheme()"
                            title="Toggle theme">
                            <i class="fas fa-moon text-lg"></i>
                        </button>

                        <!-- Notifications -->
                        <?php 
                        if (function_exists('generateNotificationBell')) {
                            echo generateNotificationBell();
                        } else {
                            echo '<button type="button" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200 relative flex items-center justify-center" title="Notifications">
                                <i class="fas fa-bell text-lg"></i>
                            </button>';
                        }
                        ?>

                        <!-- User Profile Dropdown -->
                        <div class="relative">
                            <button type="button"
                                class="flex items-center space-x-3 p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200"
                                id="user-menu-button"
                                onclick="toggleUserMenu()"
                                aria-expanded="false"
                                aria-haspopup="true">
                                <!-- User Avatar -->
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm">
                                        <?php echo strtoupper(substr($currentUser['username'], 0, 2)); ?>
                                    </span>
                                </div>
                                <!-- User Info (Desktop) -->
                                <div class="hidden lg:block text-left">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($currentUser['username']); ?>
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        <?php echo ucfirst($currentUser['role']); ?>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-500 dark:text-gray-400"></i>
                            </button>

                            <!-- User Dropdown Menu -->
                            <div class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-strong border border-secondary-200 dark:border-gray-700 z-50"
                                id="user-dropdown-menu"
                                role="menu"
                                aria-orientation="vertical"
                                aria-labelledby="user-menu-button">

                                <!-- User Info Header -->
                                <div class="px-4 py-3 border-b border-secondary-200 dark:border-gray-700">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-semibold">
                                                <?php echo strtoupper(substr($currentUser['username'], 0, 2)); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-body-md font-medium text-secondary-900 dark:text-white">
                                                <?php echo htmlspecialchars($currentUser['username']); ?>
                                            </div>
                                            <div class="text-body-sm text-secondary-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($currentUser['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <div class="py-2">
                                    <a href="profile.php"
                                        class="flex items-center px-4 py-3 text-body-md text-secondary-700 dark:text-gray-300 hover:bg-secondary-50 dark:hover:bg-gray-700 transition-colors duration-200 <?php echo $currentPage === 'profile' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : ''; ?>"
                                        role="menuitem">
                                        <i class="fas fa-user-edit mr-3 text-secondary-400 dark:text-gray-500"></i>
                                        Profile
                                    </a>

                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <a href="settings.php"
                                            class="flex items-center px-4 py-3 text-body-md text-secondary-700 dark:text-gray-300 hover:bg-secondary-50 dark:hover:bg-gray-700 transition-colors duration-200 <?php echo $currentPage === 'settings' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : ''; ?>"
                                            role="menuitem">
                                            <i class="fas fa-cog mr-3 text-secondary-400 dark:text-gray-500"></i>
                                            Settings
                                        </a>
                                    <?php endif; ?>

                                    <div class="border-t border-secondary-200 dark:border-gray-700 my-2"></div>

                                    <a href="logout.php"
                                        class="flex items-center px-4 py-3 text-body-md text-red-600 hover:bg-red-50 transition-colors duration-200"
                                        role="menuitem">
                                        <i class="fas fa-sign-out-alt mr-3 text-red-500"></i>
                                        Sign Out
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>

        <!-- Content Wrapper -->
        <main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900">
            <!-- This is where page content will be inserted -->

            <!-- JavaScript for Sidebar and Header Interactions -->
            <script>
                /**
                 * Toggle mobile sidebar visibility
                 */
                function toggleMobileSidebar() {
                    const overlay = document.getElementById('mobile-sidebar-overlay');

                    if (overlay.classList.contains('hidden')) {
                        overlay.classList.remove('hidden');
                        document.body.classList.add('overflow-hidden');
                    } else {
                        overlay.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    }
                }

                /**
                 * Close mobile sidebar when clicking outside or on navigation
                 */
                function closeMobileSidebar() {
                    const overlay = document.getElementById('mobile-sidebar-overlay');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }

                /**
                 * Toggle mobile search bar
                 */
                function toggleMobileSearch() {
                    const searchBar = document.getElementById('mobile-search-bar');

                    if (searchBar.classList.contains('hidden')) {
                        searchBar.classList.remove('hidden');
                        // Focus on search input
                        const searchInput = searchBar.querySelector('input');
                        if (searchInput) {
                            setTimeout(() => searchInput.focus(), 100);
                        }
                    } else {
                        searchBar.classList.add('hidden');
                    }
                }

                /**
                 * Toggle user menu dropdown
                 */
                function toggleUserMenu() {
                    const dropdown = document.getElementById('user-dropdown-menu');
                    const button = document.getElementById('user-menu-button');

                    if (dropdown.classList.contains('hidden')) {
                        dropdown.classList.remove('hidden');
                        button.setAttribute('aria-expanded', 'true');
                        // Close dropdown when clicking outside
                        document.addEventListener('click', closeUserMenuOnClickOutside);
                    } else {
                        closeUserMenu();
                    }
                }

                /**
                 * Close user menu
                 */
                function closeUserMenu() {
                    const dropdown = document.getElementById('user-dropdown-menu');
                    const button = document.getElementById('user-menu-button');

                    dropdown.classList.add('hidden');
                    button.setAttribute('aria-expanded', 'false');
                    document.removeEventListener('click', closeUserMenuOnClickOutside);
                }

                /**
                 * Close user menu when clicking outside
                 */
                function closeUserMenuOnClickOutside(event) {
                    const dropdown = document.getElementById('user-dropdown-menu');
                    const button = document.getElementById('user-menu-button');

                    if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                        closeUserMenu();
                    }
                }

                /**
                 * Show toast notification (utility function)
                 */
                function showToast(message, type = 'info') {
                    // Simple toast implementation for fallback
                    const toast = document.createElement('div');
                    toast.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border-l-4 border-${type === 'info' ? 'blue' : type === 'success' ? 'green' : type === 'warning' ? 'yellow' : 'red'}-500 p-4 transform translate-x-full transition-transform duration-300`;
                    toast.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-${type === 'info' ? 'info-circle' : type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} text-${type === 'info' ? 'blue' : type === 'success' ? 'green' : type === 'warning' ? 'yellow' : 'red'}-500 mr-3"></i>
                            <span class="text-gray-900">${message}</span>
                        </div>
                    `;
                    
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.classList.remove('translate-x-full');
                    }, 100);
                    
                    setTimeout(() => {
                        toast.classList.add('translate-x-full');
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
                }

                // Close mobile sidebar when window is resized to desktop size
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 1024) { // lg breakpoint
                        closeMobileSidebar();
                    }

                    // Hide mobile search on desktop
                    if (window.innerWidth >= 768) { // md breakpoint
                        const searchBar = document.getElementById('mobile-search-bar');
                        if (searchBar) {
                            searchBar.classList.add('hidden');
                        }
                    }
                });

                // Add smooth scrolling to navigation links
                document.addEventListener('DOMContentLoaded', function() {
                    // Add loading states to navigation links
                    const navLinks = document.querySelectorAll('nav a[href]');
                    navLinks.forEach(link => {
                        link.addEventListener('click', function(e) {
                            // Add loading state
                            const icon = this.querySelector('i');
                            if (icon && !this.href.includes('#')) {
                                const originalClass = icon.className;
                                icon.className = 'fas fa-spinner fa-spin mr-3 text-lg flex-shrink-0';

                                // Restore original icon after a short delay if still on page
                                setTimeout(() => {
                                    if (icon) {
                                        icon.className = originalClass;
                                    }
                                }, 1000);
                            }
                        });
                    });

                    // Add tooltip functionality for truncated text
                    const truncatedElements = document.querySelectorAll('.truncate');
                    truncatedElements.forEach(element => {
                        if (element.scrollWidth > element.clientWidth) {
                            element.title = element.textContent;
                        }
                    });

                    // Utility function
                    function escapeHtml(text) {
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }

                    // Close dropdowns when pressing Escape
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            closeUserMenu();
                            const searchBar = document.getElementById('mobile-search-bar');
                            if (searchBar && !searchBar.classList.contains('hidden')) {
                                toggleMobileSearch();
                            }
                        }
                    });
                });
            </script>
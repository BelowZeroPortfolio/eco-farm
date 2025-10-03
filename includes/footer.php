            <!-- Footer (inside main content area for sidebar layout) -->
            <footer class="bg-white dark:bg-gray-800 border-t border-secondary-200 dark:border-gray-700 mt-auto">
                <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center">
                            <i class="fas fa-seedling text-primary-600 dark:text-primary-400 text-lg mr-2"></i>
                            <span class="text-body-sm sm:text-body-md text-secondary-600 dark:text-gray-400 text-center sm:text-left">
                                IoT Farm Monitoring System &copy; <?php echo date('Y'); ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center space-x-2 sm:space-x-4 text-body-xs sm:text-body-sm text-secondary-500 dark:text-gray-500">
                            <span>Version 1.0.0</span>
                            <span class="hidden sm:inline">|</span>
                            <span class="flex items-center">
                                <i class="fas fa-shield-alt mr-1"></i>
                                <span class="hidden sm:inline">Secure Connection</span>
                                <span class="sm:hidden">Secure</span>
                            </span>
                        </div>
                    </div>
                </div>
            </footer>
    
    <!-- Global JavaScript -->
    <script>
        /**
         * Global utility functions
         */
        
        // Show toast notification
        function showToast(message, type = 'info', duration = 3000) {
            const toast = document.createElement('div');
            const bgColor = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'warning': 'bg-yellow-500',
                'info': 'bg-blue-500'
            }[type] || 'bg-blue-500';
            
            toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 alert-slide-in`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after duration
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, duration);
        }
        
        // Show loading spinner
        function showLoading(element) {
            const originalContent = element.innerHTML;
            element.classList.add('btn-loading');
            element.disabled = true;
            
            return function hideLoading() {
                element.classList.remove('btn-loading');
                element.innerHTML = originalContent;
                element.disabled = false;
            };
        }
        
        // Show global loading overlay
        function showGlobalLoading(message = 'Loading...') {
            const overlay = document.createElement('div');
            overlay.id = 'global-loading';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-content">
                    <div class="flex items-center justify-center mb-4">
                        <i class="fas fa-spinner loading-spinner text-2xl text-primary-600"></i>
                    </div>
                    <p class="text-body-md font-medium">${message}</p>
                </div>
            `;
            document.body.appendChild(overlay);
            return overlay;
        }
        
        // Hide global loading overlay
        function hideGlobalLoading() {
            const overlay = document.getElementById('global-loading');
            if (overlay) {
                overlay.remove();
            }
        }
        
        // Format date for display
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Validate form fields
        function validateForm(formElement) {
            const requiredFields = formElement.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            return isValid;
        }
        
        // Handle AJAX form submissions
        function handleAjaxForm(formElement, successCallback, errorCallback) {
            formElement.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!validateForm(formElement)) {
                    showToast('Please fill in all required fields', 'error');
                    return;
                }
                
                const formData = new FormData(formElement);
                const submitButton = formElement.querySelector('button[type="submit"]');
                const hideLoading = showLoading(submitButton);
                
                fetch(formElement.action || window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        if (successCallback) successCallback(data);
                        showToast(data.message || 'Operation completed successfully', 'success');
                    } else {
                        if (errorCallback) errorCallback(data);
                        showToast(data.message || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    if (errorCallback) errorCallback(error);
                    showToast('Network error occurred', 'error');
                });
            });
        }
        
        // Initialize tooltips and other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation to main content
            const mainContent = document.querySelector('main, .main-content');
            if (mainContent) {
                mainContent.classList.add('fade-in');
            }
            
            // Handle URL parameters for messages
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const error = urlParams.get('error');
            
            if (message) {
                showToast(decodeURIComponent(message), 'success');
            }
            
            if (error) {
                const errorMessages = {
                    'access_denied': 'Access denied. You do not have permission to view this page.',
                    'login_required': 'Please log in to access this page.',
                    'invalid_request': 'Invalid request. Please try again.',
                    'session_expired': 'Your session has expired. Please log in again.'
                };
                
                showToast(errorMessages[error] || decodeURIComponent(error), 'error');
            }
            
            // Initialize responsive tables
            initializeResponsiveTables();
            
            // Listen for theme changes to update charts
            window.addEventListener('themeChanged', function(e) {
                // Refresh charts if they exist
                if (typeof Chart !== 'undefined' && Chart.instances) {
                    Chart.instances.forEach(chart => {
                        updateChartTheme(chart, e.detail.theme);
                    });
                }
            });
        });
        
        // Initialize responsive tables
        function initializeResponsiveTables() {
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                // Add horizontal scroll wrapper if not already present
                if (!table.parentElement.classList.contains('overflow-x-auto')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'overflow-x-auto';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });
        }
        
        // Update chart theme
        function updateChartTheme(chart, theme) {
            const isDark = theme === 'dark';
            
            // Update chart options for theme
            chart.options.plugins.legend.labels.color = isDark ? '#e5e7eb' : '#374151';
            chart.options.plugins.tooltip.backgroundColor = isDark ? '#1f2937' : '#ffffff';
            chart.options.plugins.tooltip.titleColor = isDark ? '#f9fafb' : '#111827';
            chart.options.plugins.tooltip.bodyColor = isDark ? '#e5e7eb' : '#374151';
            chart.options.plugins.tooltip.borderColor = isDark ? '#374151' : '#e5e7eb';
            
            // Update scales
            chart.options.scales.x.title.color = isDark ? '#9ca3af' : '#6b7280';
            chart.options.scales.x.ticks.color = isDark ? '#9ca3af' : '#6b7280';
            chart.options.scales.x.grid.color = isDark ? '#374151' : '#e5e7eb';
            
            chart.options.scales.y.title.color = isDark ? '#9ca3af' : '#6b7280';
            chart.options.scales.y.ticks.color = isDark ? '#9ca3af' : '#6b7280';
            chart.options.scales.y.grid.color = isDark ? '#374151' : '#e5e7eb';
            
            chart.update();
        }
    </script>

    <!-- Error Handling JavaScript -->
    <?php echo getErrorHandlingJS(); ?>
    
    <?php
   
    
    // Initialize notification system (only for authenticated users)
    if (function_exists('isLoggedIn') && isLoggedIn() && function_exists('initializeNotificationSystem')) {
        echo initializeNotificationSystem();
    }
    ?>
</body>
</html>
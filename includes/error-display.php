<?php

/**
 * Error Display Component
 * 
 * Provides consistent error and success message display
 * across all pages in the IoT Farm Monitoring System
 */

/**
 * Display error or success alert
 */
function displayAlert($message, $type = 'error', $dismissible = true, $icon = null)
{
    if (empty($message)) {
        return '';
    }

    $alertClasses = [
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800'
    ];

    $icons = [
        'error' => 'fas fa-exclamation-triangle',
        'success' => 'fas fa-check-circle',
        'warning' => 'fas fa-exclamation-circle',
        'info' => 'fas fa-info-circle'
    ];

    $alertClass = $alertClasses[$type] ?? $alertClasses['error'];
    $iconClass = $icon ?? $icons[$type] ?? $icons['error'];
    $dismissibleClass = $dismissible ? 'alert-dismissible' : '';

    $html = '<div class="mb-6 ' . $alertClass . ' border rounded-lg p-4 animate-fade-in ' . $dismissibleClass . '" role="alert">';
    $html .= '<div class="flex items-start">';
    $html .= '<i class="' . $iconClass . ' mr-3 mt-0.5 flex-shrink-0"></i>';
    $html .= '<div class="flex-1">';
    $html .= '<span class="font-medium">' . htmlspecialchars($message) . '</span>';
    $html .= '</div>';
    
    if ($dismissible) {
        $html .= '<button type="button" class="ml-3 flex-shrink-0 text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">';
        $html .= '<i class="fas fa-times"></i>';
        $html .= '</button>';
    }
    
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * Display validation errors summary
 */
function displayValidationErrors($errors, $title = 'Please correct the following errors:')
{
    if (empty($errors) || !is_array($errors)) {
        return '';
    }

    $html = '<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-fade-in" role="alert">';
    $html .= '<div class="flex items-start">';
    $html .= '<i class="fas fa-exclamation-triangle text-red-500 mr-3 mt-1 flex-shrink-0"></i>';
    $html .= '<div class="flex-1">';
    $html .= '<h4 class="text-red-800 font-medium mb-3">' . htmlspecialchars($title) . '</h4>';
    $html .= '<ul class="text-red-700 text-sm space-y-2">';
    
    foreach ($errors as $field => $error) {
        $html .= '<li class="flex items-start">';
        $html .= '<i class="fas fa-circle text-xs mr-2 mt-2 flex-shrink-0"></i>';
        $html .= '<span>' . htmlspecialchars($error) . '</span>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * Display field-specific error
 */
function displayFieldError($errors, $field, $class = 'text-red-600 text-sm mt-1')
{
    if (isset($errors[$field]) && !empty($errors[$field])) {
        return '<div class="' . $class . '">' . htmlspecialchars($errors[$field]) . '</div>';
    }
    return '';
}

/**
 * Display inline validation feedback
 */
function displayInlineValidation($errors, $field, $successMessage = null)
{
    $html = '';
    
    if (isset($errors[$field]) && !empty($errors[$field])) {
        $html .= '<div class="flex items-center mt-2 text-red-600 text-sm">';
        $html .= '<i class="fas fa-exclamation-circle mr-2"></i>';
        $html .= '<span>' . htmlspecialchars($errors[$field]) . '</span>';
        $html .= '</div>';
    } elseif ($successMessage && !empty($_POST[$field])) {
        $html .= '<div class="flex items-center mt-2 text-green-600 text-sm">';
        $html .= '<i class="fas fa-check-circle mr-2"></i>';
        $html .= '<span>' . htmlspecialchars($successMessage) . '</span>';
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * Display system status alert
 */
function displaySystemStatus($status, $message, $details = null)
{
    $statusConfig = [
        'online' => ['type' => 'success', 'icon' => 'fas fa-check-circle', 'title' => 'System Online'],
        'offline' => ['type' => 'error', 'icon' => 'fas fa-times-circle', 'title' => 'System Offline'],
        'maintenance' => ['type' => 'warning', 'icon' => 'fas fa-tools', 'title' => 'Maintenance Mode'],
        'degraded' => ['type' => 'warning', 'icon' => 'fas fa-exclamation-triangle', 'title' => 'Degraded Performance']
    ];

    $config = $statusConfig[$status] ?? $statusConfig['offline'];
    
    $html = '<div class="mb-6 p-4 border rounded-lg ' . ($config['type'] === 'success' ? 'bg-green-50 border-green-200' : 
           ($config['type'] === 'warning' ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200')) . '">';
    $html .= '<div class="flex items-start">';
    $html .= '<i class="' . $config['icon'] . ' mr-3 mt-1 ' . ($config['type'] === 'success' ? 'text-green-600' : 
           ($config['type'] === 'warning' ? 'text-yellow-600' : 'text-red-600')) . '"></i>';
    $html .= '<div>';
    $html .= '<h4 class="font-medium ' . ($config['type'] === 'success' ? 'text-green-800' : 
           ($config['type'] === 'warning' ? 'text-yellow-800' : 'text-red-800')) . '">' . $config['title'] . '</h4>';
    $html .= '<p class="mt-1 ' . ($config['type'] === 'success' ? 'text-green-700' : 
           ($config['type'] === 'warning' ? 'text-yellow-700' : 'text-red-700')) . '">' . htmlspecialchars($message) . '</p>';
    
    if ($details) {
        $html .= '<div class="mt-2 text-sm ' . ($config['type'] === 'success' ? 'text-green-600' : 
               ($config['type'] === 'warning' ? 'text-yellow-600' : 'text-red-600')) . '">';
        $html .= htmlspecialchars($details);
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * Display loading state with error fallback
 */
function displayLoadingWithFallback($loadingMessage = 'Loading...', $errorMessage = 'Failed to load data', $retryUrl = null)
{
    $html = '<div class="loading-container text-center py-8">';
    $html .= '<div class="loading-spinner mb-4">';
    $html .= '<i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i>';
    $html .= '</div>';
    $html .= '<p class="text-gray-600">' . htmlspecialchars($loadingMessage) . '</p>';
    $html .= '</div>';
    
    $html .= '<div class="error-fallback hidden text-center py-8">';
    $html .= '<div class="mb-4">';
    $html .= '<i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>';
    $html .= '</div>';
    $html .= '<p class="text-red-600 mb-4">' . htmlspecialchars($errorMessage) . '</p>';
    
    if ($retryUrl) {
        $html .= '<button onclick="window.location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">';
        $html .= '<i class="fas fa-redo mr-2"></i>Retry';
        $html .= '</button>';
    }
    
    $html .= '</div>';

    return $html;
}

/**
 * Display data table with error handling
 */
function displayDataTable($data, $headers, $emptyMessage = 'No data available', $errorMessage = null)
{
    if ($errorMessage) {
        return displayAlert($errorMessage, 'error');
    }

    if (empty($data)) {
        $html = '<div class="text-center py-8 text-gray-500">';
        $html .= '<i class="fas fa-inbox text-4xl mb-4"></i>';
        $html .= '<p>' . htmlspecialchars($emptyMessage) . '</p>';
        $html .= '</div>';
        return $html;
    }

    $html = '<div class="overflow-x-auto">';
    $html .= '<table class="min-w-full divide-y divide-gray-200">';
    
    // Headers
    $html .= '<thead class="bg-gray-50">';
    $html .= '<tr>';
    foreach ($headers as $header) {
        $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">';
        $html .= htmlspecialchars($header);
        $html .= '</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';
    
    // Body
    $html .= '<tbody class="bg-white divide-y divide-gray-200">';
    foreach ($data as $row) {
        $html .= '<tr class="hover:bg-gray-50">';
        foreach ($row as $cell) {
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">';
            $html .= htmlspecialchars($cell);
            $html .= '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    
    $html .= '</table>';
    $html .= '</div>';

    return $html;
}

/**
 * Display form with error handling
 */
function displayFormWithErrors($formContent, $errors = [], $successMessage = null)
{
    $html = '';
    
    // Display success message
    if ($successMessage) {
        $html .= displayAlert($successMessage, 'success');
    }
    
    // Display validation errors
    if (!empty($errors)) {
        $html .= displayValidationErrors($errors);
    }
    
    // Display form content
    $html .= $formContent;
    
    return $html;
}

/**
 * Display breadcrumb with error context
 */
function displayBreadcrumbWithError($breadcrumbs, $currentPage, $hasError = false)
{
    $html = '<nav class="flex mb-6" aria-label="Breadcrumb">';
    $html .= '<ol class="inline-flex items-center space-x-1 md:space-x-3">';
    
    foreach ($breadcrumbs as $index => $breadcrumb) {
        $html .= '<li class="inline-flex items-center">';
        
        if ($index > 0) {
            $html .= '<i class="fas fa-chevron-right text-gray-400 mx-2"></i>';
        }
        
        if (isset($breadcrumb['url']) && $breadcrumb['url']) {
            $html .= '<a href="' . htmlspecialchars($breadcrumb['url']) . '" class="text-blue-600 hover:text-blue-800">';
            $html .= htmlspecialchars($breadcrumb['title']);
            $html .= '</a>';
        } else {
            $html .= '<span class="text-gray-500">' . htmlspecialchars($breadcrumb['title']) . '</span>';
        }
        
        $html .= '</li>';
    }
    
    // Current page
    $html .= '<li class="inline-flex items-center">';
    $html .= '<i class="fas fa-chevron-right text-gray-400 mx-2"></i>';
    $html .= '<span class="text-gray-900 font-medium">' . htmlspecialchars($currentPage) . '</span>';
    
    if ($hasError) {
        $html .= '<i class="fas fa-exclamation-triangle text-red-500 ml-2" title="Page has errors"></i>';
    }
    
    $html .= '</li>';
    $html .= '</ol>';
    $html .= '</nav>';

    return $html;
}

/**
 * Display pagination with error handling
 */
function displayPagination($currentPage, $totalPages, $baseUrl, $hasErrors = false)
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">';
    
    // Previous button
    $html .= '<div class="flex flex-1 justify-between sm:hidden">';
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>';
    }
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>';
    }
    $html .= '</div>';
    
    // Desktop pagination
    $html .= '<div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">';
    $html .= '<div>';
    $html .= '<p class="text-sm text-gray-700">';
    $html .= 'Page <span class="font-medium">' . $currentPage . '</span> of <span class="font-medium">' . $totalPages . '</span>';
    if ($hasErrors) {
        $html .= ' <span class="text-red-600">(Some data may be unavailable)</span>';
    }
    $html .= '</p>';
    $html .= '</div>';
    
    $html .= '<div>';
    $html .= '<nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">';
        $html .= '<i class="fas fa-chevron-left"></i>';
        $html .= '</a>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">';
        $html .= '<i class="fas fa-chevron-right"></i>';
        $html .= '</a>';
    }
    
    $html .= '</nav>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * JavaScript for enhanced error handling
 */
function getErrorHandlingJS()
{
    return '
    <script>
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener("DOMContentLoaded", function() {
        const alerts = document.querySelectorAll(".alert-dismissible");
        alerts.forEach(function(alert) {
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.style.opacity = "0";
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                }
            }, 5000);
        });
    });

    // Handle loading states
    function showLoading(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            const loading = container.querySelector(".loading-container");
            const error = container.querySelector(".error-fallback");
            if (loading) loading.classList.remove("hidden");
            if (error) error.classList.add("hidden");
        }
    }

    function showError(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            const loading = container.querySelector(".loading-container");
            const error = container.querySelector(".error-fallback");
            if (loading) loading.classList.add("hidden");
            if (error) error.classList.remove("hidden");
        }
    }

    function hideLoading(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            const loading = container.querySelector(".loading-container");
            if (loading) loading.classList.add("hidden");
        }
    }

    // Form validation feedback
    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add("border-red-500");
            let errorDiv = field.parentNode.querySelector(".field-error");
            if (!errorDiv) {
                errorDiv = document.createElement("div");
                errorDiv.className = "field-error text-red-600 text-sm mt-1";
                field.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
        }
    }

    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.remove("border-red-500");
            const errorDiv = field.parentNode.querySelector(".field-error");
            if (errorDiv) {
                errorDiv.remove();
            }
        }
    }

    // Network error handling
    function handleNetworkError(error) {
        console.error("Network error:", error);
        const errorMessage = "Network connection failed. Please check your internet connection and try again.";
        
        // Show global error notification
        const notification = document.createElement("div");
        notification.className = "fixed top-4 right-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg shadow-lg z-50";
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-wifi text-red-500 mr-3"></i>
                <span>${errorMessage}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        document.body.appendChild(notification);
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 10000);
    }
    </script>';
}

/**
 * CSS for error handling animations
 */
function getErrorHandlingCSS()
{
    return '
    <style>
    .animate-fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }

    .animate-slide-up {
        animation: slideUp 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { 
            opacity: 0; 
            transform: translateY(10px); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0); 
        }
    }

    .loading-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .field-error {
        animation: fadeIn 0.2s ease-in-out;
    }

    .alert-dismissible {
        transition: opacity 0.3s ease-in-out;
    }

    .border-red-500 {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 1px #ef4444;
    }
    </style>';
}
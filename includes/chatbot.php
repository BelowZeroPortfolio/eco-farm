<?php

/**
 * Eco-Farm IoT Monitoring System Chatbot Component
 * 
 * Interactive chatbot with predefined questions for helping users
 * navigate and understand the farm monitoring system
 * Click-based interaction - no text input required
 */

// Predefined questions and responses for the Eco-Farm monitoring system
$chatbotQuestions = [
    // Monitoring Category
    [
        'id' => 'sensor_data',
        'question' => 'How do I check my sensor readings?',
        'category' => 'monitoring',
        'icon' => 'fas fa-thermometer-half',
        'response' => 'Go to the <strong>Sensors</strong> page from the navigation menu. You\'ll see real-time readings for temperature, humidity, soil moisture, and light levels. The dashboard also shows a quick overview of all sensor statuses with color-coded indicators.'
    ],
    [
        'id' => 'dashboard_overview',
        'question' => 'What can I see on the Dashboard?',
        'category' => 'monitoring',
        'icon' => 'fas fa-tachometer-alt',
        'response' => 'The <strong>Dashboard</strong> provides a complete overview of your farm: current sensor readings, recent pest detections, plant health status, system alerts, and quick access charts. It\'s your central hub for monitoring everything at a glance.'
    ],
    [
        'id' => 'real_time_data',
        'question' => 'Is the sensor data real-time?',
        'category' => 'monitoring',
        'icon' => 'fas fa-sync-alt',
        'response' => 'Yes! The system syncs sensor data automatically. Arduino sensors push readings every few seconds, and the dashboard updates in real-time. You can also click <strong>Force Refresh</strong> to get the latest readings instantly.'
    ],
    [
        'id' => 'sensor_history',
        'question' => 'How do I view sensor history?',
        'category' => 'monitoring',
        'icon' => 'fas fa-history',
        'response' => 'Visit the <strong>Sensor Readings Log</strong> page to view historical data. You can filter by date range, sensor type, and export the data. The <strong>Data Analytics</strong> page also shows trends and patterns over time.'
    ],
    
    // Pest Detection Category
    [
        'id' => 'pest_detection',
        'question' => 'How does pest detection work?',
        'category' => 'pest',
        'icon' => 'fas fa-bug',
        'response' => 'Our system uses <strong>YOLO AI</strong> (You Only Look Once) to analyze camera images and detect pests in real-time. When pests are detected, you\'ll see them on the <strong>Pest Detection</strong> page with confidence levels, severity ratings, and recommended actions.'
    ],
    [
        'id' => 'yolo_service',
        'question' => 'How do I start the YOLO detection service?',
        'category' => 'pest',
        'icon' => 'fas fa-play-circle',
        'response' => 'Go to <strong>YOLO Service Control</strong> or run <strong>start_yolo_service.bat</strong> from your project folder. The service will start analyzing camera feeds for pests. You can monitor its status and view detection results on the Pest Detection page.'
    ],
    [
        'id' => 'pest_severity',
        'question' => 'What do pest severity levels mean?',
        'category' => 'pest',
        'icon' => 'fas fa-exclamation-circle',
        'response' => 'Severity levels indicate threat urgency: <strong>Low</strong> (monitor situation), <strong>Medium</strong> (take preventive action), <strong>High</strong> (immediate intervention needed), <strong>Critical</strong> (severe infestation requiring urgent response). Configure thresholds in <strong>Pest Severity Config</strong>.'
    ],
    [
        'id' => 'pest_config',
        'question' => 'How do I configure pest detection settings?',
        'category' => 'pest',
        'icon' => 'fas fa-cog',
        'response' => 'Visit <strong>Pest Config</strong> to adjust detection sensitivity, set alert thresholds, configure which pests to monitor, and customize notification preferences. You can also set up automatic actions when certain pests are detected.'
    ],
    
    // Arduino & Hardware Category
    [
        'id' => 'arduino_setup',
        'question' => 'How do I connect Arduino sensors?',
        'category' => 'hardware',
        'icon' => 'fas fa-microchip',
        'response' => 'Connect your Arduino with sensors (DHT11/22, soil moisture, LDR) via USB. Run <strong>start_arduino_bridge.bat</strong> to start the Python bridge that reads sensor data. The system will automatically sync readings to the database.'
    ],
    [
        'id' => 'arduino_service',
        'question' => 'How do I start Arduino monitoring?',
        'category' => 'hardware',
        'icon' => 'fas fa-power-off',
        'response' => 'Use <strong>START_MONITORING.bat</strong> for local monitoring, or <strong>START_MONITORING_WITH_NGROK.bat</strong> for remote access. You can also control the service from the <strong>Arduino Service Control</strong> page in the web interface.'
    ],
    [
        'id' => 'sensor_offline',
        'question' => 'What if my sensors are offline?',
        'category' => 'hardware',
        'icon' => 'fas fa-exclamation-triangle',
        'response' => 'Check these steps: 1) Verify Arduino USB connection, 2) Ensure the bridge service is running, 3) Check COM port settings, 4) Verify sensor wiring. The dashboard shows last communication time for each sensor to help diagnose issues.'
    ],
    [
        'id' => 'ngrok_setup',
        'question' => 'How do I access my farm remotely?',
        'category' => 'hardware',
        'icon' => 'fas fa-globe',
        'response' => 'Use <strong>ngrok</strong> for remote access. Run <strong>START_MONITORING_WITH_NGROK.bat</strong> to create a secure tunnel. Check <strong>ARDUINO_NGROK_SETUP.md</strong> for detailed setup instructions. This lets you monitor your farm from anywhere!'
    ],
    
    // Plants Category
    [
        'id' => 'plant_database',
        'question' => 'How do I manage my plants?',
        'category' => 'plants',
        'icon' => 'fas fa-seedling',
        'response' => 'Visit the <strong>Plant Database</strong> to add, edit, and track your plants. Each plant can have optimal temperature, humidity, and soil moisture ranges. The system will alert you when conditions fall outside these thresholds.'
    ],
    [
        'id' => 'plant_thresholds',
        'question' => 'How do plant threshold alerts work?',
        'category' => 'plants',
        'icon' => 'fas fa-bell',
        'response' => 'Set optimal ranges for each plant in the Plant Database. The system continuously monitors sensor readings and triggers alerts when values exceed thresholds. View violations on the dashboard or <strong>Check Plant Thresholds</strong> page.'
    ],
    [
        'id' => 'plant_violations',
        'question' => 'How do I reset plant violations?',
        'category' => 'plants',
        'icon' => 'fas fa-undo',
        'response' => 'Go to <strong>Reset Plant Violations</strong> to clear violation history after addressing issues. This helps keep your alerts relevant and prevents notification fatigue from resolved problems.'
    ],
    
    // Reports & Analytics Category
    [
        'id' => 'reports',
        'question' => 'How do I generate reports?',
        'category' => 'analytics',
        'icon' => 'fas fa-chart-bar',
        'response' => 'The <strong>Reports</strong> page lets you generate comprehensive analytics. Create daily, weekly, or monthly reports covering sensor trends, pest detections, and plant health. Export as PDF or CSV for record keeping.'
    ],
    [
        'id' => 'data_analytics',
        'question' => 'What analytics are available?',
        'category' => 'analytics',
        'icon' => 'fas fa-chart-line',
        'response' => 'Visit <strong>Data Analytics</strong> for in-depth analysis: temperature/humidity trends, soil moisture patterns, pest detection frequency, correlation analysis, and predictive insights. Use filters to focus on specific time periods or sensors.'
    ],
    [
        'id' => 'data_export',
        'question' => 'Can I export my data?',
        'category' => 'analytics',
        'icon' => 'fas fa-download',
        'response' => 'Yes! Export sensor data, pest logs, and reports from multiple pages. Use the export buttons in <strong>Sensors</strong>, <strong>Reports</strong>, and <strong>Data Analytics</strong>. Data can be exported as CSV, Excel, or PDF.'
    ],
    
    // Alerts & Notifications Category
    [
        'id' => 'notifications',
        'question' => 'How do I manage notifications?',
        'category' => 'alerts',
        'icon' => 'fas fa-bell',
        'response' => 'Visit the <strong>Notifications</strong> page to view all alerts and configure preferences. Set up email notifications, adjust alert thresholds, and customize which events trigger notifications. Mark alerts as read or dismiss them.'
    ],
    [
        'id' => 'alert_types',
        'question' => 'What types of alerts are there?',
        'category' => 'alerts',
        'icon' => 'fas fa-exclamation',
        'response' => 'The system sends alerts for: <strong>Sensor thresholds</strong> (temp, humidity, moisture), <strong>Pest detections</strong> (with severity), <strong>System status</strong> (offline sensors, service issues), and <strong>Plant health</strong> (threshold violations).'
    ],
    
    // Account & Settings Category
    [
        'id' => 'user_roles',
        'question' => 'What are the different user roles?',
        'category' => 'account',
        'icon' => 'fas fa-users',
        'response' => 'Three roles exist: <strong>Admin</strong> (full system access, user management), <strong>Farmer</strong> (monitoring, configuration, reports), and <strong>Student</strong> (read-only access for learning). Admins can manage users in <strong>User Management</strong>.'
    ],
    [
        'id' => 'profile_settings',
        'question' => 'How do I update my profile?',
        'category' => 'account',
        'icon' => 'fas fa-user-cog',
        'response' => 'Go to <strong>Profile</strong> to update your personal information, change password, and set notification preferences. You can also upload a profile picture and configure your dashboard layout.'
    ],
    [
        'id' => 'system_settings',
        'question' => 'Where are system settings?',
        'category' => 'account',
        'icon' => 'fas fa-cogs',
        'response' => 'Access <strong>Settings</strong> to configure system-wide options: sensor sync intervals, default thresholds, display preferences, timezone, and integration settings. Admin users have access to additional configuration options.'
    ],
    
    // Help & Learning Category
    [
        'id' => 'learning_resources',
        'question' => 'Where can I learn more about farming?',
        'category' => 'help',
        'icon' => 'fas fa-graduation-cap',
        'response' => 'Visit <strong>Learning Resources</strong> for educational content about IoT farming, sensor technology, pest management, and sustainable agriculture. Great for students and anyone wanting to deepen their knowledge!'
    ],
    [
        'id' => 'help_page',
        'question' => 'Where can I get more help?',
        'category' => 'help',
        'icon' => 'fas fa-question-circle',
        'response' => 'Check the <strong>Help</strong> page for detailed documentation, FAQs, and troubleshooting guides. You can also find setup instructions for Arduino, YOLO service, and ngrok remote access.'
    ],
    [
        'id' => 'troubleshooting',
        'question' => 'How do I troubleshoot issues?',
        'category' => 'help',
        'icon' => 'fas fa-wrench',
        'response' => 'Start with the <strong>Dashboard</strong> to check system status. View <strong>Logs</strong> for error details. Common fixes: restart services, check connections, verify configurations. The Help page has specific troubleshooting guides.'
    ]
];

/**
 * Get questions by category
 */
function getChatbotQuestionsByCategory($questions, $category = null)
{
    if ($category === null) {
        return $questions;
    }
    return array_filter($questions, function ($q) use ($category) {
        return $q['category'] === $category;
    });
}

/**
 * Get chatbot response by question ID
 */
function getChatbotResponse($questions, $questionId)
{
    foreach ($questions as $question) {
        if ($question['id'] === $questionId) {
            return $question;
        }
    }
    return null;
}

/**
 * Get unique categories
 */
function getChatbotCategories($questions)
{
    $categories = [];
    foreach ($questions as $question) {
        if (!in_array($question['category'], $categories)) {
            $categories[] = $question['category'];
        }
    }
    return $categories;
}
?>

<!-- Chatbot Widget -->
<div id="chatbot-widget" class="fixed bottom-4 right-4 lg:bottom-6 lg:right-6 z-50">
    <!-- Chatbot Toggle Button -->
    <button id="chatbot-toggle"
        class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-full p-3 lg:p-4 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-green-300"
        onclick="toggleChatbot()"
        title="Farm Assistant">
        <i class="fas fa-comments text-lg lg:text-xl"></i>
        <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
    </button>

    <!-- Chatbot Panel -->
    <div id="chatbot-panel"
        class="hidden fixed lg:absolute bottom-0 left-0 right-0 lg:bottom-16 lg:right-0 lg:left-auto w-full lg:w-96 bg-white dark:bg-gray-800 lg:rounded-2xl rounded-t-2xl shadow-2xl border-t lg:border border-gray-200 dark:border-gray-700 overflow-hidden">

        <!-- Chatbot Header -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-robot text-lg"></i>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white"></div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">Eco-Farm Assistant</h3>
                        <p class="text-green-100 text-sm flex items-center">
                            <span class="w-2 h-2 bg-green-300 rounded-full mr-2 animate-pulse"></span>
                            Click a question to get help
                        </p>
                    </div>
                </div>
                <button onclick="toggleChatbot()"
                    class="text-white/80 hover:text-white transition-colors duration-200 p-1 rounded-full hover:bg-white/10">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Chat Messages Area -->
        <div class="h-[50vh] lg:h-96 flex flex-col bg-gray-50 dark:bg-gray-900">
            <!-- Messages Container -->
            <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4">
                <!-- Welcome Message -->
                <div class="flex items-start space-x-3 animate-fade-in">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-robot text-white text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-md p-3 shadow-sm border border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-800 dark:text-gray-200">
                                👋 Hi! I'm your Eco-Farm Assistant. Click any question below to get instant help with your farm monitoring system!
                            </p>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-3">
                            Just now
                        </div>
                    </div>
                </div>

                <!-- Quick Action Buttons - Show first 4 popular questions -->
                <div id="quick-actions" class="flex flex-wrap gap-2 ml-11 animate-fade-in" style="animation-delay: 0.2s;">
                    <?php 
                    $popularQuestions = ['sensor_data', 'pest_detection', 'arduino_setup', 'dashboard_overview'];
                    foreach ($chatbotQuestions as $question): 
                        if (in_array($question['id'], $popularQuestions)):
                    ?>
                        <button onclick="askQuestion('<?php echo $question['id']; ?>', '<?php echo htmlspecialchars($question['question'], ENT_QUOTES); ?>')"
                            class="quick-action-btn bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:border-green-300 rounded-full px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200 flex items-center space-x-2">
                            <i class="<?php echo $question['icon']; ?> text-xs"></i>
                            <span><?php echo htmlspecialchars($question['question']); ?></span>
                        </button>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>

                <!-- More Options Button -->
                <div class="ml-11 animate-fade-in" style="animation-delay: 0.4s;">
                    <button onclick="showAllQuestions()"
                        class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-full px-4 py-2 text-xs font-medium transition-all duration-200 flex items-center space-x-2 shadow-sm hover:shadow-md">
                        <i class="fas fa-list text-xs"></i>
                        <span>Browse all topics (<?php echo count($chatbotQuestions); ?> questions)</span>
                    </button>
                </div>
            </div>

            <!-- All Questions Modal -->
            <div id="all-questions-modal" class="hidden absolute inset-0 bg-white dark:bg-gray-800 z-10">
                <div class="flex flex-col h-full">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-500 to-green-600 text-white">
                        <h4 class="font-semibold">Help Topics</h4>
                        <button onclick="hideAllQuestions()"
                            class="text-white/80 hover:text-white p-1 rounded-full hover:bg-white/10">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Category Filters -->
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <div class="flex flex-wrap gap-2">
                            <button onclick="filterQuestions('all')"
                                class="category-filter active px-3 py-1.5 text-xs rounded-full bg-green-500 text-white transition-colors duration-200">
                                All
                            </button>
                            <button onclick="filterQuestions('monitoring')"
                                class="category-filter px-3 py-1.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900 transition-colors duration-200">
                                <i class="fas fa-chart-line mr-1"></i>Monitoring
                            </button>
                            <button onclick="filterQuestions('pest')"
                                class="category-filter px-3 py-1.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900 transition-colors duration-200">
                                <i class="fas fa-bug mr-1"></i>Pest Detection
                            </button>
                            <button onclick="filterQuestions('hardware')"
                                class="category-filter px-3 py-1.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900 transition-colors duration-200">
                                <i class="fas fa-microchip mr-1"></i>Hardware
                            </button>
                            <button onclick="filterQuestions('plants')"
                                class="category-filter px-3 py-1.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900 transition-colors duration-200">
                                <i class="fas fa-seedling mr-1"></i>Plants
                            </button>
                            <button onclick="filterQuestions('analytics')"
                                class="category-filter px-3 py-1.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900 transition-colors duration-200">
                                <i class="fas fa-chart-bar mr-1"></i>Analytics
                            </button>
                            <button onclick="filterQuestions('alerts')"
                                class="category-filter px-3 py-1.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900 transition-colors duration-200">
                                <i class="fas fa-bell mr-1"></i>Alerts
                            </button>
                            <button onclick="filterQuestions('account')"
                                class="category-filter px-3 py-1.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900 transition-colors duration-200">
                                <i class="fas fa-user mr-1"></i>Account
                            </button>
                            <button onclick="filterQuestions('help')"
                                class="category-filter px-3 py-1.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-900 transition-colors duration-200">
                                <i class="fas fa-question-circle mr-1"></i>Help
                            </button>
                        </div>
                    </div>

                    <!-- Questions List -->
                    <div class="flex-1 overflow-y-auto p-3">
                        <div class="space-y-2" id="questions-container">
                            <?php foreach ($chatbotQuestions as $question): ?>
                                <button onclick="askQuestion('<?php echo $question['id']; ?>', '<?php echo htmlspecialchars($question['question'], ENT_QUOTES); ?>')"
                                    class="question-item w-full text-left p-3 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-green-400 hover:bg-green-50 dark:hover:bg-gray-700 transition-all duration-200 group"
                                    data-category="<?php echo $question['category']; ?>">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-800 transition-colors duration-200">
                                            <i class="<?php echo $question['icon']; ?> text-green-600 dark:text-green-400"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-300 transition-colors duration-200">
                                                <?php echo htmlspecialchars($question['question']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 capitalize mt-0.5">
                                                <?php echo $question['category']; ?>
                                            </p>
                                        </div>
                                        <i class="fas fa-chevron-right text-gray-400 text-sm group-hover:text-green-500 group-hover:translate-x-1 transition-all duration-200"></i>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Typing Indicator -->
            <div id="typing-indicator" class="hidden px-4 pb-2">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-robot text-white text-sm"></i>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-md p-3 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar - Simplified without input -->
            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                        Click any question above for instant help
                    </p>
                    <button onclick="clearChat()"
                        class="text-xs text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400 transition-colors duration-200 flex items-center space-x-1 px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-redo"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Chatbot JavaScript -->
<script>
    // Chatbot questions data
    const chatbotQuestions = <?php echo json_encode($chatbotQuestions); ?>;

    // Chat state management
    let chatHistory = [];
    let isTyping = false;

    /**
     * Toggle chatbot panel visibility
     */
    function toggleChatbot() {
        const panel = document.getElementById('chatbot-panel');
        const button = document.getElementById('chatbot-toggle');

        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            panel.classList.add('animate-fade-in');
            button.querySelector('.fa-comments').className = 'fas fa-times text-xl';

            // Remove notification dot
            const notificationDot = button.querySelector('.animate-pulse');
            if (notificationDot) {
                notificationDot.remove();
            }
        } else {
            panel.classList.add('hidden');
            button.querySelector('.fas').className = 'fas fa-comments text-xl';
            hideAllQuestions();
        }
    }

    /**
     * Show all questions modal
     */
    function showAllQuestions() {
        const modal = document.getElementById('all-questions-modal');
        modal.classList.remove('hidden');
        modal.classList.add('animate-fade-in');
    }

    /**
     * Hide all questions modal
     */
    function hideAllQuestions() {
        const modal = document.getElementById('all-questions-modal');
        modal.classList.add('hidden');
    }

    /**
     * Filter questions by category
     */
    function filterQuestions(category) {
        const questions = document.querySelectorAll('.question-item');
        const filters = document.querySelectorAll('.category-filter');

        // Update filter buttons
        filters.forEach(filter => {
            filter.classList.remove('active', 'bg-green-500', 'text-white');
            filter.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
        });

        event.target.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
        event.target.classList.add('active', 'bg-green-500', 'text-white');

        // Filter questions
        questions.forEach(question => {
            const questionCategory = question.getAttribute('data-category');
            if (category === 'all' || questionCategory === category) {
                question.style.display = 'block';
            } else {
                question.style.display = 'none';
            }
        });
    }

    /**
     * Ask a specific question (from quick actions or question list)
     */
    function askQuestion(questionId, questionText) {
        // Add user message to chat
        addUserMessage(questionText);

        // Hide quick actions
        const quickActions = document.getElementById('quick-actions');
        if (quickActions) {
            quickActions.style.display = 'none';
        }

        // Hide "browse all" button after first question
        const browseBtn = document.querySelector('[onclick="showAllQuestions()"]');
        if (browseBtn && browseBtn.closest('.ml-11')) {
            browseBtn.closest('.ml-11').style.display = 'none';
        }

        // Hide all questions modal if open
        hideAllQuestions();

        // Show typing indicator and respond
        showTypingIndicator();

        setTimeout(() => {
            hideTypingIndicator();
            const question = chatbotQuestions.find(q => q.id === questionId);
            if (question) {
                addBotMessage(question.response, question.icon);
                addRelatedQuestions(questionId);
            }
        }, 800 + Math.random() * 500);
    }

    /**
     * Add user message to chat
     */
    function addUserMessage(message) {
        const messagesContainer = document.getElementById('chat-messages');
        const messageElement = document.createElement('div');
        messageElement.className = 'flex items-start space-x-3 justify-end animate-fade-in';

        messageElement.innerHTML = `
            <div class="flex-1 flex justify-end">
                <div class="bg-green-500 text-white rounded-2xl rounded-tr-md p-3 shadow-sm max-w-[80%]">
                    <p class="text-sm">${escapeHtml(message)}</p>
                </div>
            </div>
            <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-white text-sm"></i>
            </div>
        `;

        messagesContainer.appendChild(messageElement);
        scrollToBottom();

        chatHistory.push({ type: 'user', message: message, timestamp: new Date() });
    }

    /**
     * Add bot message to chat
     */
    function addBotMessage(message, icon = 'fas fa-robot') {
        const messagesContainer = document.getElementById('chat-messages');
        const messageElement = document.createElement('div');
        messageElement.className = 'flex items-start space-x-3 animate-fade-in';

        messageElement.innerHTML = `
            <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                <i class="${icon} text-white text-sm"></i>
            </div>
            <div class="flex-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-md p-3 shadow-sm border border-gray-200 dark:border-gray-700 max-w-[90%]">
                    <div class="text-sm text-gray-800 dark:text-gray-200">${message}</div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-3">
                    ${formatTime(new Date())}
                </div>
            </div>
        `;

        messagesContainer.appendChild(messageElement);
        scrollToBottom();

        chatHistory.push({ type: 'bot', message: message, timestamp: new Date() });
    }

    /**
     * Show typing indicator
     */
    function showTypingIndicator() {
        const indicator = document.getElementById('typing-indicator');
        indicator.classList.remove('hidden');
        isTyping = true;
        scrollToBottom();
    }

    /**
     * Hide typing indicator
     */
    function hideTypingIndicator() {
        const indicator = document.getElementById('typing-indicator');
        indicator.classList.add('hidden');
        isTyping = false;
    }

    /**
     * Add related question buttons after bot response
     */
    function addRelatedQuestions(excludeQuestionId) {
        const messagesContainer = document.getElementById('chat-messages');
        const actionsElement = document.createElement('div');
        actionsElement.className = 'ml-11 animate-fade-in mt-2';

        // Get current question's category
        const currentQuestion = chatbotQuestions.find(q => q.id === excludeQuestionId);
        const currentCategory = currentQuestion ? currentQuestion.category : null;

        // Get related questions (same category first, then others)
        let relatedQuestions = chatbotQuestions
            .filter(q => q.id !== excludeQuestionId)
            .sort((a, b) => {
                if (a.category === currentCategory && b.category !== currentCategory) return -1;
                if (b.category === currentCategory && a.category !== currentCategory) return 1;
                return 0;
            })
            .slice(0, 3);

        let actionsHTML = '<p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Related questions:</p><div class="flex flex-wrap gap-2">';
        relatedQuestions.forEach(question => {
            actionsHTML += `
                <button onclick="askQuestion('${question.id}', '${escapeHtml(question.question).replace(/'/g, "\\'")}')"
                    class="bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:border-green-400 rounded-full px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200 flex items-center space-x-1">
                    <i class="${question.icon} text-xs opacity-60"></i>
                    <span>${escapeHtml(question.question)}</span>
                </button>
            `;
        });
        actionsHTML += '</div>';

        // Add "Browse all" button
        actionsHTML += `
            <button onclick="showAllQuestions()" 
                class="mt-2 text-xs text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 flex items-center space-x-1 transition-colors duration-200">
                <i class="fas fa-th-list"></i>
                <span>Browse all topics</span>
            </button>
        `;

        actionsElement.innerHTML = actionsHTML;
        messagesContainer.appendChild(actionsElement);
        scrollToBottom();
    }

    /**
     * Clear chat history and reset to initial state
     */
    function clearChat() {
        const messagesContainer = document.getElementById('chat-messages');

        // Remove all children except first 3 (welcome message, quick actions, browse button)
        while (messagesContainer.children.length > 3) {
            messagesContainer.removeChild(messagesContainer.lastChild);
        }

        // Show quick actions again
        const quickActions = document.getElementById('quick-actions');
        if (quickActions) {
            quickActions.style.display = 'flex';
        }

        // Show browse button again
        const browseBtn = document.querySelector('[onclick="showAllQuestions()"]');
        if (browseBtn && browseBtn.closest('.ml-11')) {
            browseBtn.closest('.ml-11').style.display = 'block';
        }

        chatHistory = [];
    }

    /**
     * Scroll chat to bottom
     */
    function scrollToBottom() {
        const messagesContainer = document.getElementById('chat-messages');
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    }

    /**
     * Format time for message timestamps
     */
    function formatTime(date) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Auto-show chatbot hint on first visit
    document.addEventListener('DOMContentLoaded', function() {
        const hasSeenChatbot = localStorage.getItem('hasSeenEcoFarmChatbot');

        if (!hasSeenChatbot) {
            setTimeout(() => {
                const button = document.getElementById('chatbot-toggle');
                button.classList.add('animate-bounce-subtle');

                setTimeout(() => {
                    button.classList.remove('animate-bounce-subtle');
                    localStorage.setItem('hasSeenEcoFarmChatbot', 'true');
                }, 3000);
            }, 2000);
        }
    });

    // Close chatbot when clicking outside
    document.addEventListener('click', function(event) {
        const chatbot = document.getElementById('chatbot-widget');
        const panel = document.getElementById('chatbot-panel');

        if (!chatbot.contains(event.target) && !panel.classList.contains('hidden')) {
            toggleChatbot();
        }
    });
</script>

<!-- Chatbot CSS -->
<style>
    /* Animations */
    @keyframes bounceSubtle {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-bounce-subtle {
        animation: bounceSubtle 1s ease-in-out infinite;
    }

    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }

    /* Quick action buttons */
    .quick-action-btn {
        transition: all 0.2s ease;
    }

    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Scrollbar styling */
    #chat-messages::-webkit-scrollbar,
    #questions-container::-webkit-scrollbar {
        width: 4px;
    }

    #chat-messages::-webkit-scrollbar-track,
    #questions-container::-webkit-scrollbar-track {
        background: transparent;
    }

    #chat-messages::-webkit-scrollbar-thumb,
    #questions-container::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.5);
        border-radius: 2px;
    }

    /* Question item hover */
    .question-item {
        transition: all 0.2s ease;
    }

    .question-item:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Category filter */
    .category-filter {
        transition: all 0.2s ease;
    }

    .category-filter:hover {
        transform: translateY(-1px);
    }

    .category-filter.active {
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
    }

    /* Modal animation */
    #all-questions-modal {
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive */
    @media (max-width: 1024px) {
        #chatbot-panel {
            max-height: 70vh;
        }
    }

    /* Dark mode */
    .dark #chatbot-panel {
        background: rgb(17 24 39);
    }

    .dark .question-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
</style>

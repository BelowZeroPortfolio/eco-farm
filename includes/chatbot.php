<?php

/**
 * IoT Farm Monitoring System Chatbot Component
 * 
 * Interactive chatbot with predefined questions and AI-powered responses
 * for helping users navigate and understand the farm monitoring system
 */

// Predefined questions and responses for the IoT farm monitoring system
$chatbotQuestions = [
    [
        'id' => 'sensor_data',
        'question' => 'How do I check my sensor readings?',
        'category' => 'monitoring',
        'icon' => 'fas fa-thermometer-half',
        'response' => 'To check your sensor readings, go to the <strong>Sensors</strong> page from the navigation menu. There you can view real-time temperature, humidity, soil moisture, and light levels. You can also set up alerts for specific thresholds and view historical data trends.'
    ],
    [
        'id' => 'pest_detection',
        'question' => 'How does pest detection work?',
        'category' => 'monitoring',
        'icon' => 'fas fa-bug',
        'response' => 'Our AI-powered pest detection system uses computer vision to analyze images from your field cameras. Visit the <strong>Pest Detection</strong> page to see detected pests, confidence levels, and recommended actions. The system automatically alerts you when pests are detected above threshold levels.'
    ],
    [
        'id' => 'camera_setup',
        'question' => 'How do I set up cameras?',
        'category' => 'setup',
        'icon' => 'fas fa-video',
        'response' => 'Camera setup is available in the <strong>Camera Management</strong> section. You can add new cameras, configure detection zones, set recording schedules, and adjust AI detection sensitivity. Make sure your cameras are connected to the same network as your monitoring system.'
    ],
    [
        'id' => 'alerts',
        'question' => 'How do I manage notifications?',
        'category' => 'alerts',
        'icon' => 'fas fa-bell',
        'response' => 'Visit the <strong>Notifications</strong> page to manage all your alerts. You can set up email notifications, SMS alerts, and in-app notifications for sensor thresholds, pest detections, system status, and maintenance reminders. Customize notification preferences for different alert types.'
    ],
    [
        'id' => 'reports',
        'question' => 'How do I generate reports?',
        'category' => 'analytics',
        'icon' => 'fas fa-chart-bar',
        'response' => 'The <strong>Reports</strong> section allows you to generate comprehensive analytics reports. You can create daily, weekly, or monthly reports covering sensor data trends, pest detection summaries, system performance, and crop health insights. Reports can be exported as PDF or CSV files.'
    ],
    [
        'id' => 'troubleshooting',
        'question' => 'What if my sensors are offline?',
        'category' => 'troubleshooting',
        'icon' => 'fas fa-exclamation-triangle',
        'response' => 'If sensors appear offline, first check the <strong>Dashboard</strong> for system status. Verify power connections, network connectivity, and battery levels. The system will show last communication time for each sensor. Contact support if sensors remain offline after basic troubleshooting.'
    ],
    [
        'id' => 'data_export',
        'question' => 'Can I export my data?',
        'category' => 'data',
        'icon' => 'fas fa-download',
        'response' => 'Yes! You can export sensor data, pest detection logs, and reports from multiple sections. Use the export buttons in the <strong>Sensors</strong> and <strong>Reports</strong> pages. Data can be exported in CSV, Excel, or PDF formats for further analysis or record keeping.'
    ],
    [
        'id' => 'user_roles',
        'question' => 'What are the different user roles?',
        'category' => 'users',
        'icon' => 'fas fa-users',
        'response' => 'The system has three user roles: <strong>Admin</strong> (full system access), <strong>Farmer</strong> (monitoring and management), and <strong>Student</strong> (read-only access for learning). Admins can manage users, farmers can configure sensors and cameras, while students can view data and reports.'
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
                        <h3 class="font-semibold text-lg">Farm Assistant</h3>
                        <p class="text-green-100 text-sm flex items-center">
                            <span class="w-2 h-2 bg-green-300 rounded-full mr-2 animate-pulse"></span>
                            Online now
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
                                👋 Hi! I'm your Farm Assistant. I can help you with:
                            </p>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-3">
                            Just now
                        </div>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div id="quick-actions" class="flex flex-wrap gap-2 ml-11 animate-fade-in" style="animation-delay: 0.2s;">
                    <?php foreach (array_slice($chatbotQuestions, 0, 4) as $question): ?>
                        <button onclick="askQuestion('<?php echo $question['id']; ?>', '<?php echo htmlspecialchars($question['question'], ENT_QUOTES); ?>')"
                            class="quick-action-btn bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:border-green-300 rounded-full px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200 flex items-center space-x-2">
                            <i class="<?php echo $question['icon']; ?> text-xs"></i>
                            <span><?php echo htmlspecialchars($question['question']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- More Options Button -->
                <div class="ml-11 animate-fade-in" style="animation-delay: 0.4s;">
                    <button onclick="showAllQuestions()"
                        class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-full px-4 py-2 text-xs font-medium transition-all duration-200 flex items-center space-x-2 shadow-sm hover:shadow-md">
                        <i class="fas fa-ellipsis-h text-xs"></i>
                        <span>More help topics</span>
                    </button>
                </div>
            </div>

            <!-- All Questions Modal -->
            <div id="all-questions-modal" class="hidden absolute inset-0 bg-white dark:bg-gray-800 z-10">
                <div class="flex flex-col h-full">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Help Topics</h4>
                        <button onclick="hideAllQuestions()"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Category Filters -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap gap-2">
                            <button onclick="filterQuestions('all')"
                                class="category-filter active px-3 py-1 text-xs rounded-full bg-green-100 text-green-700 hover:bg-green-200 transition-colors duration-200">
                                All
                            </button>
                            <button onclick="filterQuestions('monitoring')"
                                class="category-filter px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors duration-200">
                                Monitoring
                            </button>
                            <button onclick="filterQuestions('setup')"
                                class="category-filter px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors duration-200">
                                Setup
                            </button>
                            <button onclick="filterQuestions('alerts')"
                                class="category-filter px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors duration-200">
                                Alerts
                            </button>
                        </div>
                    </div>

                    <!-- Questions List -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <div class="space-y-2" id="questions-container">
                            <?php foreach ($chatbotQuestions as $question): ?>
                                <button onclick="askQuestion('<?php echo $question['id']; ?>', '<?php echo htmlspecialchars($question['question'], ENT_QUOTES); ?>')"
                                    class="question-item w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-green-300 hover:bg-green-50 dark:hover:bg-gray-700 transition-all duration-200 group"
                                    data-category="<?php echo $question['category']; ?>">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-800 transition-colors duration-200">
                                            <i class="<?php echo $question['icon']; ?> text-green-600 dark:text-green-400 text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-300 transition-colors duration-200">
                                                <?php echo htmlspecialchars($question['question']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">
                                                <?php echo $question['category']; ?>
                                            </p>
                                        </div>
                                        <i class="fas fa-chevron-right text-gray-400 text-xs group-hover:text-green-500 transition-colors duration-200"></i>
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

            <!-- Input Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="flex items-end space-x-3">
                    <div class="flex-1">
                        <input type="text"
                            id="chatbot-input"
                            placeholder="Ask me anything about your farm..."
                            class="w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white resize-none"
                            onkeypress="handleChatbotInput(event)">
                    </div>
                    <button onclick="sendChatbotMessage()"
                        id="send-button"
                        class="bg-green-500 hover:bg-green-600 disabled:bg-gray-300 text-white rounded-full p-3 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-paper-plane text-sm"></i>
                    </button>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <i class="fas fa-lightbulb mr-1"></i>
                        Try asking about sensors, pests, or reports
                    </p>
                    <div class="flex items-center space-x-2">
                        <button onclick="clearChat()"
                            class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors duration-200">
                            <i class="fas fa-trash mr-1"></i>
                            Clear
                        </button>
                    </div>
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

            // Focus on input
            setTimeout(() => {
                document.getElementById('chatbot-input').focus();
            }, 300);
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
            filter.classList.remove('active', 'bg-green-100', 'text-green-700');
            filter.classList.add('bg-gray-100', 'text-gray-700');
        });

        event.target.classList.remove('bg-gray-100', 'text-gray-700');
        event.target.classList.add('active', 'bg-green-100', 'text-green-700');

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

        // Hide all questions modal if open
        hideAllQuestions();

        // Show typing indicator and respond
        showTypingIndicator();

        setTimeout(() => {
            hideTypingIndicator();
            const question = chatbotQuestions.find(q => q.id === questionId);
            if (question) {
                addBotMessage(question.response, question.icon);
                addQuickActions(questionId);
            }
        }, 1500);
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
            <div class="bg-green-500 text-white rounded-2xl rounded-tr-md p-3 shadow-sm max-w-xs">
                <p class="text-sm">${message}</p>
            </div>
        </div>
        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
            <i class="fas fa-user text-white text-sm"></i>
        </div>
    `;

        messagesContainer.appendChild(messageElement);
        scrollToBottom();

        // Add to chat history
        chatHistory.push({
            type: 'user',
            message: message,
            timestamp: new Date()
        });
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
            <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-md p-3 shadow-sm border border-gray-200 dark:border-gray-700 max-w-xs">
                <div class="text-sm text-gray-800 dark:text-gray-200">${message}</div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-3">
                ${formatTime(new Date())}
            </div>
        </div>
    `;

        messagesContainer.appendChild(messageElement);
        scrollToBottom();

        // Add to chat history
        chatHistory.push({
            type: 'bot',
            message: message,
            timestamp: new Date()
        });
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
     * Add quick action buttons after bot response
     */
    function addQuickActions(excludeQuestionId) {
        const messagesContainer = document.getElementById('chat-messages');
        const actionsElement = document.createElement('div');
        actionsElement.className = 'ml-11 animate-fade-in';

        // Get 2-3 related questions (excluding the current one)
        const relatedQuestions = chatbotQuestions
            .filter(q => q.id !== excludeQuestionId)
            .slice(0, 3);

        let actionsHTML = '<div class="flex flex-wrap gap-2">';
        relatedQuestions.forEach(question => {
            actionsHTML += `
            <button onclick="askQuestion('${question.id}', '${question.question.replace(/'/g, "\\'")}')"
                    class="bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:border-green-300 rounded-full px-3 py-1 text-xs text-gray-700 dark:text-gray-300 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200">
                ${question.question}
            </button>
        `;
        });
        actionsHTML += '</div>';

        actionsElement.innerHTML = actionsHTML;
        messagesContainer.appendChild(actionsElement);
        scrollToBottom();
    }

    /**
     * Handle chatbot input keypress
     */
    function handleChatbotInput(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendChatbotMessage();
        }
    }

    /**
     * Send chatbot message
     */
    function sendChatbotMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();

        if (!message || isTyping) return;

        // Add user message
        addUserMessage(message);
        input.value = '';

        // Hide quick actions if visible
        const quickActions = document.getElementById('quick-actions');
        if (quickActions) {
            quickActions.style.display = 'none';
        }

        // Show typing indicator
        showTypingIndicator();

        // Process message and respond
        setTimeout(() => {
            hideTypingIndicator();
            processUserMessage(message);
        }, 1000 + Math.random() * 1000); // Random delay for realism
    }

    /**
     * Process user message and generate response
     */
    function processUserMessage(message) {
        const lowerMessage = message.toLowerCase();

        // Enhanced keyword matching
        const keywords = {
            'sensor': 'sensor_data',
            'temperature': 'sensor_data',
            'humidity': 'sensor_data',
            'moisture': 'sensor_data',
            'pest': 'pest_detection',
            'bug': 'pest_detection',
            'insect': 'pest_detection',
            'camera': 'camera_setup',
            'video': 'camera_setup',
            'setup': 'camera_setup',
            'alert': 'alerts',
            'notification': 'alerts',
            'notify': 'alerts',
            'report': 'reports',
            'analytics': 'reports',
            'export': 'data_export',
            'download': 'data_export',
            'offline': 'troubleshooting',
            'problem': 'troubleshooting',
            'issue': 'troubleshooting',
            'user': 'user_roles',
            'role': 'user_roles',
            'permission': 'user_roles'
        };

        let matchedQuestionId = null;
        let matchScore = 0;

        // Find best matching question
        for (const [keyword, questionId] of Object.entries(keywords)) {
            if (lowerMessage.includes(keyword)) {
                const score = keyword.length; // Longer keywords get higher priority
                if (score > matchScore) {
                    matchedQuestionId = questionId;
                    matchScore = score;
                }
            }
        }

        if (matchedQuestionId) {
            const question = chatbotQuestions.find(q => q.id === matchedQuestionId);
            addBotMessage(question.response, question.icon);
            addQuickActions(matchedQuestionId);
        } else {
            // Generate contextual response
            let response = generateContextualResponse(message);
            addBotMessage(response);

            // Add suggestion to browse topics
            setTimeout(() => {
                const messagesContainer = document.getElementById('chat-messages');
                const suggestionElement = document.createElement('div');
                suggestionElement.className = 'ml-11 animate-fade-in';
                suggestionElement.innerHTML = `
                <button onclick="showAllQuestions()" 
                        class="bg-blue-50 hover:bg-blue-100 dark:bg-blue-900 dark:hover:bg-blue-800 border border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 rounded-full px-3 py-2 text-xs transition-all duration-200 flex items-center space-x-2">
                    <i class="fas fa-list text-xs"></i>
                    <span>Browse all help topics</span>
                </button>
            `;
                messagesContainer.appendChild(suggestionElement);
                scrollToBottom();
            }, 500);
        }
    }

    /**
     * Generate contextual response for unmatched queries
     */
    function generateContextualResponse(message) {
        const responses = [
            "I understand you're asking about \"" + message + "\". While I don't have a specific answer for that, I can help you with sensor monitoring, pest detection, camera setup, and more!",
            "That's an interesting question about \"" + message + "\". Let me suggest some related topics that might help you.",
            "I'm not sure about \"" + message + "\" specifically, but I have lots of information about farm monitoring systems. What would you like to know?",
            "Thanks for asking about \"" + message + "\". I specialize in helping with IoT farm monitoring. Is there something specific about sensors, cameras, or reports you'd like to know?"
        ];

        return responses[Math.floor(Math.random() * responses.length)];
    }

    /**
     * Clear chat history
     */
    function clearChat() {
        const messagesContainer = document.getElementById('chat-messages');

        // Keep only the welcome message and initial quick actions
        const children = Array.from(messagesContainer.children);
        children.forEach((child, index) => {
            if (index > 2) { // Keep first 3 elements (welcome + quick actions + more button)
                child.remove();
            }
        });

        // Show quick actions again
        const quickActions = document.getElementById('quick-actions');
        if (quickActions) {
            quickActions.style.display = 'flex';
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
        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Mark response as helpful
     */
    function markHelpful(questionId) {
        // Simple feedback implementation
        event.target.innerHTML = '<i class="fas fa-check mr-1"></i>Thanks!';
        event.target.classList.remove('text-green-600', 'hover:text-green-700');
        event.target.classList.add('text-gray-500');
        event.target.disabled = true;

        // You could send this feedback to your analytics system
        console.log(`User found question ${questionId} helpful`);
    }

    // Auto-show chatbot on first visit (optional)
    document.addEventListener('DOMContentLoaded', function() {
        // Check if user has seen chatbot before
        const hasSeenChatbot = localStorage.getItem('hasSeenChatbot');

        if (!hasSeenChatbot) {
            // Show a subtle animation to draw attention
            setTimeout(() => {
                const button = document.getElementById('chatbot-toggle');
                button.classList.add('animate-bounce-subtle');

                // Stop animation after a few seconds
                setTimeout(() => {
                    button.classList.remove('animate-bounce-subtle');
                    localStorage.setItem('hasSeenChatbot', 'true');
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

<!-- Enhanced CSS for chat interface -->
<style>
    /* Chat animations */
    @keyframes bounceSubtle {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-5px);
        }
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

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .animate-bounce-subtle {
        animation: bounceSubtle 1s ease-in-out infinite;
    }

    .animate-fade-in {
        animation: fadeIn 0.4s ease-out;
    }

    .animate-slide-in-right {
        animation: slideInRight 0.3s ease-out;
    }

    .animate-slide-in-left {
        animation: slideInLeft 0.3s ease-out;
    }

    /* Chat message styling */
    .chat-message-user {
        animation: slideInRight 0.3s ease-out;
    }

    .chat-message-bot {
        animation: slideInLeft 0.3s ease-out;
    }

    /* Enhanced message bubbles */
    .message-bubble {
        position: relative;
        word-wrap: break-word;
        max-width: 85%;
    }

    .message-bubble::before {
        content: '';
        position: absolute;
        width: 0;
        height: 0;
    }

    /* User message bubble tail */
    .user-bubble::before {
        right: -8px;
        top: 10px;
        border-left: 8px solid #10b981;
        border-top: 8px solid transparent;
        border-bottom: 8px solid transparent;
    }

    /* Bot message bubble tail */
    .bot-bubble::before {
        left: -8px;
        top: 10px;
        border-right: 8px solid white;
        border-top: 8px solid transparent;
        border-bottom: 8px solid transparent;
    }

    .dark .bot-bubble::before {
        border-right-color: rgb(31 41 55);
    }

    /* Quick action buttons */
    .quick-action-btn {
        transition: all 0.2s ease;
        backdrop-filter: blur(10px);
    }

    .quick-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Typing indicator animation */
    .typing-dot {
        animation: typing 1.4s infinite ease-in-out;
    }

    .typing-dot:nth-child(1) {
        animation-delay: 0s;
    }

    .typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {

        0%,
        60%,
        100% {
            transform: translateY(0);
            opacity: 0.4;
        }

        30% {
            transform: translateY(-10px);
            opacity: 1;
        }
    }

    /* Scrollbar styling for chat */
    #chat-messages::-webkit-scrollbar {
        width: 4px;
    }

    #chat-messages::-webkit-scrollbar-track {
        background: transparent;
    }

    #chat-messages::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.5);
        border-radius: 2px;
    }

    #chat-messages::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.7);
    }

    /* Enhanced input styling */
    #chatbot-input {
        transition: all 0.2s ease;
    }

    #chatbot-input:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);
    }

    /* Send button animation */
    #send-button {
        transition: all 0.2s ease;
    }

    #send-button:hover {
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    #send-button:active {
        transform: scale(0.95);
    }

    /* Category filter enhancements */
    .category-filter {
        transition: all 0.2s ease;
        backdrop-filter: blur(10px);
    }

    .category-filter:hover {
        transform: translateY(-1px);
    }

    .category-filter.active {
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2);
    }

    /* Question item hover effects */
    .question-item {
        transition: all 0.2s ease;
    }

    .question-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Modal animations */
    #all-questions-modal {
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Online status pulse */
    .status-pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        #chatbot-panel {
            max-height: 70vh;
        }
        
        #chatbot-widget {
            bottom: 1rem;
            right: 1rem;
        }

        .message-bubble {
            max-width: 80%;
        }

        .quick-action-btn {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
    }

    @media (max-width: 640px) {
        .quick-action-btn {
            font-size: 0.7rem;
            padding: 0.4rem 0.6rem;
        }
        
        .message-bubble {
            max-width: 75%;
        }
    }

    /* Dark mode enhancements */
    .dark #chatbot-panel {
        background: rgb(17 24 39);
        border-color: rgb(55 65 81);
    }

    .dark .question-item {
        border-color: rgb(55 65 81);
        background: rgb(31 41 55);
    }

    .dark .question-item:hover {
        border-color: rgb(34 197 94);
        background: rgb(55 65 81);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .dark .quick-action-btn {
        background: rgb(31 41 55);
        border-color: rgb(55 65 81);
    }

    .dark .quick-action-btn:hover {
        background: rgb(55 65 81);
        border-color: rgb(34 197 94);
    }

    /* Loading states */
    .loading-message {
        opacity: 0.7;
        pointer-events: none;
    }

    /* Enhanced focus states */
    .focus-ring:focus-visible {
        outline: 2px solid rgb(34 197 94);
        outline-offset: 2px;
    }

    /* Smooth transitions for all interactive elements */
    * {
        transition-property: color, background-color, border-color, transform, box-shadow, opacity;
        transition-duration: 0.2s;
        transition-timing-function: ease;
    }

    /* Prevent text selection on buttons */
    button,
    .quick-action-btn,
    .category-filter {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }
</style>
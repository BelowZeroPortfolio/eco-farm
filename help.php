<?php
/**
 * Help & Documentation Page
 * User guide and FAQ for the IoT Farm Monitoring System
 */

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

$pageTitle = 'Help & Documentation - IoT Farm Monitoring System';
include 'includes/header.php';
include 'includes/navigation.php';
?>

<div class="p-4 max-w-7xl mx-auto">

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Sidebar Navigation -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sticky top-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Topics</h3>
                <nav class="space-y-1">
                    <a href="#getting-started" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-play-circle mr-2 text-xs"></i>Getting Started
                    </a>
                    <a href="#dashboard" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-tachometer-alt mr-2 text-xs"></i>Dashboard Guide
                    </a>
                    <a href="#sensors" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-thermometer-half mr-2 text-xs"></i>Sensor Monitoring
                    </a>
                    <a href="#pest-detection" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-bug mr-2 text-xs"></i>Pest Detection
                    </a>
                    <a href="#reports" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-chart-bar mr-2 text-xs"></i>Reports
                    </a>
                    <a href="#architecture" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-server mr-2 text-xs"></i>System Architecture
                    </a>
                    <a href="#roles" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-users mr-2 text-xs"></i>User Roles
                    </a>
                    <a href="#faq" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-question-circle mr-2 text-xs"></i>FAQ
                    </a>
                    <a href="#troubleshooting" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-wrench mr-2 text-xs"></i>Troubleshooting
                    </a>
                    <a href="#contact" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-envelope mr-2 text-xs"></i>Contact Support
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3 space-y-4">
            <!-- Getting Started -->
            <div id="getting-started" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Getting Started</h2>
                <div class="prose dark:prose-invert max-w-none">
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Welcome to the IoT-Enabled Farm Monitoring System for Sagay Eco-Farm! This comprehensive platform combines real-time sensor monitoring, AI-powered pest detection using YOLO (You Only Look Once) technology, and advanced data analytics to help you manage your farm efficiently.
                    </p>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>System Overview
                        </h4>
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            This system monitors environmental conditions (temperature, humidity, soil moisture) through IoT sensors and detects pests using AI-powered cameras. All data is stored in a MySQL database and accessible through this web interface built with PHP and JavaScript.
                        </p>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Quick Start Steps</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">Login to Your Account</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Use your credentials provided by your administrator. Default accounts:</p>
                                <ul class="text-xs text-gray-500 dark:text-gray-400 list-disc list-inside ml-4">
                                    <li><strong>Admin:</strong> admin / password (Full system access)</li>
                                    <li><strong>Farmer:</strong> farmer / password (Monitoring & management)</li>
                                    <li><strong>Student:</strong> student / password (Read-only access)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">2</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">Explore the Dashboard</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">View real-time sensor data, weather conditions for Sagay City, and recent pest detections. The dashboard updates automatically every 5 minutes.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">3</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">Check Notifications</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Click the bell icon (top right) to view pest alerts and system notifications. Critical alerts are shown automatically. The system polls for new notifications every 30 seconds.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">4</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">Start Pest Detection</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Navigate to Pest Detection page and click "Start Detection" to begin real-time AI monitoring using your webcam. The YOLO model scans every 5 seconds with 60% confidence threshold.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-900 dark:text-yellow-200 mb-2 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Important Notes
                        </h4>
                        <ul class="text-sm text-yellow-800 dark:text-yellow-300 space-y-1 list-disc list-inside">
                            <li>Ensure YOLO Flask service is running on port 5000 for pest detection to work</li>
                            <li>Camera permissions must be granted in your browser for webcam detection</li>
                            <li>System requires XAMPP (Apache + MySQL) to be running</li>
                            <li>Optimal viewing: Chrome, Firefox, or Edge browsers</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Dashboard Guide -->
            <div id="dashboard" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Dashboard Guide</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    The dashboard (dashboard.php) is your central hub for monitoring farm conditions. It displays real-time data from 9 active sensors across multiple locations including Field A, Field B, and Greenhouse A.
                </p>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Dashboard Components</h3>
                <div class="space-y-3 mb-6">
                    <div class="border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Stats Cards (Top Row)</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Five key metrics displayed prominently:</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                            <li><strong>Temperature:</strong> Current average across all sensors (°C)</li>
                            <li><strong>Humidity:</strong> Current average humidity percentage</li>
                            <li><strong>Soil Moisture:</strong> Average soil moisture levels</li>
                            <li><strong>Weekly Reports:</strong> Number of reports generated this week</li>
                            <li><strong>Live Time:</strong> Current time and date (updates every second)</li>
                        </ul>
                    </div>
                    <div class="border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Sensor Analytics Chart</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Interactive 7-day trend visualization showing:</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                            <li>Bar chart with daily averages for the past week</li>
                            <li>Dropdown to switch between Temperature, Humidity, and Soil Moisture</li>
                            <li>Color-coded bars (today's reading highlighted)</li>
                            <li>Hover over bars to see exact values</li>
                        </ul>
                    </div>
                    <div class="border-l-4 border-purple-500 bg-purple-50 dark:bg-purple-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Weather Widget</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Live weather data from OpenWeatherMap API for Sagay, Negros Occidental (coordinates: 10.8967°N, 123.4167°E):</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                            <li>Current temperature and "feels like" temperature</li>
                            <li>Humidity, wind speed, and direction</li>
                            <li>Sunrise and sunset times</li>
                            <li>Data cached for 30 minutes to reduce API calls</li>
                            <li>Fallback to typical tropical climate data if API unavailable</li>
                        </ul>
                    </div>
                    <div class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Recent Pest Detections</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Latest 5 pest alerts from the database with:</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                            <li>Pest type and detection confidence score</li>
                            <li>Severity level (Critical, High, Medium, Low)</li>
                            <li>Detection timestamp</li>
                            <li>Unread indicator (blue dot)</li>
                            <li>Click any alert to view full details in Pest Detection page</li>
                        </ul>
                    </div>
                    <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Quick Actions Grid</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Four shortcut buttons for rapid navigation:</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                            <li><strong>Sensors:</strong> View detailed sensor readings and history</li>
                            <li><strong>Pest Detection:</strong> Access AI-powered pest monitoring</li>
                            <li><strong>Reports:</strong> Generate and export data reports</li>
                            <li><strong>Cameras:</strong> Manage IP cameras and detection settings</li>
                        </ul>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Data Refresh Rates</h4>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Sensor Data:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">Every 5 minutes</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Weather Data:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">Every 30 minutes</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Pest Alerts:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">Real-time</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Live Clock:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-2">Every second</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sensor Monitoring -->
            <div id="sensors" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Sensor Monitoring</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    The system monitors environmental conditions using IoT sensors deployed across your farm. Data is collected every 5 minutes and stored in the database for analysis.
                </p>
                
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Sensor Types & Specifications</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900 dark:text-white">Sensor</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900 dark:text-white">Optimal Range</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900 dark:text-white">Update Frequency</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-900 dark:text-white">Action Needed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">Temperature</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">20-28°C</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Every 5 minutes</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Adjust ventilation or shading</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">Humidity</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">60-80%</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Every 5 minutes</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Improve air circulation</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">Soil Moisture</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">40-60%</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Every 5 minutes</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">Adjust irrigation schedule</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Data Storage</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">All sensor readings are stored in the database with timestamps. You can view historical data, generate reports, and export data in CSV or PDF format.</p>
                    </div>
                    <div class="border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Real-Time Monitoring</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">The Dashboard displays current readings with weekly trend charts. Sensor status (online/offline) is monitored continuously.</p>
                    </div>
                    <div class="border-l-4 border-purple-500 bg-purple-50 dark:bg-purple-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Data Analytics</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Visit the Data Analytics page to view correlation analysis between environmental factors and pest activity. The system calculates averages, min/max values, and trends.</p>
                    </div>
                </div>
            </div>

            <!-- Pest Detection -->
            <div id="pest-detection" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">AI-Powered Pest Detection</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    The system uses YOLO (You Only Look Once) deep learning model running on a Flask service (port 5000) to detect pests in real-time through webcam or IP cameras. Detections are stored in the pest_alerts table with confidence scores and suggested actions.
                </p>

                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-purple-900 dark:text-purple-200 mb-2 flex items-center">
                        <i class="fas fa-brain mr-2"></i>YOLO AI Model Specifications
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-purple-800 dark:text-purple-300">
                        <div><strong>Model:</strong> YOLOv8 (Ultralytics)</div>
                        <div><strong>Service:</strong> Flask (Python) on http://127.0.0.1:5000</div>
                        <div><strong>Scan Interval:</strong> Every 5 seconds</div>
                        <div><strong>Confidence Threshold:</strong> 60% (logged to database)</div>
                        <div><strong>Detection Classes:</strong> 10+ pest types</div>
                        <div><strong>Response Time:</strong> ~1-2 seconds per frame</div>
                    </div>
                </div>

                <div class="space-y-4 mb-6">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">How It Works (Technical Flow)</h4>
                        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400 ml-4">
                            <li><strong>Image Capture:</strong> Webcam or IP camera captures frame (1920x1080 or 1280x720)</li>
                            <li><strong>Upload:</strong> JavaScript sends image to pest_detection.php via AJAX POST</li>
                            <li><strong>Validation:</strong> PHP validates file (max 5MB, JPEG/PNG only)</li>
                            <li><strong>YOLO Processing:</strong> YOLODetector2.php sends image to Flask service</li>
                            <li><strong>AI Analysis:</strong> YOLO model detects pests and returns JSON with confidence scores</li>
                            <li><strong>Database Logging:</strong> Detections ≥60% confidence saved to pest_alerts table</li>
                            <li><strong>Rate Limiting:</strong> Same pest type limited to 1 entry per 60 seconds</li>
                            <li><strong>Notification:</strong> Critical alerts trigger real-time notifications</li>
                        </ol>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Detected Pest Types (from sample_data.sql)</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded">• Aphids</div>
                            <div class="text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded">• Caterpillars</div>
                            <div class="text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded">• Fungal Infection</div>
                            <div class="text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded">• Beetles</div>
                            <div class="text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded">• Spider Mites</div>
                            <div class="text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded">• Whiteflies</div>
                            <div class="text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded">• Thrips</div>
                            <div class="text-xs bg-gray-50 dark:bg-gray-700 p-2 rounded">• Root Rot</div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Severity Levels & Response Times</h4>
                        <div class="space-y-2">
                            <div class="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <span class="px-2 py-1 bg-red-600 text-white text-xs font-bold rounded">CRITICAL</span>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Immediate action required (0-2 hours)</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Examples: Fungal infections, locust swarms, severe infestations</p>
                                    <p class="text-xs text-red-700 dark:text-red-300 mt-1">Auto-notification sent to all users</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                                <span class="px-2 py-1 bg-orange-600 text-white text-xs font-bold rounded">HIGH</span>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Action needed within 24 hours</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Examples: Caterpillars, whiteflies, root rot</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <span class="px-2 py-1 bg-yellow-600 text-white text-xs font-bold rounded">MEDIUM</span>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Monitor closely (2-3 days)</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Examples: Aphids, beetles, early-stage infestations</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <span class="px-2 py-1 bg-blue-600 text-white text-xs font-bold rounded">LOW</span>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Routine monitoring (weekly)</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Examples: Spider mites, thrips, minor pest presence</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <h4 class="font-semibold text-yellow-900 dark:text-yellow-200 mb-2 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Important Setup Requirements
                    </h4>
                    <ul class="text-sm text-yellow-800 dark:text-yellow-300 space-y-1 list-disc list-inside">
                        <li>Flask service must be running: <code class="bg-yellow-100 dark:bg-yellow-900 px-1 rounded">python yolo_service.py</code></li>
                        <li>Service health check: <code class="bg-yellow-100 dark:bg-yellow-900 px-1 rounded">http://127.0.0.1:5000/health</code></li>
                        <li>Required Python packages: flask, ultralytics, pillow, opencv-python</li>
                        <li>Camera permissions must be granted in browser settings</li>
                        <li>Optimal lighting conditions for accurate detection</li>
                    </ul>
                </div>
            </div>

            <!-- FAQ -->
            <div id="faq" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Frequently Asked Questions</h2>
                <div class="space-y-4">
                    <details class="group">
                        <summary class="flex items-center justify-between cursor-pointer p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="font-medium text-gray-900 dark:text-white">How often is sensor data updated?</span>
                            <i class="fas fa-chevron-down group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 px-3">
                            Sensor data is updated every 5 minutes and displayed in real-time on the dashboard.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="flex items-center justify-between cursor-pointer p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="font-medium text-gray-900 dark:text-white">What should I do when I receive a pest alert?</span>
                            <i class="fas fa-chevron-down group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 px-3">
                            Check the alert details for recommended actions. Follow IPM principles and consult with farm staff before applying treatments.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="flex items-center justify-between cursor-pointer p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="font-medium text-gray-900 dark:text-white">Can I export data for my reports?</span>
                            <i class="fas fa-chevron-down group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 px-3">
                            Yes! Go to the Reports page and use the export buttons to download data in CSV or PDF format.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="flex items-center justify-between cursor-pointer p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="font-medium text-gray-900 dark:text-white">How accurate is the pest detection?</span>
                            <i class="fas fa-chevron-down group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 px-3">
                            The AI model has been trained to achieve over 90% accuracy for common pests. However, always verify detections visually.
                        </p>
                    </details>
                </div>
            </div>

            <!-- Troubleshooting -->
            <div id="troubleshooting" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Troubleshooting</h2>
                <div class="space-y-4">
                    <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Sensor shows "Offline"</h4>
                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>Check power connection</li>
                            <li>Verify network connectivity</li>
                            <li>Contact administrator if issue persists</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Not receiving notifications</h4>
                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>Check your notification settings in Profile</li>
                            <li>Ensure browser notifications are enabled</li>
                            <li>Verify your contact information is correct</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Dashboard not loading</h4>
                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>Refresh the page (Ctrl+F5)</li>
                            <li>Clear browser cache</li>
                            <li>Try a different browser</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- System Architecture -->
            <div id="architecture" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">System Architecture & Technical Stack</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Understanding the technical components that power the IoT Farm Monitoring System.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-server text-blue-600 mr-2"></i>Backend Stack
                        </h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <li><strong>PHP 7.4+:</strong> Server-side logic and API endpoints</li>
                            <li><strong>MySQL:</strong> Database (farm_database) with 9 tables</li>
                            <li><strong>Apache/XAMPP:</strong> Web server on localhost</li>
                            <li><strong>PDO:</strong> Database abstraction layer with prepared statements</li>
                            <li><strong>Session Management:</strong> Secure user authentication</li>
                        </ul>
                    </div>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-code text-green-600 mr-2"></i>Frontend Stack
                        </h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <li><strong>Tailwind CSS:</strong> Utility-first styling framework</li>
                            <li><strong>JavaScript ES6+:</strong> Interactive features and AJAX</li>
                            <li><strong>Chart.js:</strong> Data visualization library</li>
                            <li><strong>Font Awesome:</strong> Icon library</li>
                            <li><strong>Responsive Design:</strong> Mobile-first approach</li>
                        </ul>
                    </div>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-brain text-purple-600 mr-2"></i>AI/ML Stack
                        </h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <li><strong>Python 3.8+:</strong> AI service runtime</li>
                            <li><strong>Flask:</strong> Lightweight web framework for YOLO API</li>
                            <li><strong>Ultralytics YOLOv8:</strong> Object detection model</li>
                            <li><strong>OpenCV:</strong> Image processing library</li>
                            <li><strong>Pillow:</strong> Image manipulation</li>
                        </ul>
                    </div>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-database text-red-600 mr-2"></i>Database Schema
                        </h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                            <li><strong>users:</strong> Authentication & roles</li>
                            <li><strong>sensors:</strong> IoT device registry</li>
                            <li><strong>sensor_readings:</strong> Time-series data</li>
                            <li><strong>cameras:</strong> IP camera configuration</li>
                            <li><strong>pest_alerts:</strong> Detection records</li>
                            <li><strong>user_settings:</strong> Preferences</li>
                        </ul>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Key Files & Their Functions</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div>
                            <code class="text-blue-600 dark:text-blue-400">config/database.php</code>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">Database connection & auth functions</p>
                        </div>
                        <div>
                            <code class="text-blue-600 dark:text-blue-400">YOLODetector2.php</code>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">Flask service integration class</p>
                        </div>
                        <div>
                            <code class="text-blue-600 dark:text-blue-400">includes/export-handler.php</code>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">CSV/PDF export functionality</p>
                        </div>
                        <div>
                            <code class="text-blue-600 dark:text-blue-400">includes/weather-api.php</code>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">OpenWeatherMap API integration</p>
                        </div>
                        <div>
                            <code class="text-blue-600 dark:text-blue-400">includes/notifications.php</code>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">Real-time notification system</p>
                        </div>
                        <div>
                            <code class="text-blue-600 dark:text-blue-400">pest_severity_config.php</code>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">Pest classification & actions</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Roles & Permissions -->
            <div id="roles" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">User Roles & Permissions</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    The system implements role-based access control (RBAC) with three user levels defined in the users table.
                </p>

                <div class="space-y-4">
                    <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-user-shield text-red-600 mr-2"></i>Administrator
                            </h4>
                            <span class="px-2 py-1 bg-red-600 text-white text-xs font-bold rounded">FULL ACCESS</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Complete system control and management capabilities:</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                            <li>User management (create, edit, delete, toggle status)</li>
                            <li>Camera configuration and IP camera management</li>
                            <li>System settings and configuration</li>
                            <li>Export up to 50,000 rows in reports</li>
                            <li>Access to all pages and features</li>
                            <li>View audit logs and system statistics</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-user-tie text-green-600 mr-2"></i>Farmer
                            </h4>
                            <span class="px-2 py-1 bg-green-600 text-white text-xs font-bold rounded">MONITORING & MANAGEMENT</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Operational access for farm management:</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                            <li>View and monitor all sensor data</li>
                            <li>Access pest detection and camera feeds</li>
                            <li>Generate and export reports (CSV/PDF)</li>
                            <li>Acknowledge and resolve pest alerts</li>
                            <li>Update profile and notification settings</li>
                            <li>Export up to 10,000 rows in reports</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-user-graduate text-blue-600 mr-2"></i>Student
                            </h4>
                            <span class="px-2 py-1 bg-blue-600 text-white text-xs font-bold rounded">READ-ONLY</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Educational access for learning purposes:</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside ml-4 space-y-1">
                            <li>View dashboard and sensor readings</li>
                            <li>Access pest detection history</li>
                            <li>View reports and analytics</li>
                            <li>Export CSV only (no PDF access)</li>
                            <li>Access learning resources and documentation</li>
                            <li>Cannot modify system settings or data</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div id="contact" class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl p-6">
                <h2 class="text-xl font-bold mb-4">Need More Help?</h2>
                <p class="mb-4">Our support team is here to assist you with technical issues and questions.</p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-envelope text-2xl"></i>
                        <div>
                            <div class="font-semibold">Email Support</div>
                            <div class="text-sm opacity-90">support@farmmonitor.edu.ph</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-phone text-2xl"></i>
                        <div>
                            <div class="font-semibold">Phone Support</div>
                            <div class="text-sm opacity-90">(034) 123-4567</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock text-2xl"></i>
                        <div>
                            <div class="font-semibold">Office Hours</div>
                            <div class="text-sm opacity-90">Monday - Friday, 8:00 AM - 5:00 PM</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-book text-2xl"></i>
                        <div>
                            <div class="font-semibold">Documentation</div>
                            <div class="text-sm opacity-90">QUICK_START.txt, REALTIME_NOTIFICATIONS.md</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>

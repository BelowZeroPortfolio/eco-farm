<?php
// Start session
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/language.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Helper functions are now centralized in config/database.php
// getTimeAgo() is defined in includes/notifications.php

// Static sensor data for MVP with weekly trends
function getCurrentSensorReadings()
{
    return [
        [
            'sensor_type' => 'temperature',
            'avg_value' => 24.5,
            'unit' => '°C',
            'weekly_data' => [22.1, 23.8, 24.2, 25.1, 24.8, 23.9, 24.5], // Last 7 days
            'trend' => 'up'
        ],
        [
            'sensor_type' => 'humidity',
            'avg_value' => 68.2,
            'unit' => '%',
            'weekly_data' => [65.2, 67.1, 69.8, 68.5, 67.9, 68.8, 68.2],
            'trend' => 'stable'
        ],
        [
            'sensor_type' => 'soil_moisture',
            'avg_value' => 45.8,
            'unit' => '%',
            'weekly_data' => [48.2, 47.1, 46.5, 45.2, 44.8, 45.5, 45.8],
            'trend' => 'down'
        ]
    ];
}

function getRecentPestAlerts()
{
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->query("
            SELECT 
                id,
                pest_type,
                severity,
                confidence_score,
                detected_at,
                is_read
            FROM pest_alerts 
            ORDER BY detected_at DESC 
            LIMIT 5
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching pest alerts: " . $e->getMessage());
        return [];
    }
}

function getDailyStatistics()
{
    try {
        $pdo = getDatabaseConnection();

        // Get alert statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_alerts,
                COUNT(CASE WHEN is_read = FALSE THEN 1 END) as new_alerts,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN DATE(detected_at) = CURDATE() THEN 1 END) as today_alerts
            FROM pest_alerts
        ");
        $alertStats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'daily_averages' => [
                ['sensor_type' => 'temperature', 'daily_avg' => 24.5, 'unit' => '°C'],
                ['sensor_type' => 'humidity', 'daily_avg' => 68.2, 'unit' => '%'],
                ['sensor_type' => 'soil_moisture', 'daily_avg' => 45.8, 'unit' => '%']
            ],
            'alert_stats' => $alertStats,
            'sensor_stats' => [
                'total_sensors' => 9,
                'online_sensors' => 9,
                'offline_sensors' => 0
            ]
        ];
    } catch (Exception $e) {
        error_log("Error fetching daily statistics: " . $e->getMessage());
        return [
            'daily_averages' => [
                ['sensor_type' => 'temperature', 'daily_avg' => 24.5, 'unit' => '°C'],
                ['sensor_type' => 'humidity', 'daily_avg' => 68.2, 'unit' => '%'],
                ['sensor_type' => 'soil_moisture', 'daily_avg' => 45.8, 'unit' => '%']
            ],
            'alert_stats' => [
                'total_alerts' => 0,
                'new_alerts' => 0,
                'critical_alerts' => 0,
                'today_alerts' => 0
            ],
            'sensor_stats' => [
                'total_sensors' => 9,
                'online_sensors' => 9,
                'offline_sensors' => 0
            ]
        ];
    }
}

// Get data for dashboard
$sensorReadings = getCurrentSensorReadings();
$recentAlerts = getRecentPestAlerts();
$dailyStats = getDailyStatistics();

// Set page title for header component
$pageTitle = 'Dashboard - IoT Farm Monitoring System';

// Include shared header
include 'includes/header.php';

// Add language support JavaScript
$currentLanguage = getCurrentLanguage();
$translations = getTranslations();
$jsTranslations = $translations[$currentLanguage] ?? $translations['en'];
?>

<script>
    // Initialize language system for this page
    const pageLanguage = '<?php echo $currentLanguage; ?>';
    const pageTranslations = <?php echo json_encode($jsTranslations); ?>;
</script>
<script src="includes/language.js"></script>

<?php
?>
<?php
// Include shared navigation component (sidebar)
include 'includes/navigation.php';
?>

<!-- Dashboard Content -->
<div class="p-4 max-w-7xl mx-auto">

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
        <!-- Total Sensors -->
        <div class="bg-green-600 text-white rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-white/80 text-xs font-medium" data-translate="temperature">Temperature</h3>
                <i class="fas fa-thermometer-half text-xs"></i>
            </div>
            <div class="text-xl font-bold">30</div>
            <div class="text-white/80 text-xs">Online</div>
        </div>

        <!-- Humidity -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium">Humidity</h3>
                <i class="fas fa-camera text-purple-600 dark:text-purple-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">30</div>
            <div class="text-green-600 text-xs" data-translate="online">Online</div>
        </div>

        <!-- Soil Moisture -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium" data-translate="Soil Moisture">Soil Moisture</h3>
                <i class="fas fa-bug text-yellow-600 dark:text-yellow-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">30</div>
            <div class="text-green-600 text-xs" data-translate="online">Online</div>
        </div>

        <!-- Reports -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium" data-translate="reports">Reports</h3>
                <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">12</div>
            <div class="text-gray-500 text-xs">Generated</div>
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

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Sensor Analytics -->
            <div class="bg-gray-900 dark:bg-white border border-gray-800 dark:border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white dark:text-gray-900" data-translate="sensor_data">Sensor Analytics</h3>
                    <select id="sensor-type-select" class="bg-gray-800 dark:bg-gray-100 text-white dark:text-gray-900 text-xs border border-gray-700 dark:border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="temperature" data-translate="temperature">Temperature</option>
                        <option value="humidity" data-translate="humidity">Humidity</option>
                        <option value="soil_moisture" data-translate="soil_moisture">Soil Moisture</option>
                    </select>
                </div>
                <div class="flex items-end justify-between h-24 mb-3" id="chart-container">
                    <!-- Chart will be populated by JavaScript -->
                </div>
                <div class="flex items-center justify-between text-xs text-gray-300 dark:text-gray-600">
                    <span id="chart-label">Temperature (°C)</span>
                    <span data-translate="this_week">This week</span>
                </div>
            </div>

            <script>
                // Dummy sensor data
                const sensorData = {
                    temperature: {
                        data: [22.1, 23.8, 24.2, 25.1, 24.8, 23.9, 24.5],
                        unit: '°C',
                        colors: {
                            normal: '#ef4444', // red-500
                            today: '#dc2626', // red-600
                            normalDark: '#f87171', // red-400
                            todayDark: '#ef4444' // red-500
                        },
                        label: 'Temperature (°C)'
                    },
                    humidity: {
                        data: [65.2, 67.1, 69.8, 68.5, 67.9, 68.8, 68.2],
                        unit: '%',
                        colors: {
                            normal: '#3b82f6', // blue-500
                            today: '#2563eb', // blue-600
                            normalDark: '#60a5fa', // blue-400
                            todayDark: '#3b82f6' // blue-500
                        },
                        label: 'Humidity (%)'
                    },
                    soil_moisture: {
                        data: [48.2, 47.1, 46.5, 45.2, 44.8, 45.5, 45.8],
                        unit: '%',
                        colors: {
                            normal: '#10b981', // green-500
                            today: '#059669', // green-600
                            normalDark: '#34d399', // green-400
                            todayDark: '#10b981' // green-500
                        },
                        label: 'Soil Moisture (%)'
                    }
                };

                const days = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

                function updateChart(sensorType) {
                    const data = sensorData[sensorType];
                    const maxValue = Math.max(...data.data);
                    const minValue = Math.min(...data.data);
                    const chartContainer = document.getElementById('chart-container');
                    const chartLabel = document.getElementById('chart-label');

                    // Update label
                    chartLabel.textContent = data.label;

                    // Clear existing chart
                    chartContainer.innerHTML = '';

                    // Create new chart bars
                    data.data.forEach((value, index) => {
                        const height = ((value - minValue) / (maxValue - minValue)) * 100;
                        const normalizedHeight = Math.max(height, 20);
                        const isToday = index === 6;

                        const barContainer = document.createElement('div');
                        barContainer.className = 'flex flex-col items-center flex-1';

                        const bar = document.createElement('div');
                        bar.className = 'w-4 rounded-t mb-1 hover:opacity-80 transition-all cursor-pointer';
                        bar.style.height = normalizedHeight + '%';

                        // Set color based on theme and today status
                        const isDarkMode = document.documentElement.classList.contains('dark');
                        if (isDarkMode) {
                            bar.style.backgroundColor = isToday ? data.colors.todayDark : data.colors.normalDark;
                        } else {
                            bar.style.backgroundColor = isToday ? data.colors.today : data.colors.normal;
                        }

                        bar.title = `${days[index]}: ${value}${data.unit}`;

                        const dayLabel = document.createElement('span');
                        dayLabel.className = 'text-xs text-gray-300 dark:text-gray-600';
                        dayLabel.textContent = days[index];

                        barContainer.appendChild(bar);
                        barContainer.appendChild(dayLabel);
                        chartContainer.appendChild(barContainer);
                    });
                }

                // Initialize chart with temperature data
                document.addEventListener('DOMContentLoaded', function() {
                    updateChart('temperature');

                    // Add event listener for dropdown
                    document.getElementById('sensor-type-select').addEventListener('change', function() {
                        updateChart(this.value);
                    });
                });
            </script>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4" data-translate="quick_actions">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="sensors.php" class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-center hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors group">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-thermometer-half text-blue-600 dark:text-blue-400 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Sensors</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Monitor data</p>
                    </a>
                    <a href="pest_detection.php" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-center hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors group">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-bug text-yellow-600 dark:text-yellow-400 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Pest Detection</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">AI monitoring</p>
                    </a>
                    <a href="reports.php" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-center hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors group">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-bar text-green-600 dark:text-green-400 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Reports</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Generate data</p>
                    </a>
                    <a href="camera_management.php" class="p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg text-center hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors group">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-camera text-purple-600 dark:text-purple-400 text-lg"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Cameras</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Manage feeds</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-4">
            <!-- Current Conditions -->
            <div class="bg-green-600 text-white rounded-xl p-4">
                <h3 class="text-white/80 text-xs font-medium mb-2" data-translate="live_conditions">Live Conditions</h3>
                <div class="text-center">
                    <div class="text-2xl font-bold mb-1"><?php echo $sensorReadings[0]['avg_value']; ?>°C</div>
                    <div class="text-white/80 text-xs mb-2" data-translate="temperature">Temperature</div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div><span data-translate="humidity">Humidity</span>: <?php echo $sensorReadings[1]['avg_value']; ?>%</div>
                        <div>Soil: <?php echo $sensorReadings[2]['avg_value']; ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Pest Detection -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white" data-translate="pest_detection">Pest Detection</h3>
                    <a href="pest_detection.php" class="text-green-600 hover:text-green-700 text-xs font-medium">View All</a>
                </div>

                <?php if (empty($recentAlerts)): ?>
                    <div class="text-center py-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-shield-alt text-green-600 dark:text-green-400"></i>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">No threats detected</p>
                        <p class="text-xs text-green-600 font-medium">Farm is secure</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <?php
                        $severityColors = [
                            'critical' => ['bg' => 'red', 'icon' => 'fa-exclamation-triangle'],
                            'high' => ['bg' => 'orange', 'icon' => 'fa-exclamation-circle'],
                            'medium' => ['bg' => 'yellow', 'icon' => 'fa-info-circle'],
                            'low' => ['bg' => 'blue', 'icon' => 'fa-check-circle']
                        ];

                        foreach ($recentAlerts as $alert):
                            $color = $severityColors[$alert['severity']] ?? $severityColors['low'];
                            $detectedTime = date('M j, g:i A', strtotime($alert['detected_at']));
                        ?>
                            <a href="pest_detection.php?alert_id=<?php echo $alert['id']; ?>"
                                class="block p-2 bg-<?php echo $color['bg']; ?>-50 dark:bg-<?php echo $color['bg']; ?>-900/20 border border-<?php echo $color['bg']; ?>-200 dark:border-<?php echo $color['bg']; ?>-800 rounded-lg hover:bg-<?php echo $color['bg']; ?>-100 dark:hover:bg-<?php echo $color['bg']; ?>-900/30 transition-colors">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-<?php echo $color['bg']; ?>-100 dark:bg-<?php echo $color['bg']; ?>-900 rounded flex items-center justify-center flex-shrink-0">
                                        <i class="fas <?php echo $color['icon']; ?> text-<?php echo $color['bg']; ?>-600 dark:text-<?php echo $color['bg']; ?>-400 text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
                                            <?php echo htmlspecialchars($alert['pest_type']); ?>
                                        </p>
                                        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                            <span><?php echo round($alert['confidence_score'], 1); ?>%</span>
                                            <span>•</span>
                                            <span><?php echo $detectedTime; ?></span>
                                        </div>
                                    </div>
                                    <?php if (!$alert['is_read']): ?>
                                        <span class="w-2 h-2 bg-<?php echo $color['bg']; ?>-500 rounded-full flex-shrink-0"></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    // Count by severity
                    $criticalCount = count(array_filter($recentAlerts, fn($a) => $a['severity'] === 'critical'));
                    $highCount = count(array_filter($recentAlerts, fn($a) => $a['severity'] === 'high'));
                    ?>

                    <?php if ($criticalCount > 0 || $highCount > 0): ?>
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-xs">
                                <?php if ($criticalCount > 0): ?>
                                    <span class="text-red-600 dark:text-red-400 font-medium">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <?php echo $criticalCount; ?> Critical
                                    </span>
                                <?php endif; ?>
                                <?php if ($highCount > 0): ?>
                                    <span class="text-orange-600 dark:text-orange-400 font-medium">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        <?php echo $highCount; ?> High
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            
        </div>
    </div>

    <!-- Additional Content Row to Fill Space -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3" data-translate="recent_activity">Recent Activity</h3>
            <div class="space-y-2">
                <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-thermometer-half text-green-600 dark:text-green-400 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">Temperature sensor updated</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">2 min ago</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-camera text-blue-600 dark:text-blue-400 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">Camera stream started</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">5 min ago</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-bar text-purple-600 dark:text-purple-400 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">Report generated</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">10 min ago</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weather Conditions -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Weather</h3>
            <div class="text-center mb-3">
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-sun text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">26°C</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">Sunny</p>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                    <p class="text-gray-600 dark:text-gray-400">Change of Raining</p>
                    <p class="font-bold text-gray-900 dark:text-white">40%</p>
                </div>
                <div class="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                    <p class="text-gray-600 dark:text-gray-400">Wind</p>
                    <p class="font-bold text-gray-900 dark:text-white">12 km/h</p>
                </div>
            </div>
        </div>

        <!-- Quick Reports -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Quick Reports</h3>
                    <a href="reports.php" class="text-green-600 hover:text-green-700 text-xs font-medium">Generate</a>
                </div>
                <div class="space-y-2">
                    <button class="w-full text-left p-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-thermometer-half text-red-600 dark:text-red-400 text-xs"></i>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Temperature Report</span>
                        </div>
                    </button>
                    <button class="w-full text-left p-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-tint text-blue-600 dark:text-blue-400 text-xs"></i>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Humidity Report</span>
                        </div>
                    </button>
                    <button class="w-full text-left p-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-seedling text-green-600 dark:text-green-400 text-xs"></i>
                            <span class="text-xs font-medium text-gray-900 dark:text-white">Soil Report</span>
                        </div>
                    </button>
                </div>
            </div>
    </div>


</div>
<?php
// Include shared footer
include 'includes/footer.php';
?>
</main>
</div>
</div>
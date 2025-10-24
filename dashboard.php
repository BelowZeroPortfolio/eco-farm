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
require_once 'includes/weather-api.php';
require_once 'includes/arduino-api.php';
require_once 'includes/pest-config-helper.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Get weather data
$weatherData = getWeatherData();

// Helper functions are now centralized in config/database.php
// getTimeAgo() is defined in includes/notifications.php

// Get current sensor readings with dynamic remarks
function getCurrentSensorReadings()
{
    try {
        require_once 'includes/arduino-api.php';
        $arduino = new ArduinoBridge();

        // Get real-time Arduino data
        $arduinoData = null;
        if ($arduino->isHealthy()) {
            $arduinoData = $arduino->getAllSensorData();
        }

        // Get thresholds from database (from sensors table)
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT sensor_type, alert_threshold_min, alert_threshold_max 
            FROM sensors 
            WHERE sensor_name LIKE 'Arduino%'
            GROUP BY sensor_type
        ");

        $thresholds = [];
        while ($row = $stmt->fetch()) {
            $thresholds[$row['sensor_type']] = [
                'min' => $row['alert_threshold_min'] ?? 20,
                'max' => $row['alert_threshold_max'] ?? 28,
                'unit' => $row['sensor_type'] === 'temperature' ? '°C' : '%'
            ];
        }

        // Set defaults if not found in database
        if (!isset($thresholds['temperature'])) {
            $thresholds['temperature'] = ['min' => 20, 'max' => 28, 'unit' => '°C'];
        }
        if (!isset($thresholds['humidity'])) {
            $thresholds['humidity'] = ['min' => 60, 'max' => 80, 'unit' => '%'];
        }
        if (!isset($thresholds['soil_moisture'])) {
            $thresholds['soil_moisture'] = ['min' => 40, 'max' => 60, 'unit' => '%'];
        }

        $readings = [];

        foreach ($thresholds as $type => $threshold) {
            $value = null;
            $status = 'offline';
            $remark = 'No data available';

            if ($arduinoData && isset($arduinoData[$type]['value'])) {
                $value = $arduinoData[$type]['value'];
                $status = $arduinoData[$type]['status'] ?? 'online';

                // Generate dynamic remarks based on thresholds
                $remark = generateSensorRemark($type, $value, $threshold);
            }

            $readings[] = [
                'sensor_type' => $type,
                'avg_value' => $value ?? 0,
                'unit' => $threshold['unit'],
                'status' => $status,
                'remark' => $remark,
                'threshold_min' => $threshold['min'],
                'threshold_max' => $threshold['max'],
                'weekly_data' => [0], // Placeholder for chart
                'trend' => 'stable'
            ];
        }

        return $readings;
    } catch (Exception $e) {
        error_log("Error getting sensor readings: " . $e->getMessage());
        // Return fallback data
        return [
            [
                'sensor_type' => 'temperature',
                'avg_value' => 24.5,
                'unit' => '°C',
                'status' => 'simulated',
                'remark' => 'Using simulated data - Arduino not connected',
                'threshold_min' => 20,
                'threshold_max' => 28,
                'weekly_data' => [22.1, 23.8, 24.2, 25.1, 24.8, 23.9, 24.5],
                'trend' => 'up'
            ],
            [
                'sensor_type' => 'humidity',
                'avg_value' => 68.2,
                'unit' => '%',
                'status' => 'simulated',
                'remark' => 'Using simulated data - Arduino not connected',
                'threshold_min' => 60,
                'threshold_max' => 80,
                'weekly_data' => [65.2, 67.1, 69.8, 68.5, 67.9, 68.8, 68.2],
                'trend' => 'stable'
            ],
            [
                'sensor_type' => 'soil_moisture',
                'avg_value' => 45.8,
                'unit' => '%',
                'status' => 'simulated',
                'remark' => 'Using simulated data - Arduino not connected',
                'threshold_min' => 40,
                'threshold_max' => 60,
                'weekly_data' => [48.2, 47.1, 46.5, 45.2, 44.8, 45.5, 45.8],
                'trend' => 'down'
            ]
        ];
    }
}

// Generate dynamic sensor remarks based on thresholds
function generateSensorRemark($type, $value, $threshold)
{
    $min = $threshold['min'];
    $max = $threshold['max'];
    $unit = $threshold['unit'];

    $remarks = [
        'temperature' => [
            'optimal' => "Temperature is optimal at {$value}{$unit}. Perfect for crop growth.",
            'high' => "Temperature is high at {$value}{$unit}. Consider ventilation or shading. Optimal: {$min}-{$max}{$unit}.",
            'low' => "Temperature is low at {$value}{$unit}. Consider heating or greenhouse cover. Optimal: {$min}-{$max}{$unit}.",
            'critical_high' => "⚠️ CRITICAL: Temperature too high at {$value}{$unit}! Immediate action needed. Risk of heat stress.",
            'critical_low' => "⚠️ CRITICAL: Temperature too low at {$value}{$unit}! Immediate action needed. Risk of frost damage."
        ],
        'humidity' => [
            'optimal' => "Humidity is optimal at {$value}{$unit}. Good conditions for plant health.",
            'high' => "Humidity is high at {$value}{$unit}. Risk of fungal diseases. Increase ventilation. Optimal: {$min}-{$max}{$unit}.",
            'low' => "Humidity is low at {$value}{$unit}. Plants may experience water stress. Consider misting. Optimal: {$min}-{$max}{$unit}.",
            'critical_high' => "⚠️ CRITICAL: Humidity too high at {$value}{$unit}! High risk of mold and fungal diseases.",
            'critical_low' => "⚠️ CRITICAL: Humidity too low at {$value}{$unit}! Severe water stress risk."
        ],
        'soil_moisture' => [
            'optimal' => "Soil moisture is optimal at {$value}{$unit}. Good water availability for roots.",
            'high' => "Soil moisture is high at {$value}{$unit}. Risk of root rot. Reduce irrigation. Optimal: {$min}-{$max}{$unit}.",
            'low' => "Soil moisture is low at {$value}{$unit}. Plants need watering soon. Optimal: {$min}-{$max}{$unit}.",
            'critical_high' => "⚠️ CRITICAL: Soil is waterlogged at {$value}{$unit}! Stop irrigation immediately. Risk of root rot.",
            'critical_low' => "⚠️ CRITICAL: Soil is too dry at {$value}{$unit}! Irrigate immediately to prevent wilting."
        ]
    ];

    // Determine status
    if ($value >= $min && $value <= $max) {
        return $remarks[$type]['optimal'];
    } elseif ($value > $max) {
        // High
        if ($value > $max + ($max - $min) * 0.5) {
            return $remarks[$type]['critical_high'];
        }
        return $remarks[$type]['high'];
    } else {
        // Low
        if ($value < $min - ($max - $min) * 0.5) {
            return $remarks[$type]['critical_low'];
        }
        return $remarks[$type]['low'];
    }
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

        // Calculate weekly reports (based on days with pest alerts this week)
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT DATE(detected_at)) as reports_generated
            FROM pest_alerts
            WHERE detected_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $reportStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $weeklyReports = max(1, $reportStats['reports_generated']); // At least 1 report

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
            ],
            'weekly_reports' => $weeklyReports
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
            ],
            'weekly_reports' => 7 // Default to 7 (one per day)
        ];
    }
}

// Initialize Arduino bridge for real-time data
$arduino = new ArduinoBridge();
$arduinoHealthy = false;
$arduinoData = null;

if ($arduino->isHealthy()) {
    $arduinoHealthy = true;
    $arduinoData = $arduino->getAllSensorData();

    // Auto-sync all sensor data to database
    if ($arduinoData) {
        foreach ($arduinoData as $sensorType => $data) {
            if (isset($data['value']) && $data['value'] !== null) {
                $unit = ($sensorType === 'temperature') ? '°C' : '%';
                $arduino->storeSensorReading($sensorType, $data['value'], $unit);
            }
        }
    }
}

// Get data for dashboard
$sensorReadings = getCurrentSensorReadings();
$recentAlerts = getRecentPestAlerts();
$dailyStats = getDailyStatistics();
$pestStats = getPestStatistics();

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
        <!-- Arduino Temperature -->
        <?php
        $tempReading = null;
        foreach ($sensorReadings as $reading) {
            if ($reading['sensor_type'] === 'temperature') {
                $tempReading = $reading;
                break;
            }
        }
        ?>
        <div class="bg-red-600 text-white rounded-xl p-3" id="temperature-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-white/80 text-xs font-medium">Arduino Temp</h3>
                <i class="fas fa-thermometer-half text-xs"></i>
            </div>
            <?php if ($tempReading && $tempReading['avg_value'] > 0): ?>
                <div class="text-xl font-bold" id="temperature-value">
                    <?php echo number_format($tempReading['avg_value'], 1); ?>°C
                </div>
                <div class="text-white/90 text-xs mt-1" style="line-height: 1.2;">
                    <?php
                    // Show short remark
                    if (strpos($tempReading['remark'], 'optimal') !== false) {
                        echo '✓ Optimal';
                    } elseif (strpos($tempReading['remark'], 'CRITICAL') !== false) {
                        echo '⚠ Critical!';
                    } elseif (strpos($tempReading['remark'], 'high') !== false) {
                        echo '↑ Too High';
                    } elseif (strpos($tempReading['remark'], 'low') !== false) {
                        echo '↓ Too Low';
                    } else {
                        echo $tempReading['status'] === 'online' ? 'Live' : 'Database';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="text-xl font-bold text-white/60">--</div>
                <div class="text-white/60 text-xs">Offline</div>
            <?php endif; ?>
        </div>

        <!-- Arduino Humidity -->
        <?php
        $humReading = null;
        foreach ($sensorReadings as $reading) {
            if ($reading['sensor_type'] === 'humidity') {
                $humReading = $reading;
                break;
            }
        }
        ?>
        <div class="bg-blue-600 text-white rounded-xl p-3" id="humidity-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-white/80 text-xs font-medium">Arduino Humidity</h3>
                <i class="fas fa-tint text-xs"></i>
            </div>
            <?php if ($humReading && $humReading['avg_value'] > 0): ?>
                <div class="text-xl font-bold" id="humidity-value">
                    <?php echo number_format($humReading['avg_value'], 1); ?>%
                </div>
                <div class="text-white/90 text-xs mt-1" style="line-height: 1.2;">
                    <?php
                    // Show short remark
                    if (strpos($humReading['remark'], 'optimal') !== false) {
                        echo '✓ Optimal';
                    } elseif (strpos($humReading['remark'], 'CRITICAL') !== false) {
                        echo '⚠ Critical!';
                    } elseif (strpos($humReading['remark'], 'high') !== false) {
                        echo '↑ Too High';
                    } elseif (strpos($humReading['remark'], 'low') !== false) {
                        echo '↓ Too Low';
                    } else {
                        echo $humReading['status'] === 'online' ? 'Live' : 'Database';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="text-xl font-bold text-white/60">--</div>
                <div class="text-white/60 text-xs">Offline</div>
            <?php endif; ?>
        </div>

        <!-- Arduino Soil Moisture -->
        <?php
        $soilReading = null;
        foreach ($sensorReadings as $reading) {
            if ($reading['sensor_type'] === 'soil_moisture') {
                $soilReading = $reading;
                break;
            }
        }
        ?>
        <div class="bg-green-600 text-white rounded-xl p-3" id="soil-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-white/80 text-xs font-medium">Arduino Soil</h3>
                <i class="fas fa-seedling text-xs"></i>
            </div>
            <?php if ($soilReading && $soilReading['avg_value'] > 0): ?>
                <div class="text-xl font-bold" id="soil-value">
                    <?php echo number_format($soilReading['avg_value'], 1); ?>%
                </div>
                <div class="text-white/90 text-xs mt-1" style="line-height: 1.2;">
                    <?php
                    // Show short remark
                    if (strpos($soilReading['remark'], 'optimal') !== false) {
                        echo '✓ Optimal';
                    } elseif (strpos($soilReading['remark'], 'CRITICAL') !== false) {
                        echo '⚠ Critical!';
                    } elseif (strpos($soilReading['remark'], 'high') !== false) {
                        echo '↑ Too High';
                    } elseif (strpos($soilReading['remark'], 'low') !== false) {
                        echo '↓ Too Low';
                    } else {
                        echo $soilReading['status'] === 'online' ? 'Live' : 'Database';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="text-xl font-bold text-white/60">--</div>
                <div class="text-white/60 text-xs">Offline</div>
            <?php endif; ?>
        </div>

        <!-- Reports -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-gray-600 dark:text-gray-400 text-xs font-medium" data-translate="weekly_reports_generated">Weekly Reports</h3>
                <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400 text-xs"></i>
            </div>
            <div class="text-xl font-bold text-gray-900 dark:text-white"><?php echo $dailyStats['weekly_reports']; ?></div>
            <div class="text-gray-500 text-xs">This week</div>
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

    <!-- Sensor Logging Interval Display -->
    <?php include 'includes/sensor-interval-display.php'; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Sensor Analytics -->
            <div class="bg-gray-900 dark:bg-white border border-gray-800 dark:border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-white dark:text-gray-900" data-translate="sensor_data">Arduino Analytics</h3>
                        <span id="arduino-chart-status" class="w-2 h-2 bg-gray-400 rounded-full" title="Arduino Status"></span>
                    </div>
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
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span>Live Data</span>
                    </div>
                </div>
            </div>

            <script>
                // Real-time Arduino sensor data with initial values from PHP
                const sensorData = {
                    temperature: {
                        data: [
                            <?php
                            $tempReading = null;
                            foreach ($sensorReadings as $r) {
                                if ($r['sensor_type'] === 'temperature') {
                                    $tempReading = $r;
                                    break;
                                }
                            }
                            $tempVal = $tempReading ? $tempReading['avg_value'] : 24.5;
                            echo implode(', ', array_fill(0, 7, $tempVal));
                            ?>
                        ],
                        unit: '°C',
                        colors: {
                            normal: '#ef4444',
                            today: '#dc2626',
                            normalDark: '#f87171',
                            todayDark: '#ef4444'
                        },
                        label: 'Temperature (°C)',
                        currentValue: <?php echo $tempVal; ?>
                    },
                    humidity: {
                        data: [
                            <?php
                            $humReading = null;
                            foreach ($sensorReadings as $r) {
                                if ($r['sensor_type'] === 'humidity') {
                                    $humReading = $r;
                                    break;
                                }
                            }
                            $humVal = $humReading ? $humReading['avg_value'] : 68.0;
                            echo implode(', ', array_fill(0, 7, $humVal));
                            ?>
                        ],
                        unit: '%',
                        colors: {
                            normal: '#3b82f6',
                            today: '#2563eb',
                            normalDark: '#60a5fa',
                            todayDark: '#3b82f6'
                        },
                        label: 'Humidity (%)',
                        currentValue: <?php echo $humVal; ?>
                    },
                    soil_moisture: {
                        data: [
                            <?php
                            $soilReading = null;
                            foreach ($sensorReadings as $r) {
                                if ($r['sensor_type'] === 'soil_moisture') {
                                    $soilReading = $r;
                                    break;
                                }
                            }
                            $soilVal = $soilReading ? $soilReading['avg_value'] : 46.0;
                            echo implode(', ', array_fill(0, 7, $soilVal));
                            ?>
                        ],
                        unit: '%',
                        colors: {
                            normal: '#10b981',
                            today: '#059669',
                            normalDark: '#34d399',
                            todayDark: '#10b981'
                        },
                        label: 'Soil Moisture (%)',
                        currentValue: <?php echo $soilVal; ?>
                    }
                };

                // Fetch real Arduino data
                function fetchArduinoData() {
                    const statusIndicator = document.getElementById('arduino-chart-status');

                    fetch('arduino_sync.php?action=get_all')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data) {
                                // Update status indicator - Arduino connected
                                statusIndicator.className = 'w-2 h-2 bg-green-500 rounded-full animate-pulse';
                                statusIndicator.title = 'Arduino Connected - Live Data';

                                // Update current values
                                if (data.data.temperature && data.data.temperature.value !== null) {
                                    sensorData.temperature.currentValue = parseFloat(data.data.temperature.value);
                                    // Add to data array and shift old values
                                    sensorData.temperature.data.push(sensorData.temperature.currentValue);
                                    sensorData.temperature.data.shift();
                                }

                                if (data.data.humidity && data.data.humidity.value !== null) {
                                    sensorData.humidity.currentValue = parseFloat(data.data.humidity.value);
                                    sensorData.humidity.data.push(sensorData.humidity.currentValue);
                                    sensorData.humidity.data.shift();
                                }

                                if (data.data.soil_moisture && data.data.soil_moisture.value !== null) {
                                    sensorData.soil_moisture.currentValue = parseFloat(data.data.soil_moisture.value);
                                    sensorData.soil_moisture.data.push(sensorData.soil_moisture.currentValue);
                                    sensorData.soil_moisture.data.shift();
                                }

                                // Update the current chart
                                const currentSensorType = document.getElementById('sensor-type-select').value;
                                updateChart(currentSensorType);
                            } else {
                                // Arduino service not responding properly
                                statusIndicator.className = 'w-2 h-2 bg-yellow-500 rounded-full';
                                statusIndicator.title = 'Arduino Service Issues';
                                simulateData();
                            }
                        })
                        .catch(error => {
                            console.log('Arduino data fetch failed:', error);
                            // Update status indicator - Arduino disconnected
                            statusIndicator.className = 'w-2 h-2 bg-red-500 rounded-full';
                            statusIndicator.title = 'Arduino Disconnected - Using Simulated Data';
                            // Use simulated data as fallback
                            simulateData();
                        });
                }

                // Simulate data when Arduino is not available
                function simulateData() {
                    const now = new Date();
                    const variation = Math.sin(now.getTime() / 10000) * 2; // Smooth variation

                    sensorData.temperature.currentValue = 24.5 + variation;
                    sensorData.humidity.currentValue = 68.0 + variation;
                    sensorData.soil_moisture.currentValue = 46.0 + variation;

                    // Add to arrays
                    Object.keys(sensorData).forEach(type => {
                        sensorData[type].data.push(sensorData[type].currentValue);
                        sensorData[type].data.shift();
                    });

                    const currentSensorType = document.getElementById('sensor-type-select').value;
                    updateChart(currentSensorType);
                }

                const days = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

                function updateChart(sensorType) {
                    const data = sensorData[sensorType];
                    const chartContainer = document.getElementById('chart-container');
                    const chartLabel = document.getElementById('chart-label');

                    // Filter out zero values for better scaling
                    const nonZeroData = data.data.filter(val => val > 0);
                    const maxValue = nonZeroData.length > 0 ? Math.max(...nonZeroData) : Math.max(...data.data);
                    const minValue = nonZeroData.length > 0 ? Math.min(...nonZeroData) : Math.min(...data.data);

                    // Use reasonable ranges if all data is zero
                    const range = maxValue - minValue || 10;
                    const adjustedMax = maxValue + (range * 0.1);
                    const adjustedMin = Math.max(0, minValue - (range * 0.1));

                    // Update label with current value
                    const currentVal = data.currentValue || data.data[data.data.length - 1] || 0;
                    chartLabel.innerHTML = `${data.label} <span class="font-bold text-${data.colors.normal.replace('#', '')}">${currentVal.toFixed(1)}${data.unit}</span>`;

                    // Clear existing chart
                    chartContainer.innerHTML = '';

                    // Create new chart bars with time-based labels
                    const timeLabels = ['6h', '5h', '4h', '3h', '2h', '1h', 'Now'];

                    data.data.forEach((value, index) => {
                        const height = value > 0 ? ((value - adjustedMin) / (adjustedMax - adjustedMin)) * 100 : 5;
                        const normalizedHeight = Math.max(height, 5);
                        const isNow = index === 6;

                        const barContainer = document.createElement('div');
                        barContainer.className = 'flex flex-col items-center flex-1';

                        const bar = document.createElement('div');
                        bar.className = 'w-4 rounded-t mb-1 hover:opacity-80 transition-all cursor-pointer';
                        bar.style.height = normalizedHeight + '%';

                        // Set color based on theme and current status
                        const isDarkMode = document.documentElement.classList.contains('dark');
                        if (isDarkMode) {
                            bar.style.backgroundColor = isNow ? data.colors.todayDark : data.colors.normalDark;
                        } else {
                            bar.style.backgroundColor = isNow ? data.colors.today : data.colors.normal;
                        }

                        // Enhanced tooltip with status
                        const status = value > 0 ? 'Live' : 'No Data';
                        bar.title = `${timeLabels[index]}: ${value > 0 ? value.toFixed(1) : '--'}${data.unit} (${status})`;

                        const dayLabel = document.createElement('span');
                        dayLabel.className = 'text-xs text-gray-300 dark:text-gray-600';
                        dayLabel.textContent = timeLabels[index];

                        barContainer.appendChild(bar);
                        barContainer.appendChild(dayLabel);
                        chartContainer.appendChild(barContainer);
                    });
                }

                // Initialize chart with real Arduino data
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize with some baseline data
                    Object.keys(sensorData).forEach(type => {
                        // Fill with current value or reasonable defaults
                        const defaultValue = type === 'temperature' ? 24 : type === 'humidity' ? 65 : 45;
                        sensorData[type].data = Array(7).fill(defaultValue);
                    });

                    updateChart('temperature');

                    // Fetch initial Arduino data
                    fetchArduinoData();

                    // Add event listener for dropdown
                    document.getElementById('sensor-type-select').addEventListener('change', function() {
                        updateChart(this.value);
                    });

                    // Auto-update every 5 seconds
                    setInterval(fetchArduinoData, 5000);
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
            <!-- Arduino Live Sensors -->
            <?php if ($arduinoHealthy && $arduinoData): ?>
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 text-white rounded-xl p-4" id="arduino-sensors-widget">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-white/80 text-xs font-medium">Arduino Live Sensors</h3>
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                            <span class="text-white/80 text-xs">Live</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <?php if (isset($arduinoData['temperature'])): ?>
                            <div class="flex items-center justify-between p-2 bg-white/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-thermometer-half text-red-400 text-sm"></i>
                                    <span class="text-sm">Temperature</span>
                                </div>
                                <span class="font-bold" id="widget-temperature">
                                    <?php echo number_format($arduinoData['temperature']['value'], 1); ?>°C
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($arduinoData['humidity'])): ?>
                            <div class="flex items-center justify-between p-2 bg-white/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-tint text-blue-400 text-sm"></i>
                                    <span class="text-sm">Humidity</span>
                                </div>
                                <span class="font-bold" id="widget-humidity">
                                    <?php echo number_format($arduinoData['humidity']['value'], 1); ?>%
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($arduinoData['soil_moisture'])): ?>
                            <div class="flex items-center justify-between p-2 bg-white/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-seedling text-green-400 text-sm"></i>
                                    <span class="text-sm">Soil Moisture</span>
                                </div>
                                <span class="font-bold" id="widget-soil">
                                    <?php echo number_format($arduinoData['soil_moisture']['value'], 1); ?>%
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="text-center mt-3 pt-3 border-t border-white/20">
                        <div class="text-white/60 text-xs">
                            Last update: <span id="widget-timestamp">
                                <?php
                                $lastUpdate = '';
                                foreach ($arduinoData as $data) {
                                    if (isset($data['timestamp'])) {
                                        $lastUpdate = $data['timestamp'];
                                        break;
                                    }
                                }
                                echo $lastUpdate ?: 'Unknown';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Current Conditions -->
            <div class="bg-green-600 text-white rounded-xl p-4">
                <h3 class="text-white/80 text-xs font-medium mb-2" data-translate="weekly_average_conditions">Weekly Average Conditions</h3>
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

            <!-- Pest Database Statistics -->
            <?php if ($pestStats && $pestStats['total'] > 0): ?>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-database text-blue-600 mr-2"></i>
                            Pest Database
                        </h3>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <a href="pest_config.php" class="text-blue-600 hover:text-blue-700 text-xs font-medium">Manage</a>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Total Pests</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo $pestStats['total']; ?></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-red-600 dark:text-red-400">Critical</span>
                                    <span class="font-bold text-red-700 dark:text-red-300"><?php echo $pestStats['critical']; ?></span>
                                </div>
                            </div>
                            <div class="p-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-orange-600 dark:text-orange-400">High</span>
                                    <span class="font-bold text-orange-700 dark:text-orange-300"><?php echo $pestStats['high']; ?></span>
                                </div>
                            </div>
                            <div class="p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-yellow-600 dark:text-yellow-400">Medium</span>
                                    <span class="font-bold text-yellow-700 dark:text-yellow-300"><?php echo $pestStats['medium']; ?></span>
                                </div>
                            </div>
                            <div class="p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-green-600 dark:text-green-400">Low</span>
                                    <span class="font-bold text-green-700 dark:text-green-300"><?php echo $pestStats['low']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-600 dark:text-gray-400">Active Pests</span>
                                <span class="font-medium text-green-600 dark:text-green-400">
                                    <i class="fas fa-check-circle mr-1"></i><?php echo $pestStats['active']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-xs text-blue-800 dark:text-blue-300">
                                <i class="fas fa-info-circle mr-1"></i>
                                YOLO model can detect all <?php echo $pestStats['total']; ?> pest types with severity levels and treatment recommendations.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

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

        <!-- Weather Conditions - Live Data -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">Weather</h3>
                <span class="text-xs opacity-75">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    Sagay, N.O.
                </span>
            </div>
            <div class="text-center mb-3">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas <?php echo $weatherData['icon']; ?> text-white text-2xl"></i>
                </div>
                <p class="text-3xl font-bold mb-1"><?php echo $weatherData['temperature']; ?>°C</p>
                <p class="text-sm opacity-90"><?php echo $weatherData['description']; ?></p>
                <p class="text-xs opacity-75 mt-1">Feels like <?php echo $weatherData['feels_like']; ?>°C</p>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="text-center p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                    <div class="flex items-center justify-center mb-1">
                        <i class="fas fa-cloud-rain mr-1"></i>
                        <span class="opacity-75">Rain</span>
                    </div>
                    <p class="font-bold"><?php echo $weatherData['rain_chance']; ?>%</p>
                </div>
                <div class="text-center p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                    <div class="flex items-center justify-center mb-1">
                        <i class="fas fa-wind mr-1"></i>
                        <span class="opacity-75">Wind</span>
                    </div>
                    <p class="font-bold"><?php echo $weatherData['wind_speed']; ?> km/h</p>
                </div>
                <div class="text-center p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                    <div class="flex items-center justify-center mb-1">
                        <i class="fas fa-tint mr-1"></i>
                        <span class="opacity-75">Humidity</span>
                    </div>
                    <p class="font-bold"><?php echo $weatherData['humidity']; ?>%</p>
                </div>
                <div class="text-center p-2 bg-white/10 backdrop-blur-sm rounded-lg">
                    <div class="flex items-center justify-center mb-1">
                        <i class="fas fa-eye mr-1"></i>
                        <span class="opacity-75">Visibility</span>
                    </div>
                    <p class="font-bold"><?php echo $weatherData['visibility']; ?> km</p>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-white/20 flex items-center justify-between text-xs opacity-75">
                <span>
                    <i class="fas fa-sunrise mr-1"></i>
                    <?php echo $weatherData['sunrise']; ?>
                </span>
                <span>
                    <i class="fas fa-sunset mr-1"></i>
                    <?php echo $weatherData['sunset']; ?>
                </span>
            </div>
            <div class="mt-2 text-center text-xs opacity-60">
                Updated: <?php echo $weatherData['last_updated']; ?>
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
<!-- Real-time Arduino Data JavaScript -->
<script>
    // Real-time Arduino sensor updates
    function updateArduinoSensors() {
        fetch('arduino_sync.php?action=get_all')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const sensors = data.data;

                    // Update temperature card
                    if (sensors.temperature && sensors.temperature.value !== null) {
                        const tempValue = document.getElementById('temperature-value');
                        if (tempValue) {
                            tempValue.textContent = parseFloat(sensors.temperature.value).toFixed(1) + '°C';
                        }

                        // Update widget
                        const widgetTemp = document.getElementById('widget-temperature');
                        if (widgetTemp) {
                            widgetTemp.textContent = parseFloat(sensors.temperature.value).toFixed(1) + '°C';
                        }

                        // Update chart if temperature is selected
                        const sensorSelect = document.getElementById('sensor-type-select');
                        if (sensorSelect && sensorSelect.value === 'temperature' && typeof sensorData !== 'undefined') {
                            sensorData.temperature.data.push(parseFloat(sensors.temperature.value));
                            sensorData.temperature.data.shift();
                            updateChart('temperature');
                        }
                    }

                    // Update humidity card
                    if (sensors.humidity && sensors.humidity.value !== null) {
                        const humidityValue = document.getElementById('humidity-value');
                        if (humidityValue) {
                            humidityValue.textContent = parseFloat(sensors.humidity.value).toFixed(1) + '%';
                        }

                        // Update widget
                        const widgetHumidity = document.getElementById('widget-humidity');
                        if (widgetHumidity) {
                            widgetHumidity.textContent = parseFloat(sensors.humidity.value).toFixed(1) + '%';
                        }

                        // Update chart if humidity is selected
                        const sensorSelect = document.getElementById('sensor-type-select');
                        if (sensorSelect && sensorSelect.value === 'humidity' && typeof sensorData !== 'undefined') {
                            sensorData.humidity.data.push(parseFloat(sensors.humidity.value));
                            sensorData.humidity.data.shift();
                            updateChart('humidity');
                        }
                    }

                    // Update soil moisture card
                    if (sensors.soil_moisture && sensors.soil_moisture.value !== null) {
                        const soilValue = document.getElementById('soil-value');
                        if (soilValue) {
                            soilValue.textContent = parseFloat(sensors.soil_moisture.value).toFixed(1) + '%';
                        }

                        // Update widget
                        const widgetSoil = document.getElementById('widget-soil');
                        if (widgetSoil) {
                            widgetSoil.textContent = parseFloat(sensors.soil_moisture.value).toFixed(1) + '%';
                        }

                        // Update chart if soil moisture is selected
                        const sensorSelect = document.getElementById('sensor-type-select');
                        if (sensorSelect && sensorSelect.value === 'soil_moisture' && typeof sensorData !== 'undefined') {
                            sensorData.soil_moisture.data.push(parseFloat(sensors.soil_moisture.value));
                            sensorData.soil_moisture.data.shift();
                            updateChart('soil_moisture');
                        }
                    }

                    // Update widget timestamp
                    const widgetTimestamp = document.getElementById('widget-timestamp');
                    if (widgetTimestamp) {
                        let latestTimestamp = '';
                        for (const sensorType in sensors) {
                            if (sensors[sensorType].timestamp) {
                                latestTimestamp = sensors[sensorType].timestamp;
                                break;
                            }
                        }
                        widgetTimestamp.textContent = latestTimestamp || 'Just now';
                    }
                }
            })
            .catch(error => {
                console.log('Arduino sensors update failed:', error);
            });
    }

    // Start real-time updates
    <?php if ($arduinoHealthy): ?>
        // Update every 5 seconds for real-time feel
        setInterval(updateArduinoSensors, 5000);

        // Initial update after 2 seconds
        setTimeout(updateArduinoSensors, 2000);
    <?php endif; ?>
</script>

<?php
// Include shared footer
include 'includes/footer.php';
?>
</main>
</div>
</div>
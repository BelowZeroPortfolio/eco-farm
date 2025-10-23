<?php
/**
 * Advanced Sensors Analytics & Visualization Page
 * Features: Multi-chart types, filtering, export, real-time updates
 */

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/language.php';
require_once 'includes/arduino-api.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Get filter parameters
$filterType = $_GET['sensor_type'] ?? 'all';
$filterPeriod = $_GET['period'] ?? '24h';
$chartType = $_GET['chart'] ?? 'line';

// Calculate date range based on period
function getDateRange($period) {
    $now = new DateTime();
    switch ($period) {
        case '1h': return $now->modify('-1 hour')->format('Y-m-d H:i:s');
        case '6h': return $now->modify('-6 hours')->format('Y-m-d H:i:s');
        case '24h': return $now->modify('-24 hours')->format('Y-m-d H:i:s');
        case '7d': return $now->modify('-7 days')->format('Y-m-d H:i:s');
        case '30d': return $now->modify('-30 days')->format('Y-m-d H:i:s');
        default: return $now->modify('-24 hours')->format('Y-m-d H:i:s');
    }
}

// Get filtered sensor data
function getFilteredSensorData($sensorType = 'all', $startDate) {
    try {
        $pdo = getDatabaseConnection();
        
        $whereClause = "WHERE sr.recorded_at >= ?";
        $params = [$startDate];
        
        if ($sensorType !== 'all') {
            $whereClause .= " AND s.sensor_type = ?";
            $params[] = $sensorType;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                s.id as sensor_id,
                s.sensor_name,
                s.sensor_type,
                s.location,
                sr.value,
                sr.unit,
                sr.recorded_at
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            $whereClause
            ORDER BY sr.recorded_at ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get filtered sensor data: " . $e->getMessage());
        return [];
    }
}

// Get sensor statistics
function getSensorStats($sensorType = 'all', $startDate) {
    try {
        $pdo = getDatabaseConnection();
        
        $whereClause = "WHERE sr.recorded_at >= ?";
        $params = [$startDate];
        
        if ($sensorType !== 'all') {
            $whereClause .= " AND s.sensor_type = ?";
            $params[] = $sensorType;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                s.sensor_type,
                COUNT(*) as reading_count,
                AVG(sr.value) as avg_value,
                MIN(sr.value) as min_value,
                MAX(sr.value) as max_value,
                sr.unit
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            $whereClause
            GROUP BY s.sensor_type, sr.unit
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get sensor stats: " . $e->getMessage());
        return [];
    }
}

// Get sensor thresholds from database
function getSensorThresholds() {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                sensor_type,
                AVG(alert_threshold_min) as min_threshold,
                AVG(alert_threshold_max) as max_threshold
            FROM sensors
            WHERE alert_threshold_min IS NOT NULL 
            AND alert_threshold_max IS NOT NULL
            GROUP BY sensor_type
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        // Convert to associative array
        $thresholds = [];
        foreach ($results as $row) {
            $thresholds[$row['sensor_type']] = [
                'min' => floatval($row['min_threshold']),
                'max' => floatval($row['max_threshold'])
            ];
        }
        
        // Fallback to defaults if no thresholds in database
        if (empty($thresholds)) {
            return [
                'temperature' => ['min' => 20, 'max' => 28],
                'humidity' => ['min' => 60, 'max' => 80],
                'soil_moisture' => ['min' => 40, 'max' => 60]
            ];
        }
        
        return $thresholds;
    } catch (Exception $e) {
        error_log("Failed to get sensor thresholds: " . $e->getMessage());
        // Return defaults on error
        return [
            'temperature' => ['min' => 20, 'max' => 28],
            'humidity' => ['min' => 60, 'max' => 80],
            'soil_moisture' => ['min' => 40, 'max' => 60]
        ];
    }
}

$startDate = getDateRange($filterPeriod);
$sensorData = getFilteredSensorData($filterType, $startDate);
$sensorStats = getSensorStats($filterType, $startDate);
$thresholds = getSensorThresholds();

// Initialize Arduino bridge
$arduino = new ArduinoBridge();
$arduinoHealthy = $arduino->isHealthy();
$arduinoData = $arduinoHealthy ? $arduino->getAllSensorData() : null;

// Organize data by sensor type
$dataByType = [];
foreach ($sensorData as $reading) {
    $type = $reading['sensor_type'];
    if (!isset($dataByType[$type])) {
        $dataByType[$type] = [];
    }
    $dataByType[$type][] = $reading;
}

$pageTitle = 'Advanced Sensor Analytics - IoT Farm Monitoring';
include 'includes/header.php';

$currentLanguage = getCurrentLanguage();
$translations = getTranslations();
$jsTranslations = $translations[$currentLanguage] ?? $translations['en'];
?>

<script>
    const pageLanguage = '<?php echo $currentLanguage; ?>';
    const pageTranslations = <?php echo json_encode($jsTranslations); ?>;
</script>
<script src="includes/language.js"></script>

<?php include 'includes/navigation.php'; ?>

<div class="p-4 max-w-7xl mx-auto">

    <!-- Compact Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
        <?php foreach ($sensorStats as $stat): 
            $icons = [
                'temperature' => ['icon' => 'fa-thermometer-half', 'color' => 'red', 'emoji' => '🌡️'],
                'humidity' => ['icon' => 'fa-tint', 'color' => 'blue', 'emoji' => '💧'],
                'soil_moisture' => ['icon' => 'fa-seedling', 'color' => 'green', 'emoji' => '🌱']
            ];
            $config = $icons[$stat['sensor_type']] ?? ['icon' => 'fa-sensor', 'color' => 'gray', 'emoji' => '📊'];
        ?>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-lg"><?php echo $config['emoji']; ?></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <?php echo number_format($stat['reading_count']); ?> pts
                    </span>
                </div>
                <h3 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <?php echo ucfirst(str_replace('_', ' ', $stat['sensor_type'])); ?>
                </h3>
                <div class="flex items-center justify-between text-xs">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Avg:</span>
                        <span class="font-bold text-gray-900 dark:text-white ml-1">
                            <?php echo number_format($stat['avg_value'], 1); ?><?php echo $stat['unit']; ?>
                        </span>
                    </div>
                    <div class="text-gray-400 dark:text-gray-500">
                        <?php echo number_format($stat['min_value'], 1); ?> - <?php echo number_format($stat['max_value'], 1); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Compact Filters -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-3">
        <div class="flex flex-wrap items-center gap-2">
            <select id="sensorTypeFilter" onchange="applyFilters()" class="px-3 py-1.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Sensors</option>
                <option value="temperature" <?php echo $filterType === 'temperature' ? 'selected' : ''; ?>>🌡️ Temperature</option>
                <option value="humidity" <?php echo $filterType === 'humidity' ? 'selected' : ''; ?>>💧 Humidity</option>
                <option value="soil_moisture" <?php echo $filterType === 'soil_moisture' ? 'selected' : ''; ?>>🌱 Soil Moisture</option>
            </select>
            
            <select id="periodFilter" onchange="applyFilters()" class="px-3 py-1.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="1h" <?php echo $filterPeriod === '1h' ? 'selected' : ''; ?>>1 Hour</option>
                <option value="6h" <?php echo $filterPeriod === '6h' ? 'selected' : ''; ?>>6 Hours</option>
                <option value="24h" <?php echo $filterPeriod === '24h' ? 'selected' : ''; ?>>24 Hours</option>
                <option value="7d" <?php echo $filterPeriod === '7d' ? 'selected' : ''; ?>>7 Days</option>
                <option value="30d" <?php echo $filterPeriod === '30d' ? 'selected' : ''; ?>>30 Days</option>
            </select>
            
            <select id="chartTypeFilter" onchange="applyFilters()" class="px-3 py-1.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="line" <?php echo $chartType === 'line' ? 'selected' : ''; ?>>📈 Line</option>
                <option value="bar" <?php echo $chartType === 'bar' ? 'selected' : ''; ?>>📊 Bar</option>
                <option value="area" <?php echo $chartType === 'area' ? 'selected' : ''; ?>>📉 Area</option>
            </select>
            
            <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">
                <?php echo count($sensorData); ?> readings
            </span>
        </div>
    </div>
    
    <!-- Main Chart -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-3">
        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                📊 Visualization
            </h3>
        </div>
        <div class="p-3">
            <div class="chart-container" style="position: relative; height: 250px;">
                <canvas id="mainChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Individual Sensor Type Charts -->
    <?php if ($filterType === 'all'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-3">
        <?php 
        $sensorTypes = [
            'temperature' => ['name' => 'Temperature', 'emoji' => '🌡️', 'color' => 'red'],
            'humidity' => ['name' => 'Humidity', 'emoji' => '💧', 'color' => 'blue'],
            'soil_moisture' => ['name' => 'Soil Moisture', 'emoji' => '🌱', 'color' => 'green']
        ];
        
        foreach ($sensorTypes as $type => $config): 
            if (!isset($dataByType[$type]) || empty($dataByType[$type])) continue;
        ?>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="text-xs font-medium text-gray-900 dark:text-white">
                        <?php echo $config['emoji']; ?> <?php echo $config['name']; ?>
                    </h4>
                </div>
                <div class="p-2">
                    <div class="chart-container" style="position: relative; height: 140px;">
                        <canvas id="chart-<?php echo $type; ?>"></canvas>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                    📋 Recent Readings
                </h3>
                <input type="text" id="tableSearch" placeholder="Search..." class="px-2 py-1 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="dataTable">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer" onclick="sortTable(0)">
                            Time <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer" onclick="sortTable(1)">
                            Type <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer" onclick="sortTable(2)">
                            Sensor <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer" onclick="sortTable(3)">
                            Value <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php 
                    $typeEmojis = [
                        'temperature' => '🌡️',
                        'humidity' => '💧',
                        'soil_moisture' => '🌱'
                    ];
                    
                    // Limit to last 50 readings for better UX
                    $displayData = array_slice($sensorData, -50);
                    
                    foreach (array_reverse($displayData) as $reading): 
                        $value = $reading['value'];
                        $type = $reading['sensor_type'];
                        $threshold = $thresholds[$type] ?? ['min' => 0, 'max' => 100];
                        
                        $isOptimal = ($value >= $threshold['min'] && $value <= $threshold['max']);
                        $statusColor = $isOptimal ? 'green' : ($value < $threshold['min'] ? 'blue' : 'red');
                        $statusText = $isOptimal ? '✓' : ($value < $threshold['min'] ? '↓' : '↑');
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-3 py-2 text-xs text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                <?php echo date('M j, g:i A', strtotime($reading['recorded_at'])); ?>
                            </td>
                            <td class="px-3 py-2 text-xs">
                                <span class="inline-flex items-center">
                                    <?php echo $typeEmojis[$type] ?? '📊'; ?>
                                    <span class="ml-1 text-gray-700 dark:text-gray-300">
                                        <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                                    </span>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($reading['sensor_name']); ?>
                            </td>
                            <td class="px-3 py-2 text-sm font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($reading['value'], 1); ?><?php echo htmlspecialchars($reading['unit']); ?>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 text-sm bg-<?php echo $statusColor; ?>-100 dark:bg-<?php echo $statusColor; ?>-900/50 text-<?php echo $statusColor; ?>-700 dark:text-<?php echo $statusColor; ?>-300 rounded-full">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($sensorData)): ?>
            <div class="p-6 text-center">
                <div class="text-4xl mb-2">📊</div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No data for selected filters
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
    }

    const isDarkMode = document.documentElement.classList.contains('dark');
    const chartType = '<?php echo $chartType; ?>';
    
    // Color schemes
    const colorSchemes = {
        temperature: {
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.2)',
            pointBackgroundColor: '#ef4444'
        },
        humidity: {
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            pointBackgroundColor: '#3b82f6'
        },
        soil_moisture: {
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            pointBackgroundColor: '#10b981'
        }
    };

    // Prepare data
    const sensorData = <?php echo json_encode($sensorData); ?>;
    const dataByType = <?php echo json_encode($dataByType); ?>;
    
    // Create main chart
    createMainChart(sensorData, chartType, isDarkMode);
    
    // Create individual charts if showing all sensors
    <?php if ($filterType === 'all'): ?>
    Object.keys(dataByType).forEach(type => {
        if (dataByType[type].length > 0) {
            createTypeChart(type, dataByType[type], chartType, isDarkMode);
        }
    });
    <?php endif; ?>

    function createMainChart(data, type, darkMode) {
        const canvas = document.getElementById('mainChart');
        if (!canvas || data.length === 0) return;

        const ctx = canvas.getContext('2d');
        
        // Group by sensor type
        const grouped = {};
        data.forEach(reading => {
            if (!grouped[reading.sensor_type]) {
                grouped[reading.sensor_type] = {
                    labels: [],
                    values: []
                };
            }
            const time = new Date(reading.recorded_at).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
            grouped[reading.sensor_type].labels.push(time);
            grouped[reading.sensor_type].values.push(parseFloat(reading.value));
        });

        // Create datasets
        const datasets = Object.keys(grouped).map(sensorType => {
            const colors = colorSchemes[sensorType] || colorSchemes.temperature;
            return {
                label: sensorType.replace('_', ' ').toUpperCase(),
                data: grouped[sensorType].values,
                borderColor: colors.borderColor,
                backgroundColor: type === 'area' ? colors.backgroundColor : 'transparent',
                pointBackgroundColor: colors.pointBackgroundColor,
                pointBorderColor: '#ffffff',
                fill: type === 'area',
                tension: 0.4,
                borderWidth: 2,
                pointRadius: type === 'scatter' ? 4 : 2,
                pointHoverRadius: 6
            };
        });

        // Use first sensor type's labels (or merge all unique labels)
        const allLabels = Object.values(grouped)[0]?.labels || [];

        new Chart(ctx, {
            type: type === 'area' ? 'line' : type,
            data: {
                labels: allLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: darkMode ? '#e5e7eb' : '#374151',
                            font: { size: 12 },
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: darkMode ? '#1f2937' : '#ffffff',
                        titleColor: darkMode ? '#f9fafb' : '#111827',
                        bodyColor: darkMode ? '#e5e7eb' : '#374151',
                        borderColor: darkMode ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            color: darkMode ? '#374151' : '#e5e7eb',
                            drawBorder: false
                        },
                        ticks: {
                            color: darkMode ? '#9ca3af' : '#6b7280',
                            font: { size: 10 },
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: darkMode ? '#374151' : '#e5e7eb',
                            drawBorder: false
                        },
                        ticks: {
                            color: darkMode ? '#9ca3af' : '#6b7280',
                            font: { size: 11 }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    function createTypeChart(sensorType, data, type, darkMode) {
        const canvas = document.getElementById(`chart-${sensorType}`);
        if (!canvas || data.length === 0) return;

        const ctx = canvas.getContext('2d');
        const colors = colorSchemes[sensorType] || colorSchemes.temperature;

        const labels = data.map(r => {
            const date = new Date(r.recorded_at);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        });
        const values = data.map(r => parseFloat(r.value));

        new Chart(ctx, {
            type: type === 'area' ? 'line' : type,
            data: {
                labels: labels,
                datasets: [{
                    label: sensorType.replace('_', ' ').toUpperCase(),
                    data: values,
                    borderColor: colors.borderColor,
                    backgroundColor: type === 'area' ? colors.backgroundColor : 'transparent',
                    pointBackgroundColor: colors.pointBackgroundColor,
                    pointBorderColor: '#ffffff',
                    fill: type === 'area',
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: type === 'scatter' ? 3 : 1.5,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: darkMode ? '#1f2937' : '#ffffff',
                        titleColor: darkMode ? '#f9fafb' : '#111827',
                        bodyColor: darkMode ? '#e5e7eb' : '#374151',
                        borderColor: darkMode ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        cornerRadius: 6,
                        padding: 8
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: { display: false },
                        ticks: {
                            color: darkMode ? '#9ca3af' : '#6b7280',
                            font: { size: 8 },
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: darkMode ? '#374151' : '#e5e7eb',
                            drawBorder: false
                        },
                        ticks: {
                            color: darkMode ? '#9ca3af' : '#6b7280',
                            font: { size: 9 }
                        }
                    }
                }
            }
        });
    }
});

// Filter functions
function applyFilters() {
    const sensorType = document.getElementById('sensorTypeFilter').value;
    const period = document.getElementById('periodFilter').value;
    const chart = document.getElementById('chartTypeFilter').value;
    
    window.location.href = `sensors.php?sensor_type=${sensorType}&period=${period}&chart=${chart}`;
}

// Table search
document.getElementById('tableSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#dataTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Table sorting
let sortDirection = {};
function sortTable(columnIndex) {
    const table = document.getElementById('dataTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const direction = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
    sortDirection[columnIndex] = direction;
    
    rows.sort((a, b) => {
        let aText = a.cells[columnIndex].textContent.trim();
        let bText = b.cells[columnIndex].textContent.trim();
        
        // For value column, extract numeric value
        if (columnIndex === 3) {
            aText = aText.replace(/[^0-9.-]/g, '');
            bText = bText.replace(/[^0-9.-]/g, '');
        }
        
        // Try numeric comparison
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // String comparison
        return direction === 'asc' 
            ? aText.localeCompare(bText)
            : bText.localeCompare(aText);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}



// Real-time updates if Arduino is connected
<?php if ($arduinoHealthy): ?>
setInterval(() => {
    fetch('arduino_sync.php?action=get_all')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                console.log('Arduino data updated:', data.data);
                // Could update stats cards here in real-time
            }
        })
        .catch(error => console.log('Arduino update failed:', error));
}, 10000); // Update every 10 seconds
<?php endif; ?>
</script>

<?php
include 'includes/footer.php';
?>
</main>
</div>
</div>

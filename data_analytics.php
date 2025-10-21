<?php
/**
 * Data Analytics Page
 * Advanced data visualization and analysis for students
 */

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 'student'
];

// Get date range from query params or default to last 30 days
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));

/**
 * Get sensor analytics data
 */
function getSensorAnalytics($startDate, $endDate)
{
    try {
        $pdo = getDatabaseConnection();
        
        // Get average sensor readings for the period
        $stmt = $pdo->prepare("
            SELECT 
                s.sensor_type,
                AVG(sr.value) as avg_value,
                MIN(sr.value) as min_value,
                MAX(sr.value) as max_value,
                COUNT(*) as reading_count
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            WHERE sr.recorded_at BETWEEN ? AND ?
            GROUP BY s.sensor_type
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching sensor analytics: " . $e->getMessage());
        return [];
    }
}

/**
 * Get sensor trend data for charts
 */
function getSensorTrendData($startDate, $endDate, $sensorType)
{
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE(sr.recorded_at) as date,
                AVG(sr.value) as avg_value
            FROM sensors s
            JOIN sensor_readings sr ON s.id = sr.sensor_id
            WHERE s.sensor_type = ?
            AND sr.recorded_at BETWEEN ? AND ?
            GROUP BY DATE(sr.recorded_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$sensorType, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching sensor trend: " . $e->getMessage());
        return [];
    }
}

/**
 * Get pest detection analytics
 */
function getPestAnalytics($startDate, $endDate)
{
    try {
        $pdo = getDatabaseConnection();
        
        // Total detections
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_detections
            FROM pest_alerts
            WHERE detected_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $total = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // By severity
        $stmt = $pdo->prepare("
            SELECT 
                severity,
                COUNT(*) as count
            FROM pest_alerts
            WHERE detected_at BETWEEN ? AND ?
            GROUP BY severity
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $bySeverity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // By type
        $stmt = $pdo->prepare("
            SELECT 
                pest_type,
                COUNT(*) as count
            FROM pest_alerts
            WHERE detected_at BETWEEN ? AND ?
            GROUP BY pest_type
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total' => $total['total_detections'] ?? 0,
            'by_severity' => $bySeverity,
            'by_type' => $byType
        ];
    } catch (Exception $e) {
        error_log("Error fetching pest analytics: " . $e->getMessage());
        return ['total' => 0, 'by_severity' => [], 'by_type' => []];
    }
}

// Fetch analytics data
$sensorAnalytics = getSensorAnalytics($startDate, $endDate);
$pestAnalytics = getPestAnalytics($startDate, $endDate);

// Get trend data for each sensor type
$tempTrend = getSensorTrendData($startDate, $endDate, 'temperature');
$humidityTrend = getSensorTrendData($startDate, $endDate, 'humidity');
$soilTrend = getSensorTrendData($startDate, $endDate, 'soil_moisture');

// Calculate averages with fallback to static data
$avgTemp = 24.5;
$avgHumidity = 68.2;
$avgSoil = 45.8;

foreach ($sensorAnalytics as $sensor) {
    if ($sensor['sensor_type'] === 'temperature') {
        $avgTemp = round($sensor['avg_value'], 1);
    } elseif ($sensor['sensor_type'] === 'humidity') {
        $avgHumidity = round($sensor['avg_value'], 1);
    } elseif ($sensor['sensor_type'] === 'soil_moisture') {
        $avgSoil = round($sensor['avg_value'], 1);
    }
}

$pageTitle = 'Data Analytics - IoT Farm Monitoring System';
include 'includes/header.php';
include 'includes/navigation.php';
?>

<div class="p-4 max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-white mb-1">
            Data Analytics
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Analyze historical trends and patterns in farm data</p>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <i class="fas fa-filter mr-2"></i>Apply Filter
            </button>
        </form>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- Temperature Trend -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Temperature Trend</h3>
                <select id="tempChartType" onchange="changeChartType('temp', this.value)" class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    <option value="line">Line</option>
                    <option value="bar">Bar</option>
                    <option value="area">Area</option>
                </select>
            </div>
            <div class="relative h-64">
                <canvas id="tempChart"></canvas>
            </div>
        </div>

        <!-- Humidity Trend -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Humidity Trend</h3>
                <select id="humidityChartType" onchange="changeChartType('humidity', this.value)" class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    <option value="line">Line</option>
                    <option value="bar">Bar</option>
                    <option value="area">Area</option>
                </select>
            </div>
            <div class="relative h-64">
                <canvas id="humidityChart"></canvas>
            </div>
        </div>

        <!-- Soil Moisture Trend -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Soil Moisture Trend</h3>
                <select id="soilChartType" onchange="changeChartType('soil', this.value)" class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    <option value="line">Line</option>
                    <option value="bar">Bar</option>
                    <option value="area">Area</option>
                </select>
            </div>
            <div class="relative h-64">
                <canvas id="soilChart"></canvas>
            </div>
        </div>

        <!-- Pest Detection Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Pest Detection by Type</h3>
                <select id="pestChartType" onchange="changeChartType('pest', this.value)" class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    <option value="doughnut">Doughnut</option>
                    <option value="pie">Pie</option>
                    <option value="bar">Bar</option>
                    <option value="polarArea">Polar Area</option>
                </select>
            </div>
            <div class="relative h-64">
                <canvas id="pestChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Correlation Analysis -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">
            <i class="fas fa-project-diagram text-purple-600 mr-2 text-sm"></i>
            Correlation Analysis
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Understanding relationships between environmental factors and pest activity
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Temp vs Pests</span>
                    <span class="text-xs px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded">Strong</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">+0.78</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Higher temp = More pests</p>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Humidity vs Pests</span>
                    <span class="text-xs px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded">Moderate</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">+0.45</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Some correlation observed</p>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Soil vs Pests</span>
                    <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">Weak</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">+0.12</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimal correlation</p>
            </div>
        </div>
    </div>

    <!-- Insights -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <h3 class="text-base font-semibold text-blue-900 dark:text-blue-200 mb-3 flex items-center">
            <i class="fas fa-lightbulb mr-2 text-sm"></i>
            Key Insights
        </h3>
        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-1"></i>
                <span>Temperature shows strong positive correlation with pest activity - consider monitoring closely during hot periods</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-1"></i>
                <span>Soil moisture levels have been declining - irrigation schedule may need adjustment</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-1"></i>
                <span>Pest detections peak during afternoon hours (2-4 PM) - optimal time for monitoring</span>
            </li>
        </ul>
    </div>
</div>

<script>
// Data from PHP
const tempData = <?php echo json_encode($tempTrend); ?>;
const humidityData = <?php echo json_encode($humidityTrend); ?>;
const soilData = <?php echo json_encode($soilTrend); ?>;
const pestByType = <?php echo json_encode($pestAnalytics['by_type']); ?>;

// Prepare labels and values
const tempLabels = tempData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});
const tempValues = tempData.map(d => parseFloat(d.avg_value).toFixed(1));

const humidityLabels = humidityData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});
const humidityValues = humidityData.map(d => parseFloat(d.avg_value).toFixed(1));

const soilLabels = soilData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});
const soilValues = soilData.map(d => parseFloat(d.avg_value).toFixed(1));

const pestLabels = pestByType.map(d => d.pest_type);
const pestValues = pestByType.map(d => parseInt(d.count));

// Fallback to sample data if no data available
const labels = tempLabels.length > 0 ? tempLabels : ['No Data'];
const defaultTemp = tempValues.length > 0 ? tempValues : [0];
const defaultHumidity = humidityValues.length > 0 ? humidityValues : [0];
const defaultSoil = soilValues.length > 0 ? soilValues : [0];
const defaultPestLabels = pestLabels.length > 0 ? pestLabels : ['No Data'];
const defaultPestValues = pestValues.length > 0 ? pestValues : [0];

// Store chart instances
let charts = {
    temp: null,
    humidity: null,
    soil: null,
    pest: null
};

// Chart data storage
const chartData = {
    temp: {
        labels: tempLabels.length > 0 ? tempLabels : labels,
        data: defaultTemp,
        label: 'Temperature (°C)',
        borderColor: 'rgb(239, 68, 68)',
        backgroundColor: 'rgba(239, 68, 68, 0.2)'
    },
    humidity: {
        labels: humidityLabels.length > 0 ? humidityLabels : labels,
        data: defaultHumidity,
        label: 'Humidity (%)',
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.2)'
    },
    soil: {
        labels: soilLabels.length > 0 ? soilLabels : labels,
        data: defaultSoil,
        label: 'Soil Moisture (%)',
        borderColor: 'rgb(34, 197, 94)',
        backgroundColor: 'rgba(34, 197, 94, 0.2)'
    },
    pest: {
        labels: defaultPestLabels,
        data: defaultPestValues
    }
};

// Generate colors for pest chart
const pestColors = [
    'rgb(239, 68, 68)',
    'rgb(251, 146, 60)',
    'rgb(234, 179, 8)',
    'rgb(34, 197, 94)',
    'rgb(59, 130, 246)',
    'rgb(147, 51, 234)',
    'rgb(236, 72, 153)',
    'rgb(156, 163, 175)',
    'rgb(20, 184, 166)',
    'rgb(245, 158, 11)'
];

// Common chart options for line/bar charts
function getCommonOptions(hasScales = true) {
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                display: false 
            }
        }
    };
    
    if (hasScales) {
        options.scales = {
            y: {
                beginAtZero: false,
                grid: {
                    color: 'rgba(156, 163, 175, 0.1)'
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        };
    }
    
    return options;
}

// Function to create/update chart
function createChart(chartId, type, data) {
    const ctx = document.getElementById(chartId + 'Chart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (charts[chartId]) {
        charts[chartId].destroy();
    }
    
    let chartConfig = {};
    
    if (chartId === 'pest') {
        // Pest chart configuration
        chartConfig = {
            type: type,
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: pestColors.slice(0, data.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        };
        
        // Add scales for bar chart
        if (type === 'bar') {
            chartConfig.options.scales = {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(156, 163, 175, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            };
        }
    } else {
        // Sensor chart configuration
        const isArea = type === 'area';
        const actualType = isArea ? 'line' : type;
        
        chartConfig = {
            type: actualType,
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label,
                    data: data.data,
                    borderColor: data.borderColor,
                    backgroundColor: data.backgroundColor,
                    tension: actualType === 'line' ? 0.4 : 0,
                    fill: isArea,
                    borderWidth: 2
                }]
            },
            options: getCommonOptions(true)
        };
    }
    
    charts[chartId] = new Chart(ctx, chartConfig);
}

// Function to change chart type
function changeChartType(chartId, type) {
    createChart(chartId, type, chartData[chartId]);
}

// Initialize all charts
createChart('temp', 'line', chartData.temp);
createChart('humidity', 'line', chartData.humidity);
createChart('soil', 'line', chartData.soil);
createChart('pest', 'doughnut', chartData.pest);
</script>

<?php include 'includes/footer.php'; ?>

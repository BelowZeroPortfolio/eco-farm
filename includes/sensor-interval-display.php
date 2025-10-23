<?php
/**
 * Sensor Interval Display Component
 * Shows current logging interval on dashboard
 */

try {
    require_once __DIR__ . '/arduino-api.php';
    $arduino = new ArduinoBridge();
    $intervalSetting = $arduino->getLoggingIntervalSetting();
    $currentInterval = $intervalSetting['interval_minutes'] ?? 30;
    $formattedInterval = $intervalSetting['formatted'] ?? '30 minutes';
    
    // Determine color based on interval
    $intervalColor = 'blue';
    if ($currentInterval < 1) {
        $intervalColor = 'orange'; // Testing mode
    } elseif ($currentInterval <= 15) {
        $intervalColor = 'green'; // High frequency
    } elseif ($currentInterval <= 60) {
        $intervalColor = 'blue'; // Standard
    } else {
        $intervalColor = 'purple'; // Low frequency
    }
    
} catch (Exception $e) {
    error_log("Error loading sensor interval: " . $e->getMessage());
    $formattedInterval = 'Unknown';
    $intervalColor = 'gray';
}
?>

<!-- Sensor Logging Interval Display -->
<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-<?php echo $intervalColor; ?>-100 dark:bg-<?php echo $intervalColor; ?>-900 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-<?php echo $intervalColor; ?>-600 dark:text-<?php echo $intervalColor; ?>-400"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sensor Logging Interval</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400">Database logging frequency</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold text-<?php echo $intervalColor; ?>-600 dark:text-<?php echo $intervalColor; ?>-400">
                <?php echo htmlspecialchars($formattedInterval); ?>
            </div>
            <a href="settings.php" class="text-xs text-gray-500 dark:text-gray-400 hover:text-<?php echo $intervalColor; ?>-600 dark:hover:text-<?php echo $intervalColor; ?>-400">
                <i class="fas fa-cog mr-1"></i>Change in Settings
            </a>
        </div>
    </div>
    
    <?php if ($currentInterval < 1): ?>
        <div class="mt-3 p-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
            <p class="text-xs text-orange-700 dark:text-orange-300">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Testing Mode:</strong> Very high frequency logging. Not recommended for production use.
            </p>
        </div>
    <?php endif; ?>
</div>

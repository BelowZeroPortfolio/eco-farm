<?php
/**
 * Plant Monitoring Dashboard Widget
 * Include this in dashboard.php to show plant monitoring status
 */

require_once __DIR__ . '/plant-monitor-logic.php';

$monitor = new PlantMonitor();
$activePlant = $monitor->getActivePlant();
$recentNotifications = $monitor->getNotifications(5, true); // Unread only
$stats = $monitor->getSensorStatistics(24);
$latestReadings = $monitor->getLatestReadings(1);
$latestReading = !empty($latestReadings) ? $latestReadings[0] : null;
?>

<style>
.plant-widget {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.plant-widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #4CAF50;
}

.plant-widget-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
}

.plant-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.plant-status-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.plant-status-value {
    font-size: 32px;
    font-weight: bold;
    margin: 10px 0;
}

.plant-status-label {
    font-size: 14px;
    opacity: 0.9;
}

.plant-status-range {
    font-size: 12px;
    opacity: 0.8;
    margin-top: 5px;
}

.notification-badge {
    background-color: #f44336;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.plant-notification-item {
    background: #fff3cd;
    border-left: 4px solid #ff9800;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 5px;
    font-size: 14px;
}

.plant-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.plant-stat-item {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.plant-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #4CAF50;
}

.plant-stat-label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.btn-plant {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    margin: 5px;
}

.btn-plant-primary {
    background-color: #4CAF50;
    color: white;
}

.btn-plant-info {
    background-color: #2196F3;
    color: white;
}

.btn-plant:hover {
    opacity: 0.8;
}

.status-indicator-widget {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}

.status-ok {
    background-color: #4CAF50;
}

.status-warning {
    background-color: #ff9800;
}

.status-error {
    background-color: #f44336;
}
</style>

<div class="plant-widget">
    <div class="plant-widget-header">
        <div class="plant-widget-title">
            🌱 Plant Monitoring System
        </div>
        <?php if (count($recentNotifications) > 0): ?>
        <div class="notification-badge">
            <?= count($recentNotifications) ?> New Alert<?= count($recentNotifications) > 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($activePlant): ?>
    <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <strong>Active Plant:</strong> 
        <?= htmlspecialchars($activePlant['PlantName']) ?> 
        (<?= htmlspecialchars($activePlant['LocalName']) ?>)
    </div>

    <?php if ($latestReading): ?>
    <div class="plant-status-grid">
        <div class="plant-status-item" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="plant-status-label">Temperature</div>
            <div class="plant-status-value"><?= number_format($latestReading['Temperature'], 1) ?>°C</div>
            <div class="plant-status-range">
                Range: <?= $activePlant['MinTemperature'] ?>-<?= $activePlant['MaxTemperature'] ?>°C
            </div>
            <?php 
            $tempOk = $latestReading['Temperature'] >= $activePlant['MinTemperature'] && 
                      $latestReading['Temperature'] <= $activePlant['MaxTemperature'];
            ?>
            <span class="status-indicator-widget <?= $tempOk ? 'status-ok' : 'status-error' ?>"></span>
            <?= $tempOk ? 'Normal' : 'Out of Range' ?>
        </div>

        <div class="plant-status-item" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="plant-status-label">Humidity</div>
            <div class="plant-status-value"><?= number_format($latestReading['Humidity'], 1) ?>%</div>
            <div class="plant-status-range">
                Range: <?= $activePlant['MinHumidity'] ?>-<?= $activePlant['MaxHumidity'] ?>%
            </div>
            <?php 
            $humOk = $latestReading['Humidity'] >= $activePlant['MinHumidity'] && 
                     $latestReading['Humidity'] <= $activePlant['MaxHumidity'];
            ?>
            <span class="status-indicator-widget <?= $humOk ? 'status-ok' : 'status-error' ?>"></span>
            <?= $humOk ? 'Normal' : 'Out of Range' ?>
        </div>

        <div class="plant-status-item" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="plant-status-label">Soil Moisture</div>
            <div class="plant-status-value"><?= number_format($latestReading['SoilMoisture'], 1) ?>%</div>
            <div class="plant-status-range">
                Range: <?= $activePlant['MinSoilMoisture'] ?>-<?= $activePlant['MaxSoilMoisture'] ?>%
            </div>
            <?php 
            $soilOk = $latestReading['SoilMoisture'] >= $activePlant['MinSoilMoisture'] && 
                      $latestReading['SoilMoisture'] <= $activePlant['MaxSoilMoisture'];
            ?>
            <span class="status-indicator-widget <?= $soilOk ? 'status-ok' : 'status-error' ?>"></span>
            <?= $soilOk ? 'Normal' : 'Out of Range' ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($recentNotifications)): ?>
    <div style="margin: 20px 0;">
        <h3 style="margin-bottom: 15px;">⚠️ Recent Alerts</h3>
        <?php foreach ($recentNotifications as $notification): ?>
        <div class="plant-notification-item">
            <strong><?= ucwords(str_replace('_', ' ', $notification['SensorType'])) ?>:</strong>
            <?= htmlspecialchars($notification['Message']) ?>
            <br>
            <small style="color: #666;">
                <?= date('M d, h:i A', strtotime($notification['CreatedAt'])) ?>
            </small>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($stats && $stats['reading_count'] > 0): ?>
    <div class="plant-stats">
        <div class="plant-stat-item">
            <div class="plant-stat-value"><?= number_format($stats['avg_temp'], 1) ?>°C</div>
            <div class="plant-stat-label">Avg Temperature (24h)</div>
        </div>
        <div class="plant-stat-item">
            <div class="plant-stat-value"><?= number_format($stats['avg_humidity'], 1) ?>%</div>
            <div class="plant-stat-label">Avg Humidity (24h)</div>
        </div>
        <div class="plant-stat-item">
            <div class="plant-stat-value"><?= number_format($stats['avg_soil'], 1) ?>%</div>
            <div class="plant-stat-label">Avg Soil Moisture (24h)</div>
        </div>
        <div class="plant-stat-item">
            <div class="plant-stat-value"><?= $stats['reading_count'] ?></div>
            <div class="plant-stat-label">Readings (24h)</div>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top: 20px; text-align: center;">
        <a href="notifications.php" class="btn-plant btn-plant-primary">
            View All Notifications
        </a>
        <a href="plant_database.php" class="btn-plant btn-plant-info">
            Manage Plants
        </a>
        <a href="plant_service_control.php" class="btn-plant btn-plant-info">
            Service Control
        </a>
    </div>

    <?php else: ?>
    <div style="text-align: center; padding: 40px; color: #999;">
        <p>No active plant configured.</p>
        <a href="plant_database.php" class="btn-plant btn-plant-primary">
            Configure Plant
        </a>
    </div>
    <?php endif; ?>
</div>

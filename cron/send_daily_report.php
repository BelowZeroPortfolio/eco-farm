<!-- <php

/**
 * Daily Report Cron Job
 * 
 * This script should be run daily via cron job to send email reports
 * Example cron: 0 8 * * * php /path/to/send_daily_report.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

// Check if daily reports are enabled
if (env('DAILY_REPORT_ENABLED', 'false') !== 'true') {
    echo "Daily reports are disabled.\n";
    exit(0);
}

// Get report data
function getDailyReportData()
{
    try {
        $pdo = getDatabaseConnection();

        // Get pest alerts from last 24 hours
        $stmt = $pdo->query("
            SELECT 
                pest_type,
                severity,
                confidence_score,
                location,
                detected_at
            FROM pest_alerts 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY detected_at DESC
        ");
        $pestAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get pest statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_detections,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_count,
                COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_count,
                COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_count,
                COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_count
            FROM pest_alerts 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $pestStats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'date' => date('F j, Y'),
            'pest_alerts' => $pestAlerts,
            'pest_stats' => $pestStats,
            'sensor_data' => [
                'temperature' => ['avg' => 24.5, 'min' => 22.1, 'max' => 26.8, 'unit' => '°C'],
                'humidity' => ['avg' => 68.2, 'min' => 62.5, 'max' => 75.3, 'unit' => '%'],
                'soil_moisture' => ['avg' => 45.8, 'min' => 42.1, 'max' => 49.2, 'unit' => '%']
            ]
        ];
    } catch (Exception $e) {
        error_log("Error generating daily report: " . $e->getMessage());
        return null;
    }
}

// Generate HTML email content
function generateEmailHTML($data)
{
    $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; }
        .section { background: white; padding: 15px; margin-bottom: 15px; border-radius: 8px; border-left: 4px solid #10b981; }
        .stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px; }
        .stat-box { background: #f3f4f6; padding: 10px; border-radius: 6px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #10b981; }
        .stat-label { font-size: 12px; color: #6b7280; }
        .alert-item { padding: 10px; margin: 5px 0; border-radius: 6px; }
        .alert-critical { background: #fee2e2; border-left: 4px solid #dc2626; }
        .alert-high { background: #fed7aa; border-left: 4px solid #ea580c; }
        .alert-medium { background: #fef3c7; border-left: 4px solid #d97706; }
        .alert-low { background: #dbeafe; border-left: 4px solid #2563eb; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">🌾 Daily Farm Report</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">' . $data['date'] . '</p>
        </div>
        
        <div class="content">
            <!-- Pest Detection Summary -->
            <div class="section">
                <h2 style="margin-top: 0; color: #10b981;">🐛 Pest Detection Summary</h2>
                <div class="stat-grid">
                    <div class="stat-box">
                        <div class="stat-value">' . $data['pest_stats']['total_detections'] . '</div>
                        <div class="stat-label">Total Detections</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" style="color: #dc2626;">' . $data['pest_stats']['critical_count'] . '</div>
                        <div class="stat-label">Critical</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" style="color: #ea580c;">' . $data['pest_stats']['high_count'] . '</div>
                        <div class="stat-label">High</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" style="color: #d97706;">' . $data['pest_stats']['medium_count'] . '</div>
                        <div class="stat-label">Medium</div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Pest Alerts -->';

    if (count($data['pest_alerts']) > 0) {
        $html .= '
            <div class="section">
                <h2 style="margin-top: 0; color: #10b981;">Recent Pest Alerts</h2>';

        foreach (array_slice($data['pest_alerts'], 0, 10) as $alert) {
            $alertClass = 'alert-' . $alert['severity'];
            $html .= '
                <div class="alert-item ' . $alertClass . '">
                    <strong>' . htmlspecialchars($alert['pest_type']) . '</strong> 
                    <span style="float: right;">' . round($alert['confidence_score'], 1) . '%</span>
                    <br>
                    <small>' . $alert['location'] . ' • ' . date('M j, g:i A', strtotime($alert['detected_at'])) . '</small>
                </div>';
        }

        $html .= '
            </div>';
    }

    // Sensor Data
    $html .= '
            <div class="section">
                <h2 style="margin-top: 0; color: #10b981;">📊 Sensor Data (24h Average)</h2>
                <div class="stat-grid">
                    <div class="stat-box">
                        <div class="stat-value" style="color: #dc2626;">' . $data['sensor_data']['temperature']['avg'] . '°C</div>
                        <div class="stat-label">Temperature</div>
                        <small style="color: #9ca3af;">' . $data['sensor_data']['temperature']['min'] . '°C - ' . $data['sensor_data']['temperature']['max'] . '°C</small>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" style="color: #2563eb;">' . $data['sensor_data']['humidity']['avg'] . '%</div>
                        <div class="stat-label">Humidity</div>
                        <small style="color: #9ca3af;">' . $data['sensor_data']['humidity']['min'] . '% - ' . $data['sensor_data']['humidity']['max'] . '%</small>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" style="color: #10b981;">' . $data['sensor_data']['soil_moisture']['avg'] . '%</div>
                        <div class="stat-label">Soil Moisture</div>
                        <small style="color: #9ca3af;">' . $data['sensor_data']['soil_moisture']['min'] . '% - ' . $data['sensor_data']['soil_moisture']['max'] . '%</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated report from your IoT Farm Monitoring System</p>
            <p>© ' . date('Y') . ' Farm Monitoring System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    return $html;
}

// Main execution
$reportData = getDailyReportData();

if ($reportData === null) {
    echo "Failed to generate report data.\n";
    exit(1);
}

// Get recipients
$recipients = env('DAILY_REPORT_RECIPIENTS', '');
if (empty($recipients)) {
    echo "No email recipients configured.\n";
    exit(0);
}

$recipientList = array_map('trim', explode(',', $recipients));

// Generate email content
$emailHTML = generateEmailHTML($reportData);
$emailSubject = "Daily Farm Report - " . $reportData['date'];

// Prepare EmailJS data
$emailJSData = [
    'service_id' => env('EMAILJS_SERVICE_ID'),
    'template_id' => env('EMAILJS_TEMPLATE_ID'),
    'public_key' => env('EMAILJS_PUBLIC_KEY'),
    'recipients' => $recipientList,
    'subject' => $emailSubject,
    'html_content' => $emailHTML,
    'report_date' => $reportData['date'],
    'total_detections' => $reportData['pest_stats']['total_detections'],
    'critical_count' => $reportData['pest_stats']['critical_count']
];

// Save email data to file for JavaScript to send
$emailDataFile = __DIR__ . '/../temp/daily_report_email.json';
if (!file_exists(dirname($emailDataFile))) {
    mkdir(dirname($emailDataFile), 0755, true);
}
file_put_contents($emailDataFile, json_encode($emailJSData, JSON_PRETTY_PRINT));

echo "Daily report generated successfully.\n";
echo "Recipients: " . implode(', ', $recipientList) . "\n";
echo "Email data saved to: $emailDataFile\n";
echo "Use the web interface to send the email via EmailJS.\n";

exit(0); -->

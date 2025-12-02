<?php

/**
 * Export Handler for IoT Farm Monitoring System
 * 
 * Handles CSV and PDF export functionality with security measures
 * and customization options for reports
 */

// Include database configuration
require_once __DIR__ . '/../config/database.php';

class ExportHandler
{
    private $allowedFormats = ['csv', 'pdf'];
    private $allowedReportTypes = ['sensor', 'pest'];
    private $maxExportRows = 10000;

    /**
     * Validate export request parameters with basic security
     */
    public function validateExportRequest($userId, $userRole, $format, $reportType, $startDate, $endDate)
    {
        $errors = [];

        // Basic permission check
        if ($userRole === 'student' && $format === 'pdf') {
            $errors[] = "Students can only export CSV format.";
        }

        // Validate format
        if (!in_array($format, $this->allowedFormats)) {
            $errors[] = "Invalid export format.";
        }

        // Validate report type
        if (!in_array($reportType, $this->allowedReportTypes)) {
            $errors[] = "Invalid report type.";
        }

        // Additional validation
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            $errors[] = "Invalid date format. Use YYYY-MM-DD format.";
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            $errors[] = "Start date cannot be after end date.";
        }

        // Check date range (max 1 year for security)
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
        if ($daysDiff > 365) {
            $errors[] = "Date range cannot exceed 365 days.";
        }

        return $errors;
    }

    /**
     * Check if date is valid
     */
    private function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Generate secure filename
     */
    public function generateSecureFilename($reportType, $startDate, $endDate, $format)
    {
        $timestamp = date('Y-m-d_H-i-s');
        $sanitizedType = preg_replace('/[^a-zA-Z0-9_-]/', '', $reportType);
        $sanitizedStart = preg_replace('/[^0-9-]/', '', $startDate);
        $sanitizedEnd = preg_replace('/[^0-9-]/', '', $endDate);

        return "farm_report_{$sanitizedType}_{$sanitizedStart}_to_{$sanitizedEnd}_{$timestamp}.{$format}";
    }

    /**
     * Export data to CSV with enhanced security and customization
     */
    public function exportToCSV($userId, $userRole, $reportType, $startDate, $endDate, $options = [])
    {
        try {
            // Validate request with security checks
            $errors = $this->validateExportRequest($userId, $userRole, 'csv', $reportType, $startDate, $endDate);
            if (!empty($errors)) {
                throw new InvalidArgumentException(implode('; ', $errors));
            }

            // Get user-specific row limit
            $maxRows = ($userRole === 'admin') ? 50000 : $this->maxExportRows;

            // Get data
            $data = $this->getExportData($reportType, $startDate, $endDate, $maxRows);

            // Check if data exists
            if (empty($data)) {
                throw new Exception("No data found for the selected date range ({$startDate} to {$endDate}). Please check your date range and try again.");
            }

            // Check row limit
            if (count($data) > $maxRows) {
                throw new Exception("Export exceeds maximum allowed rows ({$maxRows}). Please narrow your date range.");
            }

            // Generate secure filename
            $filename = $this->generateSecureFilename($reportType, $startDate, $endDate, 'csv');

            // Clear ALL output buffers to prevent header issues
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Start fresh output buffering to catch any unexpected output
            ob_start();

            // Disable error reporting for clean CSV output
            $originalErrorReporting = error_reporting(0);
            $originalDisplayErrors = ini_get('display_errors');
            ini_set('display_errors', 0);

            // Clear the buffer and send headers
            ob_end_clean();

            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');
            header('Pragma: public');

            // Create output stream
            $output = fopen('php://output', 'w');
            if (!$output) {
                throw new Exception("Failed to create output stream for CSV export.");
            }

            // Add BOM for UTF-8 compatibility
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Add professional metadata header
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, ['IoT Farm Monitoring System - Data Export Report']);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, []);
            fputcsv($output, ['Report Information']);
            fputcsv($output, ['─────────────────────────────────────────────────────────────']);
            fputcsv($output, ['Report Type:', ucfirst($reportType) . ' Data Report']);
            fputcsv($output, ['Date Range:', date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate))]);
            fputcsv($output, ['Generated On:', date('F j, Y \a\t g:i A T')]);
            fputcsv($output, ['Total Records:', number_format(count($data))]);
            fputcsv($output, ['Exported By:', 'User ID ' . $userId]);
            fputcsv($output, []);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, ['DATA SECTION']);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, []); // Empty line

            if ($reportType === 'sensor') {
                // Sensor data headers with better formatting
                $headers = [
                    'DATE',
                    'SENSOR NAME',
                    'SENSOR TYPE',
                    'LOCATION',
                    'AVG VALUE',
                    'MIN VALUE',
                    'MAX VALUE',
                    'UNIT',
                    'READINGS',
                    'QUALITY'
                ];
                fputcsv($output, $headers);

                // Add separator line
                fputcsv($output, array_fill(0, count($headers), '─────────────'));

                foreach ($data as $row) {
                    $csvRow = [
                        date('M j, Y', strtotime($row['date'])),
                        $row['sensor_name'],
                        ucfirst(str_replace('_', ' ', $row['sensor_type'])),
                        $row['location'],
                        number_format($row['avg_value'], 2) . ' ' . $row['unit'],
                        number_format($row['min_value'], 2) . ' ' . $row['unit'],
                        number_format($row['max_value'], 2) . ' ' . $row['unit'],
                        $row['unit'],
                        number_format($row['reading_count']),
                        $this->calculateDataQuality($row['reading_count'])
                    ];
                    fputcsv($output, $csvRow);
                }
            } else {
                // Pest data headers with better formatting
                $headers = [
                    'DATE & TIME',
                    'PEST TYPE',
                    'SEVERITY',
                    'CONFIDENCE',
                    'CAMERA',
                    'LOCATION',
                    'STATUS',
                    'SUGGESTED ACTIONS'
                ];
                fputcsv($output, $headers);

                // Add separator line
                fputcsv($output, array_fill(0, count($headers), '─────────────'));

                foreach ($data as $row) {
                    $csvRow = [
                        date('M j, Y g:i A', strtotime($row['detected_at'])),
                        $row['pest_type'],
                        strtoupper($row['severity']),
                        isset($row['confidence_score']) ? number_format($row['confidence_score'], 1) . '%' : 'N/A',
                        isset($row['camera_name']) && $row['camera_name'] ? $row['camera_name'] : 'Manual Entry',
                        $row['location'],
                        ucfirst($row['status']),
                        isset($row['suggested_actions']) && $row['suggested_actions'] ? $row['suggested_actions'] : 'No actions suggested'
                    ];
                    fputcsv($output, $csvRow);
                }
            }

            // Add statistical analysis section
            fputcsv($output, []);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, ['STATISTICAL ANALYSIS']);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, []);
            
            if ($reportType === 'sensor') {
                // Calculate sensor statistics
                $stats = $this->calculateSensorStatistics($data);
                
                fputcsv($output, ['Metric', 'Value']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                fputcsv($output, ['Total Sensor-Days', number_format(count($data))]);
                fputcsv($output, ['Total Readings', number_format($stats['total_readings'])]);
                fputcsv($output, ['Average Readings per Day', number_format($stats['avg_readings_per_day'], 1)]);
                fputcsv($output, ['Unique Sensors', $stats['unique_sensors']]);
                fputcsv($output, ['Data Quality (Excellent)', $stats['excellent_quality'] . '%']);
                fputcsv($output, []);
                
                // By sensor type
                fputcsv($output, ['Statistics by Sensor Type']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                fputcsv($output, ['Sensor Type', 'Records', 'Avg Value', 'Min Value', 'Max Value', 'Unit']);
                
                foreach ($stats['by_type'] as $type => $typeStats) {
                    fputcsv($output, [
                        ucfirst(str_replace('_', ' ', $type)),
                        $typeStats['count'],
                        number_format($typeStats['avg'], 2),
                        number_format($typeStats['min'], 2),
                        number_format($typeStats['max'], 2),
                        $typeStats['unit']
                    ]);
                }
                
                fputcsv($output, []);
                fputcsv($output, ['Trend Analysis']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                fputcsv($output, [$stats['trend_analysis']]);
                
            } else {
                // Calculate pest statistics
                $stats = $this->calculatePestStatistics($data);
                
                fputcsv($output, ['Metric', 'Value']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                fputcsv($output, ['Total Alerts', number_format(count($data))]);
                fputcsv($output, ['Unique Pest Types', $stats['unique_pests']]);
                fputcsv($output, ['Average Confidence', $stats['avg_confidence'] . '%']);
                fputcsv($output, ['Resolution Rate', $stats['resolution_rate'] . '%']);
                fputcsv($output, []);
                
                // By severity
                fputcsv($output, ['Alerts by Severity']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                fputcsv($output, ['Severity', 'Count', 'Percentage']);
                
                foreach ($stats['by_severity'] as $severity => $count) {
                    $percentage = (count($data) > 0) ? round(($count / count($data)) * 100, 1) : 0;
                    fputcsv($output, [
                        ucfirst($severity),
                        $count,
                        $percentage . '%'
                    ]);
                }
                
                fputcsv($output, []);
                fputcsv($output, ['Alerts by Status']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                fputcsv($output, ['Status', 'Count', 'Percentage']);
                
                foreach ($stats['by_status'] as $status => $count) {
                    $percentage = (count($data) > 0) ? round(($count / count($data)) * 100, 1) : 0;
                    fputcsv($output, [
                        ucfirst($status),
                        $count,
                        $percentage . '%'
                    ]);
                }
                
                fputcsv($output, []);
                fputcsv($output, ['Top 5 Most Detected Pests']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                fputcsv($output, ['Rank', 'Pest Type', 'Detections']);
                
                $rank = 1;
                foreach ($stats['top_pests'] as $pest => $count) {
                    fputcsv($output, [$rank++, $pest, $count]);
                    if ($rank > 5) break;
                }
                
                fputcsv($output, []);
                fputcsv($output, ['Risk Assessment']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                fputcsv($output, [$stats['risk_assessment']]);
            }
            
            // Add Activity Logs section
            $activityLogs = $this->getActivityLogs($reportType, $startDate, $endDate);
            
            if (!empty($activityLogs)) {
                fputcsv($output, []);
                fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
                fputcsv($output, ['RECENT READINGS LOG']);
                fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
                fputcsv($output, []);
                fputcsv($output, ['Time', 'Type', 'Sensor/Pest', 'Value/Location', 'Status']);
                fputcsv($output, ['─────────────────────────────────────────────────────────────']);
                
                foreach ($activityLogs as $log) {
                    fputcsv($output, [
                        $log['time'],
                        $log['type_display'],
                        $log['sensor_name'],
                        $log['value_display'],
                        $log['status_icon'] . ' ' . ucfirst($log['status'])
                    ]);
                }
                
                fputcsv($output, []);
                fputcsv($output, ['Total Log Entries:', count($activityLogs)]);
            }
            
            fputcsv($output, []);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, ['EXPORT SUMMARY']);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, []);
            fputcsv($output, ['Total Records Exported:', number_format(count($data))]);
            fputcsv($output, ['Export Status:', 'Completed Successfully']);
            fputcsv($output, ['Export Format:', 'CSV (Comma-Separated Values)']);
            fputcsv($output, ['File Size:', 'Optimized for Excel/Spreadsheet Applications']);
            fputcsv($output, []);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);
            fputcsv($output, ['Sagay Eco-Farm IoT Agricultural System']);
            fputcsv($output, ['For support: admin@farmmonitoring.com']);
            fputcsv($output, ['═══════════════════════════════════════════════════════════════']);

            fclose($output);

            // Restore error reporting settings
            error_reporting($originalErrorReporting);
            ini_set('display_errors', $originalDisplayErrors);
        } catch (Exception $e) {
            // Restore error reporting settings
            if (isset($originalErrorReporting)) {
                error_reporting($originalErrorReporting);
                ini_set('display_errors', $originalDisplayErrors);
            }

            // Log the error
            error_log("CSV Export Error: " . $e->getMessage());

            // Clear any output that might have been sent
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Send error response
            header('Content-Type: text/plain');
            header('HTTP/1.1 500 Internal Server Error');
            echo "Export failed: " . $e->getMessage();
            exit;
        }
    }

    /**
     * Export data to PDF with enhanced formatting
     */
    public function exportToPDF($userId, $userRole, $reportType, $startDate, $endDate, $options = [])
    {
        // Validate request with security checks
        $errors = $this->validateExportRequest($userId, $userRole, 'pdf', $reportType, $startDate, $endDate);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode('; ', $errors));
        }

        // Get user-specific row limit
        $maxRows = ($userRole === 'admin') ? 50000 : $this->maxExportRows;

        // Get data
        $data = $this->getExportData($reportType, $startDate, $endDate, $maxRows);

        // Check if data exists
        if (empty($data)) {
            throw new Exception("No data found for the selected date range ({$startDate} to {$endDate}). Please check your date range and try again.");
        }

        // Check row limit
        if (count($data) > $maxRows) {
            throw new Exception("Export exceeds maximum allowed rows ({$maxRows}). Please narrow your date range.");
        }

        // Generate secure filename
        $filename = $this->generateSecureFilename($reportType, $startDate, $endDate, 'pdf');

        // Create PDF using simple HTML to PDF conversion (fallback method)
        $this->generateSimplePDF($reportType, $startDate, $endDate, $data, $filename);
    }

    /**
     * Generate print-friendly HTML that can be saved as PDF using browser's print function
     */
    private function generateSimplePDF($reportType, $startDate, $endDate, $data, $filename)
    {
        try {
            // Generate print-friendly HTML content
            $html = $this->generatePrintFriendlyHTML($reportType, $startDate, $endDate, $data);

            // Set headers for HTML display
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');

            echo $html;
        } catch (Exception $e) {
            // Fallback to existing HTML method
            error_log("Print-friendly HTML generation failed: " . $e->getMessage());
            $this->generateProfessionalHTML($reportType, $startDate, $endDate, $data);
        }
    }

    /**
     * Generate professional HTML content for PDF
     */
    private function generateProfessionalHTML($reportType, $startDate, $endDate, $data)
    {
        $title = ucfirst($reportType) . ' Report';
        $dateRange = date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));

        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 15px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 8px; }
        .title { font-size: 18px; font-weight: bold; color: #333; }
        .subtitle { font-size: 12px; color: #666; margin-top: 3px; }
        .meta { font-size: 10px; color: #888; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; font-size: 9px; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .summary { background-color: #f9f9f9; padding: 10px; margin: 15px 0; border-radius: 3px; }
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <div class='header'>
        <div class='title'>IoT Farm Monitoring System</div>
        <div class='subtitle'>{$title}</div>
        <div class='meta'>Date Range: {$dateRange} | Generated: " . date('Y-m-d H:i:s T') . "</div>
    </div>
    
    <div class='summary'>
        <strong>Report Summary:</strong><br>
        Total Records: " . count($data) . "<br>
        Report Type: {$title}<br>
        Export Format: PDF<br>
    </div>";

        if (!empty($data)) {
            $html .= "<table>";

            if ($reportType === 'sensor') {
                $html .= "<thead>
                    <tr>
                        <th>Date</th>
                        <th>Sensor Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Avg Value</th>
                        <th>Min Value</th>
                        <th>Max Value</th>
                        <th>Unit</th>
                        <th>Readings</th>
                    </tr>
                </thead>
                <tbody>";

                foreach ($data as $row) {
                    $html .= "<tr>
                        <td>" . htmlspecialchars($row['date']) . "</td>
                        <td>" . htmlspecialchars($row['sensor_name']) . "</td>
                        <td>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $row['sensor_type']))) . "</td>
                        <td>" . htmlspecialchars($row['location']) . "</td>
                        <td>" . number_format($row['avg_value'], 2) . "</td>
                        <td>" . number_format($row['min_value'], 2) . "</td>
                        <td>" . number_format($row['max_value'], 2) . "</td>
                        <td>" . htmlspecialchars($row['unit']) . "</td>
                        <td>" . $row['reading_count'] . "</td>
                    </tr>";
                }
            } else {
                $html .= "<thead>
                    <tr>
                        <th>Date</th>
                        <th>Pest Type</th>
                        <th>Location</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>";

                foreach ($data as $row) {
                    $html .= "<tr>
                        <td>" . htmlspecialchars($row['date']) . "</td>
                        <td>" . htmlspecialchars($row['pest_type']) . "</td>
                        <td>" . htmlspecialchars($row['location']) . "</td>
                        <td>" . htmlspecialchars(ucfirst($row['severity'])) . "</td>
                        <td>" . htmlspecialchars(ucfirst($row['status'])) . "</td>
                        <td>" . htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : '') . "</td>
                    </tr>";
                }
            }

            $html .= "</tbody></table>";
        } else {
            $html .= "<p style='text-align: center; color: #666; margin: 40px 0;'>No data available for the selected date range.</p>";
        }

        $html .= "
    <div class='footer'>
        <p>This report was generated by the IoT Farm Monitoring System</p>
        <p>For questions or support, contact: admin@farmmonitoring.com</p>
    </div>
</body>
</html>";

        return $html;
    }

    /**
     * Generate text-based report for PDF alternative
     */
    private function generateTextReport($reportType, $startDate, $endDate, $data)
    {
        $title = strtoupper(str_replace('_', ' ', $reportType)) . ' REPORT';
        $dateRange = date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));

        echo str_repeat('=', 80) . "\n";
        echo str_pad('IOT FARM MONITORING SYSTEM', 80, ' ', STR_PAD_BOTH) . "\n";
        echo str_pad($title, 80, ' ', STR_PAD_BOTH) . "\n";
        echo str_pad($dateRange, 80, ' ', STR_PAD_BOTH) . "\n";
        echo str_pad('Generated: ' . date('Y-m-d H:i:s T'), 80, ' ', STR_PAD_BOTH) . "\n";
        echo str_repeat('=', 80) . "\n\n";

        echo "REPORT SUMMARY:\n";
        echo "- Total Records: " . count($data) . "\n";
        echo "- Report Type: " . ucfirst($reportType) . "\n";
        echo "- Export Format: Text (PDF Alternative)\n";
        echo "- Date Range: {$dateRange}\n\n";

        if (!empty($data)) {
            echo str_repeat('-', 80) . "\n";
            echo "DATA RECORDS:\n";
            echo str_repeat('-', 80) . "\n\n";

            if ($reportType === 'sensor') {
                foreach ($data as $index => $row) {
                    echo "RECORD " . ($index + 1) . ":\n";
                    echo "  Date: " . $row['date'] . "\n";
                    echo "  Sensor: " . $row['sensor_name'] . "\n";
                    echo "  Type: " . ucfirst(str_replace('_', ' ', $row['sensor_type'])) . "\n";
                    echo "  Location: " . $row['location'] . "\n";
                    echo "  Average Value: " . number_format($row['avg_value'], 2) . $row['unit'] . "\n";
                    echo "  Min Value: " . number_format($row['min_value'], 2) . $row['unit'] . "\n";
                    echo "  Max Value: " . number_format($row['max_value'], 2) . $row['unit'] . "\n";
                    echo "  Reading Count: " . $row['reading_count'] . "\n";
                    echo "  Data Quality: " . $this->calculateDataQuality($row['reading_count']) . "\n";
                    echo "\n";
                }
            } else {
                foreach ($data as $index => $row) {
                    echo "RECORD " . ($index + 1) . ":\n";
                    echo "  Date: " . $row['date'] . "\n";
                    echo "  Pest Type: " . $row['pest_type'] . "\n";
                    echo "  Location: " . $row['location'] . "\n";
                    echo "  Severity: " . ucfirst($row['severity']) . "\n";
                    echo "  Status: " . ucfirst($row['status']) . "\n";
                    echo "  Risk Level: " . $this->calculateRiskLevel($row['severity'], $row['status']) . "\n";
                    echo "  Detection Time: " . date('H:i:s', strtotime($row['detected_at'])) . "\n";
                    echo "  Description: " . $row['description'] . "\n";
                    echo "\n";
                }
            }
        } else {
            echo "No data available for the selected date range.\n\n";
        }

        echo str_repeat('=', 80) . "\n";
        echo "This report was generated by the IoT Farm Monitoring System\n";
        echo "For questions or support, contact: admin@farmmonitoring.com\n";
        echo "Note: This is a text format. For proper PDF, install TCPDF library.\n";
        echo str_repeat('=', 80) . "\n";
    }

    /**
     * Generate text content for PDF
     */
    private function generatePDFTextContent($reportType, $startDate, $endDate, $data)
    {
        $content = "IoT Farm Monitoring System\n";
        $content .= strtoupper(str_replace('_', ' ', $reportType)) . " REPORT\n";
        $content .= "Date Range: " . date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate)) . "\n";
        $content .= "Generated: " . date('Y-m-d H:i:s T') . "\n\n";

        $content .= "SUMMARY:\n";
        $content .= "Total Records: " . count($data) . "\n";
        $content .= "Report Type: " . ucfirst($reportType) . "\n\n";

        if (!empty($data)) {
            $content .= "DATA RECORDS:\n\n";

            if ($reportType === 'sensor') {
                foreach ($data as $index => $row) {
                    $content .= "Record " . ($index + 1) . ":\n";
                    $content .= "Date: " . $row['date'] . "\n";
                    $content .= "Sensor: " . $row['sensor_name'] . "\n";
                    $content .= "Type: " . ucfirst(str_replace('_', ' ', $row['sensor_type'])) . "\n";
                    $content .= "Location: " . $row['location'] . "\n";
                    $content .= "Avg Value: " . number_format($row['avg_value'], 2) . $row['unit'] . "\n";
                    $content .= "Range: " . number_format($row['min_value'], 2) . " - " . number_format($row['max_value'], 2) . $row['unit'] . "\n";
                    $content .= "Readings: " . $row['reading_count'] . "\n\n";
                }
            } else {
                foreach ($data as $index => $row) {
                    $content .= "Record " . ($index + 1) . ":\n";
                    $content .= "Date: " . $row['date'] . "\n";
                    $content .= "Pest: " . $row['pest_type'] . "\n";
                    $content .= "Location: " . $row['location'] . "\n";
                    $content .= "Severity: " . ucfirst($row['severity']) . "\n";
                    $content .= "Status: " . ucfirst($row['status']) . "\n";
                    $content .= "Time: " . date('H:i:s', strtotime($row['detected_at'])) . "\n";
                    $content .= "Description: " . substr($row['description'], 0, 60) . "\n\n";
                }
            }
        } else {
            $content .= "No data available for the selected date range.\n\n";
        }

        $content .= "Generated by IoT Farm Monitoring System\n";
        $content .= "Contact: admin@farmmonitoring.com\n";

        return $content;
    }

    /**
     * Generate print-friendly HTML with professional styling and charts
     */
    private function generatePrintFriendlyHTML($reportType, $startDate, $endDate, $data)
    {
        $title = ucfirst($reportType) . ' Report';
        $dateRange = date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
        $summary = $this->generateSummaryStats($reportType, $data, $startDate, $endDate);
        $analysis = $this->generateAnalysis($reportType, $data);
        $chartData = $this->prepareChartData($reportType, $data);
        $activityLogs = $this->getActivityLogs($reportType, $startDate, $endDate);

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.4;
            color: #333;
            max-width: 210mm;
            margin: 0 auto;
            padding: 15px;
            background: white;
            font-size: 11px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 8px;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 3px;
        }
        
        .report-title {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            margin: 12px 0 6px;
        }
        
        .report-subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        
        .report-meta {
            font-size: 10px;
            color: #9ca3af;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .summary-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .summary-item {
            background: white;
            padding: 8px;
            border-radius: 4px;
            border-left: 3px solid #2563eb;
        }
        
        .summary-label {
            font-size: 9px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .summary-value {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
            margin-top: 3px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin: 25px 0 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            background: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .data-table th {
            background: linear-gradient(135deg, #374151, #4b5563);
            color: white;
            font-weight: 600;
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .data-table td {
            padding: 6px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 9px;
        }
        
        .data-table tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .data-table tr:hover {
            background: #f3f4f6;
        }
        
        .severity-critical { color: #dc2626; font-weight: 600; }
        .severity-high { color: #ea580c; font-weight: 600; }
        .severity-medium { color: #d97706; font-weight: 600; }
        .severity-low { color: #16a34a; font-weight: 600; }
        
        .status-new { color: #dc2626; }
        .status-acknowledged { color: #d97706; }
        .status-resolved { color: #16a34a; }
        
        .analysis-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .analysis-text {
            font-size: 11px;
            line-height: 1.5;
            color: #374151;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 8px;
        }
        
        .print-button {
            position: fixed;
            top: 15px;
            right: 15px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
            font-size: 10px;
        }
        
        .print-button:hover {
            background: #1d4ed8;
        }
        
        .note {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 10px;
            margin: 12px 0;
            font-size: 10px;
            color: #92400e;
        }
        
        .chart-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        .chart-title {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 12px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            margin-bottom: 15px;
        }
        
        .logs-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .log-entry {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 10px;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-timestamp {
            color: #6b7280;
            font-weight: 600;
        }
        
        .log-type {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .log-type-sensor {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .log-type-pest {
            background: #fef3c7;
            color: #92400e;
        }
        
        .log-type-system {
            background: #f3f4f6;
            color: #374151;
        }
        
        .log-type-user {
            background: #ddd6fe;
            color: #5b21b6;
        }
        
        @media print {
            .chart-container, .logs-container {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print to PDF</button>
    
    <div class="header">
        <div class="logo">🌱</div>
        <div class="company-name">IoT Farm Monitoring System</div>
        <div class="report-title">' . htmlspecialchars($title) . '</div>
        <div class="report-subtitle">' . htmlspecialchars($dateRange) . '</div>
        <div class="report-meta">Generated on ' . date('F j, Y \a\t g:i A') . '</div>
    </div>
    
    <div class="summary-box">
        <div class="summary-title">📊 Report Summary</div>
        <div class="summary-grid">';

        foreach ($summary as $label => $value) {
            $html .= '
            <div class="summary-item">
                <div class="summary-label">' . htmlspecialchars($label) . '</div>
                <div class="summary-value">' . htmlspecialchars($value) . '</div>
            </div>';
        }

        $html .= '
        </div>
    </div>';

        // Add charts section
        if (!empty($data) && !empty($chartData)) {
            $html .= '<div class="section-title">📊 Visual Analysis</div>';
            $html .= '<div class="chart-container">';
            $html .= '<div class="chart-title">Trend Analysis Chart</div>';
            $html .= '<div class="chart-wrapper">';
            $html .= '<canvas id="mainChart"></canvas>';
            $html .= '</div>';
            $html .= '</div>';
        }

        if (!empty($data)) {
            $html .= '<div class="section-title">📋 Data Records</div>';

            if (count($data) > 50) {
                $html .= '<div class="note">
                    <strong>Note:</strong> Showing first 50 records of ' . count($data) . ' total records. 
                    For complete data, please use the CSV export option.
                </div>';
            }

            $html .= '<table class="data-table">';

            if ($reportType === 'sensor') {
                $html .= '
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Sensor</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Avg</th>
                        <th>Range</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>';

                foreach (array_slice($data, 0, 50) as $row) {
                    $html .= '
                    <tr>
                        <td>' . date('M j', strtotime($row['date'])) . '</td>
                        <td>' . htmlspecialchars($row['sensor_name']) . '</td>
                        <td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $row['sensor_type']))) . '</td>
                        <td>' . htmlspecialchars($row['location']) . '</td>
                        <td><strong>' . number_format($row['avg_value'], 1) . $row['unit'] . '</strong></td>
                        <td>' . number_format($row['min_value'], 1) . ' - ' . number_format($row['max_value'], 1) . $row['unit'] . '</td>
                        <td>' . $row['reading_count'] . '</td>
                    </tr>';
                }
            } else {
                $html .= '
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pest</th>
                        <th>Location</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>';

                foreach (array_slice($data, 0, 50) as $row) {
                    $severityClass = 'severity-' . strtolower($row['severity']);
                    $statusClass = 'status-' . strtolower($row['status']);

                    $html .= '
                    <tr>
                        <td>' . date('M j', strtotime($row['date'])) . '</td>
                        <td>' . htmlspecialchars($row['pest_type']) . '</td>
                        <td>' . htmlspecialchars($row['location']) . '</td>
                        <td class="' . $severityClass . '">' . ucfirst($row['severity']) . '</td>
                        <td class="' . $statusClass . '">' . ucfirst($row['status']) . '</td>
                        <td>' . htmlspecialchars(substr($row['description'], 0, 60)) . (strlen($row['description']) > 60 ? '...' : '') . '</td>
                    </tr>';
                }
            }

            $html .= '</tbody></table>';
        } else {
            $html .= '<div class="section-title">📋 Data Records</div>
            <div class="note">No data available for the selected date range.</div>';
        }

        $html .= '
    <div class="section-title">📈 Analysis</div>
    <div class="analysis-box">
        <div class="analysis-text">' . nl2br(htmlspecialchars($analysis)) . '</div>
    </div>';

        // Add Activity Logs section
        if (!empty($activityLogs)) {
            $html .= '
    <div class="page-break"></div>
    <div class="section-title">📝 Recent Readings Log</div>
    <div class="logs-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Sensor</th>
                    <th>Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

            foreach ($activityLogs as $log) {
                $statusClass = $log['status'] === 'success' ? 'status-resolved' : 'severity-high';
                $statusIcon = $log['status'] === 'success' ? '✓' : ($log['status_icon'] ?? '⚠');
                
                $html .= '
                <tr>
                    <td>' . htmlspecialchars($log['time']) . '</td>
                    <td>' . htmlspecialchars($log['type_display']) . '</td>
                    <td>' . htmlspecialchars($log['sensor_name']) . '</td>
                    <td><strong>' . htmlspecialchars($log['value_display']) . '</strong></td>
                    <td class="' . $statusClass . '">' . $statusIcon . '</td>
                </tr>';
            }

            $html .= '
            </tbody>
        </table>
    </div>';
        }

        $html .= '
    
    <div class="footer">
        <p><strong>IoT Farm Monitoring System</strong></p>
        <p>This report was generated automatically. For support, contact: admin@farmmonitoring.com</p>
        <p>To save as PDF: Click the Print button above and select "Save as PDF" in your browser\'s print dialog.</p>
    </div>
    
    <script>
        // Wait for page to load
        window.addEventListener("load", function() {
            const chartData = ' . json_encode($chartData) . ';
            const reportType = "' . $reportType . '";
            
            if (chartData && chartData.labels && chartData.labels.length > 0) {
                const ctx = document.getElementById("mainChart");
                if (ctx) {
                    new Chart(ctx, {
                        type: reportType === "sensor" ? "line" : "bar",
                        data: {
                            labels: chartData.labels,
                            datasets: chartData.datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: "top",
                                    labels: {
                                        font: { size: 11 },
                                        boxWidth: 12
                                    }
                                },
                                title: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: reportType === "pest",
                                    ticks: {
                                        font: { size: 10 }
                                    },
                                    grid: {
                                        color: "#e5e7eb"
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: { size: 10 },
                                        maxRotation: 45,
                                        minRotation: 0
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            },
                            animation: {
                                duration: 0
                            }
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>';

        return $html;
    }
    
    /**
     * Prepare chart data for visualization
     */
    private function prepareChartData($reportType, $data)
    {
        if (empty($data)) {
            return null;
        }

        if ($reportType === 'sensor') {
            // Group by sensor type and date
            $grouped = [];
            foreach ($data as $row) {
                $type = $row['sensor_type'];
                $date = $row['date'];
                
                if (!isset($grouped[$type])) {
                    $grouped[$type] = [];
                }
                
                if (!isset($grouped[$type][$date])) {
                    $grouped[$type][$date] = [];
                }
                
                $grouped[$type][$date][] = $row['avg_value'];
            }

            // Get unique dates
            $allDates = [];
            foreach ($data as $row) {
                $allDates[$row['date']] = true;
            }
            $labels = array_keys($allDates);
            sort($labels);
            
            // Format labels
            $formattedLabels = array_map(function($date) {
                return date('M j', strtotime($date));
            }, $labels);

            // Create datasets
            $colors = [
                'temperature' => ['border' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.1)'],
                'humidity' => ['border' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)'],
                'soil_moisture' => ['border' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.1)']
            ];

            $datasets = [];
            foreach ($grouped as $type => $dateData) {
                $values = [];
                foreach ($labels as $date) {
                    if (isset($dateData[$date])) {
                        $values[] = round(array_sum($dateData[$date]) / count($dateData[$date]), 2);
                    } else {
                        $values[] = null;
                    }
                }

                $color = $colors[$type] ?? ['border' => '#6b7280', 'bg' => 'rgba(107, 114, 128, 0.1)'];
                
                $datasets[] = [
                    'label' => ucfirst(str_replace('_', ' ', $type)),
                    'data' => $values,
                    'borderColor' => $color['border'],
                    'backgroundColor' => $color['bg'],
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5
                ];
            }

            return [
                'labels' => $formattedLabels,
                'datasets' => $datasets
            ];
        } else {
            // Pest data - group by severity and date
            $grouped = [];
            $allDates = [];
            
            foreach ($data as $row) {
                $date = $row['date'];
                $severity = $row['severity'];
                
                $allDates[$date] = true;
                
                if (!isset($grouped[$date])) {
                    $grouped[$date] = [
                        'low' => 0,
                        'medium' => 0,
                        'high' => 0,
                        'critical' => 0
                    ];
                }
                
                $grouped[$date][$severity]++;
            }

            $labels = array_keys($allDates);
            sort($labels);
            
            // Format labels
            $formattedLabels = array_map(function($date) {
                return date('M j', strtotime($date));
            }, $labels);

            $severities = ['low', 'medium', 'high', 'critical'];
            $colors = [
                'low' => '#10b981',
                'medium' => '#f59e0b',
                'high' => '#f97316',
                'critical' => '#ef4444'
            ];

            $datasets = [];
            foreach ($severities as $severity) {
                $values = [];
                foreach ($labels as $date) {
                    $values[] = $grouped[$date][$severity] ?? 0;
                }

                $datasets[] = [
                    'label' => ucfirst($severity),
                    'data' => $values,
                    'backgroundColor' => $colors[$severity],
                    'borderColor' => $colors[$severity],
                    'borderWidth' => 1
                ];
            }

            return [
                'labels' => $formattedLabels,
                'datasets' => $datasets
            ];
        }
    }

    /**
     * Generate summary statistics for PDF
     */
    private function generateSummaryStats($reportType, $data, $startDate, $endDate)
    {
        $stats = [
            'Report Type' => ucfirst($reportType),
            'Date Range' => date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)),
            'Total Records' => count($data),
            'Generated' => date('M j, Y \a\t g:i A')
        ];

        if ($reportType === 'sensor' && !empty($data)) {
            $totalReadings = array_sum(array_column($data, 'reading_count'));
            $avgReadings = $totalReadings / count($data);
            $uniqueSensors = count(array_unique(array_column($data, 'sensor_name')));

            $stats['Unique Sensors'] = $uniqueSensors;
            $stats['Total Readings'] = number_format($totalReadings);
            $stats['Avg Readings/Day'] = number_format($avgReadings, 1);
        } elseif ($reportType === 'pest' && !empty($data)) {
            $severityCounts = array_count_values(array_column($data, 'severity'));
            $statusCounts = array_count_values(array_column($data, 'status'));

            $stats['Critical Alerts'] = $severityCounts['critical'] ?? 0;
            $stats['High Severity'] = $severityCounts['high'] ?? 0;
            $stats['Resolved'] = $statusCounts['resolved'] ?? 0;
            $stats['Active'] = ($statusCounts['new'] ?? 0) + ($statusCounts['acknowledged'] ?? 0);
        }

        return $stats;
    }

    /**
     * Generate analysis text for PDF
     */
    private function generateAnalysis($reportType, $data)
    {
        if (empty($data)) {
            return "No data available for analysis during the selected period. This could indicate system downtime, sensor maintenance, or lack of activity.";
        }

        if ($reportType === 'sensor') {
            $totalReadings = array_sum(array_column($data, 'reading_count'));
            $avgValue = array_sum(array_column($data, 'avg_value')) / count($data);
            $sensorTypes = array_count_values(array_column($data, 'sensor_type'));
            $mostCommonType = array_keys($sensorTypes, max($sensorTypes))[0];

            $analysis = "Sensor data analysis reveals {$totalReadings} total readings across " . count($data) . " sensor-day combinations. ";
            $analysis .= "The most active sensor type is " . ucfirst(str_replace('_', ' ', $mostCommonType)) . " with " . $sensorTypes[$mostCommonType] . " daily records. ";
            $analysis .= "Average sensor value across all readings is " . number_format($avgValue, 2) . ". ";

            // Data quality assessment
            $excellentCount = 0;
            foreach ($data as $row) {
                if ($row['reading_count'] >= 20) $excellentCount++;
            }
            $qualityPercent = ($excellentCount / count($data)) * 100;
            $analysis .= "Data quality is " . ($qualityPercent > 70 ? "excellent" : ($qualityPercent > 40 ? "good" : "needs improvement")) . " with {$qualityPercent}% of sensors providing 20+ daily readings.";
        } else {
            $severityCounts = array_count_values(array_column($data, 'severity'));
            $statusCounts = array_count_values(array_column($data, 'status'));
            $pestTypes = array_count_values(array_column($data, 'pest_type'));

            $criticalCount = $severityCounts['critical'] ?? 0;
            $resolvedCount = $statusCounts['resolved'] ?? 0;
            $resolutionRate = count($data) > 0 ? ($resolvedCount / count($data)) * 100 : 0;

            $analysis = "Pest monitoring analysis shows " . count($data) . " total alerts during the reporting period. ";
            $analysis .= "Critical alerts account for {$criticalCount}  (" . number_format(($criticalCount / count($data)) * 100, 1) . "%). ";
            $analysis .= "Resolution rate is " . number_format($resolutionRate, 1) . "% with {$resolvedCount} alerts successfully resolved. ";

            if (!empty($pestTypes)) {
                $mostCommonPest = array_keys($pestTypes, max($pestTypes))[0];
                $analysis .= "The most frequently detected pest is {$mostCommonPest} with " . $pestTypes[$mostCommonPest] . " occurrences.";
            }
        }

        return $analysis;
    }

    /**
     * Get export data based on report type
     */
    private function getExportData($reportType, $startDate, $endDate, $maxRows = null)
    {
        $maxRows = $maxRows ?? $this->maxExportRows;

        if ($reportType === 'sensor') {
            return $this->getSensorExportData($startDate, $endDate, $maxRows);
        } else {
            return $this->getPestExportData($startDate, $endDate, $maxRows);
        }
    }

    /**
     * Get sensor data for export
     */
    private function getSensorExportData($startDate, $endDate, $maxRows)
    {
        try {
            $pdo = getDatabaseConnection();
            // Query sensorreadings table - unpivot into separate rows per sensor type
            $stmt = $pdo->prepare("
                SELECT * FROM (
                    SELECT 
                        'Temperature Sensor' as sensor_name,
                        'temperature' as sensor_type,
                        'Farm Field' as location,
                        AVG(Temperature) as avg_value,
                        MIN(Temperature) as min_value,
                        MAX(Temperature) as max_value,
                        COUNT(*) as reading_count,
                        '°C' as unit,
                        DATE(ReadingTime) as date
                    FROM sensorreadings
                    WHERE DATE(ReadingTime) BETWEEN ? AND ?
                    GROUP BY DATE(ReadingTime)
                    UNION ALL
                    SELECT 
                        'Humidity Sensor' as sensor_name,
                        'humidity' as sensor_type,
                        'Farm Field' as location,
                        AVG(Humidity) as avg_value,
                        MIN(Humidity) as min_value,
                        MAX(Humidity) as max_value,
                        COUNT(*) as reading_count,
                        '%' as unit,
                        DATE(ReadingTime) as date
                    FROM sensorreadings
                    WHERE DATE(ReadingTime) BETWEEN ? AND ?
                    GROUP BY DATE(ReadingTime)
                    UNION ALL
                    SELECT 
                        'Soil Moisture Sensor' as sensor_name,
                        'soil_moisture' as sensor_type,
                        'Farm Field' as location,
                        AVG(SoilMoisture) as avg_value,
                        MIN(SoilMoisture) as min_value,
                        MAX(SoilMoisture) as max_value,
                        COUNT(*) as reading_count,
                        '%' as unit,
                        DATE(ReadingTime) as date
                    FROM sensorreadings
                    WHERE DATE(ReadingTime) BETWEEN ? AND ?
                    GROUP BY DATE(ReadingTime)
                ) combined
                ORDER BY date DESC, sensor_type
                LIMIT ?
            ");
            $stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $maxRows]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to get sensor export data: " . $e->getMessage());
            throw new Exception("Failed to retrieve sensor data for export.");
        }
    }

    /**
     * Get pest data for export with enhanced fields
     */
    private function getPestExportData($startDate, $endDate, $maxRows)
    {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("
                SELECT 
                    pa.pest_type,
                    pa.location,
                    pa.severity,
                    pa.status,
                    pa.confidence_score,
                    pa.description,
                    pa.suggested_actions,
                    pa.detected_at,
                    DATE(pa.detected_at) as date,
                    c.camera_name
                FROM pest_alerts pa
                LEFT JOIN cameras c ON pa.camera_id = c.id
                WHERE DATE(detected_at) BETWEEN ? AND ?
                ORDER BY detected_at DESC
                LIMIT ?
            ");
            $stmt->execute([$startDate, $endDate, $maxRows]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to get pest export data: " . $e->getMessage());
            throw new Exception("Failed to retrieve pest data for export.");
        }
    }

    /**
     * Calculate data quality based on reading count
     */
    private function calculateDataQuality($readingCount)
    {
        if ($readingCount >= 20) return 'Excellent';
        if ($readingCount >= 10) return 'Good';
        if ($readingCount >= 5) return 'Fair';
        return 'Poor';
    }

    /**
     * Calculate risk level based on severity and status
     */
    private function calculateRiskLevel($severity, $status)
    {
        if ($status === 'resolved') return 'Low';

        switch ($severity) {
            case 'critical':
                return 'Very High';
            case 'high':
                return 'High';
            case 'medium':
                return 'Medium';
            case 'low':
                return 'Low';
            default:
                return 'Unknown';
        }
    }

    /**
     * Calculate sensor statistics for CSV export
     */
    private function calculateSensorStatistics($data)
    {
        $stats = [
            'total_readings' => 0,
            'unique_sensors' => 0,
            'excellent_quality' => 0,
            'by_type' => [],
            'trend_analysis' => ''
        ];

        if (empty($data)) {
            return $stats;
        }

        // Calculate totals
        $totalReadings = array_sum(array_column($data, 'reading_count'));
        $uniqueSensors = count(array_unique(array_column($data, 'sensor_name')));
        
        // Calculate quality
        $excellentCount = 0;
        foreach ($data as $row) {
            if ($row['reading_count'] >= 20) {
                $excellentCount++;
            }
        }
        $excellentPercent = round(($excellentCount / count($data)) * 100, 1);

        // Group by type
        $byType = [];
        foreach ($data as $row) {
            $type = $row['sensor_type'];
            if (!isset($byType[$type])) {
                $byType[$type] = [
                    'count' => 0,
                    'values' => [],
                    'unit' => $row['unit']
                ];
            }
            $byType[$type]['count']++;
            $byType[$type]['values'][] = $row['avg_value'];
        }

        // Calculate stats by type
        foreach ($byType as $type => $typeData) {
            $stats['by_type'][$type] = [
                'count' => $typeData['count'],
                'avg' => array_sum($typeData['values']) / count($typeData['values']),
                'min' => min($typeData['values']),
                'max' => max($typeData['values']),
                'unit' => $typeData['unit']
            ];
        }

        // Trend analysis
        $trendText = "Data collection shows consistent monitoring across {$uniqueSensors} sensors. ";
        $trendText .= "Quality metrics indicate " . ($excellentPercent > 70 ? "excellent" : ($excellentPercent > 40 ? "good" : "needs improvement")) . " data reliability. ";
        
        $mostActiveType = array_keys($byType, max($byType))[0] ?? 'unknown';
        $trendText .= "Most active sensor type: " . ucfirst(str_replace('_', ' ', $mostActiveType)) . ".";

        $stats['total_readings'] = $totalReadings;
        $stats['unique_sensors'] = $uniqueSensors;
        $stats['excellent_quality'] = $excellentPercent;
        $stats['avg_readings_per_day'] = $totalReadings / count($data);
        $stats['trend_analysis'] = $trendText;

        return $stats;
    }

    /**
     * Calculate pest statistics for CSV export
     */
    private function calculatePestStatistics($data)
    {
        $stats = [
            'unique_pests' => 0,
            'avg_confidence' => 0,
            'resolution_rate' => 0,
            'by_severity' => [],
            'by_status' => [],
            'top_pests' => [],
            'risk_assessment' => ''
        ];

        if (empty($data)) {
            return $stats;
        }

        // Unique pests
        $uniquePests = count(array_unique(array_column($data, 'pest_type')));

        // Average confidence
        $confidenceScores = array_filter(array_column($data, 'confidence_score'));
        $avgConfidence = !empty($confidenceScores) ? round(array_sum($confidenceScores) / count($confidenceScores), 1) : 0;

        // Resolution rate
        $statusCounts = array_count_values(array_column($data, 'status'));
        $resolvedCount = $statusCounts['resolved'] ?? 0;
        $resolutionRate = round(($resolvedCount / count($data)) * 100, 1);

        // By severity
        $bySeverity = array_count_values(array_column($data, 'severity'));
        arsort($bySeverity);

        // By status
        $byStatus = array_count_values(array_column($data, 'status'));

        // Top pests
        $topPests = array_count_values(array_column($data, 'pest_type'));
        arsort($topPests);

        // Risk assessment
        $criticalCount = $bySeverity['critical'] ?? 0;
        $highCount = $bySeverity['high'] ?? 0;
        $unresolvedCount = ($statusCounts['new'] ?? 0) + ($statusCounts['acknowledged'] ?? 0);

        $riskText = "Analysis of {$uniquePests} unique pest types detected. ";
        
        if ($criticalCount > 0) {
            $riskText .= "CRITICAL: {$criticalCount} critical severity alerts require immediate attention. ";
        }
        
        if ($unresolvedCount > count($data) * 0.5) {
            $riskText .= "High number of unresolved alerts ({$unresolvedCount}) indicates need for increased intervention. ";
        } else {
            $riskText .= "Resolution rate of {$resolutionRate}% shows effective pest management. ";
        }

        $mostCommonPest = array_key_first($topPests);
        $riskText .= "Most frequent pest: {$mostCommonPest} with " . $topPests[$mostCommonPest] . " detections.";

        $stats['unique_pests'] = $uniquePests;
        $stats['avg_confidence'] = $avgConfidence;
        $stats['resolution_rate'] = $resolutionRate;
        $stats['by_severity'] = $bySeverity;
        $stats['by_status'] = $byStatus;
        $stats['top_pests'] = $topPests;
        $stats['risk_assessment'] = $riskText;

        return $stats;
    }

    /**
     * Get activity logs for the report period
     */
    private function getActivityLogs($reportType, $startDate, $endDate, $limit = 50)
    {
        try {
            $pdo = getDatabaseConnection();
            $logs = [];

            if ($reportType === 'sensor') {
                // Get sensor reading activities from sensorreadings table
                $stmt = $pdo->prepare("
                    SELECT * FROM (
                        SELECT 
                            ReadingTime as recorded_at,
                            'Temperature Sensor' as sensor_name,
                            'temperature' as sensor_type,
                            Temperature as value,
                            '°C' as unit,
                            20.00 as alert_threshold_min,
                            28.00 as alert_threshold_max
                        FROM sensorreadings
                        WHERE DATE(ReadingTime) BETWEEN ? AND ?
                        UNION ALL
                        SELECT 
                            ReadingTime as recorded_at,
                            'Humidity Sensor' as sensor_name,
                            'humidity' as sensor_type,
                            Humidity as value,
                            '%' as unit,
                            60.00 as alert_threshold_min,
                            80.00 as alert_threshold_max
                        FROM sensorreadings
                        WHERE DATE(ReadingTime) BETWEEN ? AND ?
                        UNION ALL
                        SELECT 
                            ReadingTime as recorded_at,
                            'Soil Moisture Sensor' as sensor_name,
                            'soil_moisture' as sensor_type,
                            SoilMoisture as value,
                            '%' as unit,
                            40.00 as alert_threshold_min,
                            60.00 as alert_threshold_max
                        FROM sensorreadings
                        WHERE DATE(ReadingTime) BETWEEN ? AND ?
                    ) combined
                    ORDER BY recorded_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $limit]);
                $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Format like sensors.php table
                $typeEmojis = [
                    'temperature' => '🌡️',
                    'humidity' => '💧',
                    'soil_moisture' => '🌱'
                ];

                foreach ($readings as $reading) {
                    $value = floatval($reading['value']);
                    $type = $reading['sensor_type'];
                    $minThreshold = $reading['alert_threshold_min'];
                    $maxThreshold = $reading['alert_threshold_max'];
                    
                    // Determine status
                    $isOptimal = false;
                    $statusIcon = '⚠';
                    
                    if ($minThreshold !== null && $maxThreshold !== null) {
                        $isOptimal = ($value >= $minThreshold && $value <= $maxThreshold);
                        if ($isOptimal) {
                            $statusIcon = '✓';
                        } else {
                            $statusIcon = ($value < $minThreshold) ? '↓' : '↑';
                        }
                    } else {
                        $isOptimal = true;
                        $statusIcon = '✓';
                    }
                    
                    $logs[] = [
                        'time' => date('M j, g:i A', strtotime($reading['recorded_at'])),
                        'type_display' => ($typeEmojis[$type] ?? '📊') . ' ' . ucfirst(str_replace('_', ' ', $type)),
                        'sensor_name' => $reading['sensor_name'],
                        'value_display' => number_format($value, 1) . $reading['unit'],
                        'status' => $isOptimal ? 'success' : 'warning',
                        'status_icon' => $statusIcon
                    ];
                }
            } else {
                // Get pest detection activities
                $stmt = $pdo->prepare("
                    SELECT 
                        pa.detected_at,
                        pa.pest_type,
                        pa.location,
                        pa.severity,
                        pa.status,
                        pa.confidence_score,
                        c.camera_name
                    FROM pest_alerts pa
                    LEFT JOIN cameras c ON pa.camera_id = c.id
                    WHERE DATE(pa.detected_at) BETWEEN ? AND ?
                    ORDER BY pa.detected_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$startDate, $endDate, $limit]);
                $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Format like pest detection table
                $severityEmojis = [
                    'low' => '🟢',
                    'medium' => '🟡',
                    'high' => '🟠',
                    'critical' => '🔴'
                ];

                foreach ($alerts as $alert) {
                    $severity = $alert['severity'];
                    $status = $alert['status'];
                    
                    // Determine status icon
                    $statusIcon = '⚠';
                    $statusValue = 'warning';
                    
                    if ($status === 'resolved') {
                        $statusIcon = '✓';
                        $statusValue = 'success';
                    } elseif ($severity === 'critical' || $severity === 'high') {
                        $statusIcon = '⚠';
                        $statusValue = 'error';
                    }
                    
                    $confidenceText = $alert['confidence_score'] ? ' (' . round($alert['confidence_score'], 1) . '%)' : '';
                    
                    $logs[] = [
                        'time' => date('M j, g:i A', strtotime($alert['detected_at'])),
                        'type_display' => ($severityEmojis[$severity] ?? '🐛') . ' ' . ucfirst($severity),
                        'sensor_name' => $alert['pest_type'],
                        'value_display' => $alert['location'] . $confidenceText,
                        'status' => $statusValue,
                        'status_icon' => $statusIcon
                    ];
                }
            }

            return $logs;
        } catch (Exception $e) {
            error_log("Failed to get activity logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log export activity for security auditing
     */
    public function logExportActivity($userId, $reportType, $format, $startDate, $endDate, $success = true)
    {
        $logMessage = sprintf(
            "[EXPORT] User: %s, Type: %s, Format: %s, Range: %s to %s, Success: %s, IP: %s, Time: %s",
            $userId,
            $reportType,
            $format,
            $startDate,
            $endDate,
            $success ? 'YES' : 'NO',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            date('Y-m-d H:i:s')
        );

        error_log($logMessage);
    }
}

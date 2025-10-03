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

            // Add metadata header
            fputcsv($output, ['# IoT Farm Monitoring System Export']);
            fputcsv($output, ['# Report Type: ' . ucfirst($reportType)]);
            fputcsv($output, ['# Date Range: ' . $startDate . ' to ' . $endDate]);
            fputcsv($output, ['# Generated: ' . date('Y-m-d H:i:s T')]);
            fputcsv($output, ['# Total Records: ' . count($data)]);
            fputcsv($output, []); // Empty line

            if ($reportType === 'sensor') {
                // Sensor data headers
                $headers = [
                    'Date',
                    'Sensor Name',
                    'Sensor Type',
                    'Location',
                    'Average Value',
                    'Minimum Value',
                    'Maximum Value',
                    'Unit',
                    'Reading Count',
                    'Data Quality'
                ];
                fputcsv($output, $headers);

                foreach ($data as $row) {
                    $csvRow = [
                        $row['date'],
                        $row['sensor_name'],
                        ucfirst(str_replace('_', ' ', $row['sensor_type'])),
                        $row['location'],
                        number_format($row['avg_value'], 2),
                        number_format($row['min_value'], 2),
                        number_format($row['max_value'], 2),
                        $row['unit'],
                        $row['reading_count'],
                        $this->calculateDataQuality($row['reading_count'])
                    ];
                    fputcsv($output, $csvRow);
                }
            } else {
                // Pest data headers
                $headers = [
                    'Date',
                    'Pest Type',
                    'Location',
                    'Severity',
                    'Status',
                    'Description',
                    'Detection Time',
                    'Risk Level'
                ];
                fputcsv($output, $headers);

                foreach ($data as $row) {
                    $csvRow = [
                        $row['date'],
                        $row['pest_type'],
                        $row['location'],
                        ucfirst($row['severity']),
                        ucfirst($row['status']),
                        $row['description'],
                        date('H:i:s', strtotime($row['detected_at'])),
                        $this->calculateRiskLevel($row['severity'], $row['status'])
                    ];
                    fputcsv($output, $csvRow);
                }
            }

            // Add summary footer
            fputcsv($output, []);
            fputcsv($output, ['# Export completed successfully']);
            fputcsv($output, ['# For support, contact: admin@farmmonitoring.com']);

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
     * Generate print-friendly HTML with professional styling
     */
    private function generatePrintFriendlyHTML($reportType, $startDate, $endDate, $data)
    {
        $title = ucfirst($reportType) . ' Report';
        $dateRange = date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
        $summary = $this->generateSummaryStats($reportType, $data, $startDate, $endDate);
        $analysis = $this->generateAnalysis($reportType, $data);

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
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
    </div>
    
    <div class="footer">
        <p><strong>IoT Farm Monitoring System</strong></p>
        <p>This report was generated automatically. For support, contact: admin@farmmonitoring.com</p>
        <p>To save as PDF: Click the Print button above and select "Save as PDF" in your browser\'s print dialog.</p>
    </div>
</body>
</html>';

        return $html;
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
            $analysis .= "Critical alerts account for {$criticalCount} incidents (" . number_format(($criticalCount / count($data)) * 100, 1) . "%). ";
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
            $stmt = $pdo->prepare("
                SELECT 
                    s.sensor_name,
                    s.sensor_type,
                    s.location,
                    AVG(sr.value) as avg_value,
                    MIN(sr.value) as min_value,
                    MAX(sr.value) as max_value,
                    COUNT(sr.id) as reading_count,
                    sr.unit,
                    DATE(sr.recorded_at) as date
                FROM sensors s
                JOIN sensor_readings sr ON s.id = sr.sensor_id
                WHERE DATE(sr.recorded_at) BETWEEN ? AND ?
                GROUP BY s.id, DATE(sr.recorded_at)
                ORDER BY sr.recorded_at DESC, s.sensor_type, s.sensor_name
                LIMIT ?
            ");
            $stmt->execute([$startDate, $endDate, $maxRows]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to get sensor export data: " . $e->getMessage());
            throw new Exception("Failed to retrieve sensor data for export.");
        }
    }

    /**
     * Get pest data for export
     */
    private function getPestExportData($startDate, $endDate, $maxRows)
    {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("
                SELECT 
                    pest_type,
                    location,
                    severity,
                    status,
                    description,
                    detected_at,
                    DATE(detected_at) as date
                FROM pest_alerts
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

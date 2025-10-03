# Export Functionality Documentation

## Overview

The IoT Farm Monitoring System includes comprehensive export functionality for reports with enhanced security measures, customization options, and role-based access control.

## Features

### Supported Export Formats

1. **CSV Export**
   - Excel-compatible format with UTF-8 BOM
   - Includes metadata headers and summary information
   - Enhanced data quality indicators
   - Risk level calculations for pest data

2. **PDF Export**
   - Formatted HTML-based PDF generation
   - Professional report layout with headers and footers
   - Summary statistics and data tables
   - Ready for integration with TCPDF or mPDF libraries

### Security Features

#### Role-Based Permissions
- **Admin**: Full access to CSV and PDF exports, up to 50,000 rows
- **Student**: CSV export only, limited to 10,000 rows
- **Farmer**: CSV and PDF exports, limited to 10,000 rows

#### Security Measures
- Input validation and sanitization
- Rate limiting (50 exports per user per hour)
- Maximum date range limit (365 days)
- Row count limits based on user role
- Export activity logging for audit trails
- Secure filename generation
- SQL injection prevention

#### Data Protection
- Parameterized database queries
- Output escaping for HTML content
- File path traversal protection
- Memory-efficient streaming for large datasets

## Usage

### Basic Export

```php
// Initialize export handler
$exportHandler = new ExportHandler();

// Export to CSV
$exportHandler->exportToCSV($userId, $userRole, $reportType, $startDate, $endDate);

// Export to PDF
$exportHandler->exportToPDF($userId, $userRole, $reportType, $startDate, $endDate);
```

### URL Parameters

```
reports.php?export=csv&report_type=sensor&start_date=2025-09-01&end_date=2025-09-30
reports.php?export=pdf&report_type=pest&start_date=2025-09-01&end_date=2025-09-30
```

### Advanced Options

The export modal provides additional customization:
- Include/exclude metadata
- Chart inclusion for PDF exports
- Format-specific options

## Configuration

### Security Settings (`includes/export-config.php`)

```php
// Maximum rows per export
define('EXPORT_MAX_ROWS', 10000);

// Maximum date range in days
define('EXPORT_MAX_DATE_RANGE_DAYS', 365);

// Rate limiting (exports per hour)
define('EXPORT_RATE_LIMIT', 50);

// Role-based permissions
define('EXPORT_PERMISSIONS', [
    'admin' => ['csv', 'pdf', 'unlimited_rows'],
    'student' => ['csv'],
    'farmer' => ['csv', 'pdf']
]);
```

### File Structure

```
includes/
├── export-handler.php      # Main export functionality
├── export-config.php       # Security configuration
logs/
├── export_activity.log     # Export audit log
docs/
├── EXPORT_FUNCTIONALITY.md # This documentation
```

## Data Formats

### CSV Export Structure

#### Sensor Data
```csv
# IoT Farm Monitoring System Export
# Report Type: Sensor
# Date Range: 2025-09-01 to 2025-09-30
# Generated: 2025-10-03 14:30:00 UTC
# Total Records: 150

Date,Sensor Name,Sensor Type,Location,Average Value,Minimum Value,Maximum Value,Unit,Reading Count,Data Quality
2025-09-30,Temp Sensor 1,Temperature,Field A,23.45,18.20,28.90,°C,24,Excellent
```

#### Pest Data
```csv
# IoT Farm Monitoring System Export
# Report Type: Pest
# Date Range: 2025-09-01 to 2025-09-30
# Generated: 2025-10-03 14:30:00 UTC
# Total Records: 12

Date,Pest Type,Location,Severity,Status,Description,Detection Time,Risk Level
2025-09-30,Aphids,Field B,Medium,New,Small aphid colony detected,14:25:30,Medium
```

### PDF Export Structure

- **Header**: System title, report type, date range
- **Summary**: Record count, report metadata
- **Data Table**: Formatted data with proper styling
- **Footer**: Contact information and generation timestamp

## Error Handling

### Common Errors

1. **Permission Denied**
   ```
   You don't have permission to export in PDF format.
   ```

2. **Rate Limit Exceeded**
   ```
   Export rate limit exceeded. Please try again later.
   ```

3. **Data Limit Exceeded**
   ```
   Export exceeds maximum allowed rows (10000). Please narrow your date range.
   ```

4. **Invalid Parameters**
   ```
   Invalid date format. Use YYYY-MM-DD format.
   ```

### Error Logging

All export attempts and errors are logged to `logs/export_activity.log`:

```
[EXPORT] User: 1, Type: sensor, Format: csv, Range: 2025-09-01 to 2025-09-30, Success: YES, IP: 192.168.1.100, Time: 2025-10-03 14:30:00
```

## Testing

Run the test script to verify functionality:

```bash
php test_export.php
```

The test script validates:
- Permission checking
- Security validation
- Role-based access control
- Configuration functions

## Future Enhancements

### Planned Features

1. **TCPDF Integration**
   - Professional PDF generation
   - Chart embedding
   - Custom styling options

2. **Email Export**
   - Scheduled report delivery
   - Email notifications
   - Attachment security

3. **Advanced Filtering**
   - Custom field selection
   - Data aggregation options
   - Template-based exports

4. **API Integration**
   - RESTful export endpoints
   - Webhook notifications
   - Third-party integrations

### Performance Optimizations

1. **Caching**
   - Query result caching
   - Template caching
   - Static file serving

2. **Streaming**
   - Large dataset streaming
   - Progressive download
   - Memory optimization

## Support

For technical support or questions about the export functionality:

- Check the error logs in `logs/export_activity.log`
- Review the test script output
- Verify user permissions and role assignments
- Contact: admin@farmmonitoring.com

## Security Considerations

### Best Practices

1. **Regular Audits**
   - Review export logs regularly
   - Monitor for unusual activity
   - Check rate limit violations

2. **Access Control**
   - Regularly review user roles
   - Update permissions as needed
   - Implement principle of least privilege

3. **Data Protection**
   - Ensure HTTPS in production
   - Regular security updates
   - Backup export logs

### Compliance

The export functionality is designed to support:
- Data privacy regulations
- Audit requirements
- Security compliance standards
- Access control policies
-- ============================================================================
-- Sensor Alerts Table
-- Tracks sensor threshold violations - follows pest_alerts pattern
-- ============================================================================

USE farm_database;

-- Create sensor_alerts table (mirrors pest_alerts structure)
CREATE TABLE IF NOT EXISTS sensor_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    sensor_type VARCHAR(50) NOT NULL,
    sensor_value DECIMAL(10,2) NOT NULL,
    threshold_min DECIMAL(10,2) NULL,
    threshold_max DECIMAL(10,2) NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('new', 'acknowledged', 'resolved') DEFAULT 'new',
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    read_by INT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(id) ON DELETE CASCADE,
    FOREIGN KEY (read_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sensor_id (sensor_id),
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    INDEX idx_is_read (is_read),
    INDEX idx_detected_at (detected_at)
) COMMENT='Sensor threshold alerts';

SELECT 'Sensor alerts table created successfully!' as status;

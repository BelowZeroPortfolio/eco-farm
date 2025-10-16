-- IoT Farm Monitoring System Database Schema
-- This file contains the complete database schema for the farm monitoring system

-- Create database
CREATE DATABASE IF NOT EXISTS farm_database;
USE farm_database;

-- Users table (extending existing structure)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'student', 'farmer') DEFAULT 'student',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sensors table for IoT device information
CREATE TABLE IF NOT EXISTS sensors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_name VARCHAR(100) NOT NULL,
    sensor_type ENUM('temperature', 'humidity', 'soil_moisture') NOT NULL,
    location VARCHAR(100) NOT NULL,
    arduino_pin INT NOT NULL,
    sensor_id VARCHAR(50),
    calibration_offset DECIMAL(10,4) DEFAULT 0.0000,
    alert_threshold_min DECIMAL(10,2) NULL,
    alert_threshold_max DECIMAL(10,2) NULL,
    status ENUM('online', 'offline', 'error') DEFAULT 'offline',
    last_reading_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_arduino_pin (arduino_pin),
    INDEX idx_sensor_type (sensor_type),
    INDEX idx_status (status),
    UNIQUE KEY unique_active_pin (arduino_pin, status)
);

-- Sensor readings table for historical data
CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(id) ON DELETE CASCADE,
    INDEX idx_sensor_recorded (sensor_id, recorded_at),
    INDEX idx_recorded_at (recorded_at)
);

-- Cameras table for pest detection cameras
CREATE TABLE IF NOT EXISTS cameras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camera_name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    port INT DEFAULT 80,
    username VARCHAR(50),
    password VARCHAR(255),
    camera_type ENUM('ip_camera', 'usb_camera', 'rtsp_stream') DEFAULT 'ip_camera',
    resolution VARCHAR(20) DEFAULT '1920x1080',
    fps INT DEFAULT 30,
    detection_enabled BOOLEAN DEFAULT TRUE,
    detection_sensitivity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    detection_zones JSON,
    status ENUM('online', 'offline', 'error') DEFAULT 'offline',
    last_detection TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_location (location)
);

-- Pest alerts table for pest detection events (updated with camera reference)
CREATE TABLE IF NOT EXISTS pest_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camera_id INT,
    pest_type VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('new', 'acknowledged', 'resolved') DEFAULT 'new',
    confidence_score DECIMAL(5,2),
    image_path VARCHAR(255),
    description TEXT,
    suggested_actions TEXT,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(id) ON DELETE SET NULL,
    INDEX idx_camera_id (camera_id),
    INDEX idx_severity_status (severity, status),
    INDEX idx_detected_at (detected_at)
);

-- User settings table for personalization
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_setting (user_id, setting_key)
);
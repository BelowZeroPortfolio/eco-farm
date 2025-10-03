# Camera-Based Pest Detection System

## Overview

This IoT Farm Monitoring System now includes a comprehensive camera-based pest detection system that uses AI-powered image analysis to identify pests in real-time. The system is based on the capstone project specifications and integrates YOLOv8 AI model for accurate pest detection.

## System Architecture

### Hardware Components
- **Camera Modules**: Logitech 720p HD cameras (as specified in the capstone project)
- **Microcontroller**: Arduino Mega R3 for sensor integration
- **Network**: Wi-Fi connectivity for data transmission
- **Power Supply**: 12V/2A adapter with backup power

### Software Components
- **AI Model**: YOLOv8 for real-time pest detection and classification
- **Backend**: PHP with MySQL database for data management
- **Frontend**: Responsive web interface with real-time monitoring
- **Cloud Integration**: Microsoft Azure for data backup and historical tracking

## Camera Management Features

### 1. Camera Configuration
- **Multiple Camera Support**: Manage up to 6+ cameras across different farm locations
- **Camera Types**: Support for IP cameras, USB cameras, and RTSP streams
- **Network Settings**: Configure IP addresses, ports, and authentication
- **Video Settings**: Adjustable resolution (VGA to 2K) and frame rates (15-60 FPS)

### 2. AI Detection Settings
- **Detection Sensitivity**: Three levels (Low: 90%+, Medium: 80%+, High: 70%+ confidence)
- **Detection Zones**: Configurable areas within camera view for focused monitoring
- **Pest Classification**: Trained to detect common agricultural pests:
  - Aphids
  - Caterpillars
  - Whiteflies
  - Spider Mites
  - Thrips
  - Beetles
  - Fungal Infections

### 3. Real-time Monitoring
- **Live Feed**: View real-time camera streams
- **Status Monitoring**: Track camera online/offline status
- **Connection Testing**: Verify camera connectivity and settings
- **Image Capture**: Manual test image capture with AI analysis

## Database Schema

### Cameras Table
```sql
CREATE TABLE cameras (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Enhanced Pest Alerts Table
```sql
CREATE TABLE pest_alerts (
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
    FOREIGN KEY (camera_id) REFERENCES cameras(id) ON DELETE SET NULL
);
```

## Implementation Details

### 1. Camera Network Setup
- **IP Range**: 192.168.1.101-106 for camera devices
- **Authentication**: Username/password authentication for IP cameras
- **Network Protocol**: HTTP/RTSP for video streaming
- **Backup Power**: UPS system for continuous operation

### 2. AI Model Integration
- **Model**: YOLOv8 trained on agricultural pest dataset
- **Processing**: Real-time image analysis with confidence scoring
- **Output**: Bounding boxes, pest classification, and confidence levels
- **Storage**: Detection images saved with metadata

### 3. Alert System
- **Real-time Notifications**: Immediate alerts for new pest detections
- **Severity Levels**: Automatic classification based on pest type and population
- **Action Recommendations**: IPM-based suggestions for pest control
- **Historical Tracking**: Complete audit trail of all detections

## User Interface Features

### 1. Camera Dashboard
- **Grid View**: Visual overview of all cameras with status indicators
- **Quick Stats**: Total cameras, online status, AI-enabled count
- **Live Preview**: Real-time camera feeds with detection overlays
- **Status Monitoring**: Connection status and last detection times

### 2. Camera Settings Modal
- **Configuration Form**: Complete camera setup interface
- **Connection Testing**: Verify camera connectivity before saving
- **Live Preview**: Test camera feed and AI detection
- **Bulk Operations**: Configure multiple cameras efficiently

### 3. Pest Detection Integration
- **Enhanced Alerts**: Show camera source and detection images
- **Confidence Scores**: Display AI model confidence levels
- **Image Gallery**: View captured detection images with metadata
- **Camera Filtering**: Filter alerts by specific cameras

## Security Features

### 1. Access Control
- **Role-based Access**: Admin and farmer roles can manage cameras
- **Authentication**: Secure camera credentials storage
- **Session Management**: Secure user sessions with timeout

### 2. Data Protection
- **Password Hashing**: Secure storage of camera passwords
- **Input Validation**: Comprehensive input sanitization
- **SQL Injection Prevention**: Prepared statements for all queries

## Performance Optimization

### 1. Image Processing
- **Efficient Compression**: Optimized image storage and transmission
- **Batch Processing**: Handle multiple camera feeds simultaneously
- **Caching**: Cache frequently accessed camera data

### 2. Network Optimization
- **Bandwidth Management**: Adaptive streaming based on network conditions
- **Connection Pooling**: Efficient camera connection management
- **Error Recovery**: Automatic reconnection for failed cameras

## Maintenance and Monitoring

### 1. System Health
- **Camera Status Monitoring**: Real-time status tracking
- **Performance Metrics**: FPS, detection accuracy, uptime statistics
- **Error Logging**: Comprehensive error tracking and reporting

### 2. AI Model Management
- **Model Updates**: Support for AI model retraining and updates
- **Performance Tuning**: Adjustable detection sensitivity
- **Dataset Management**: Continuous learning from new detections

## Future Enhancements

### 1. Advanced Features
- **Motion Detection**: Trigger recording only when motion is detected
- **Time-lapse**: Create time-lapse videos of crop growth
- **Weather Integration**: Correlate detections with weather conditions

### 2. Mobile Integration
- **Mobile App**: Dedicated mobile application for camera management
- **Push Notifications**: Real-time mobile alerts for critical detections
- **Offline Mode**: Local processing when internet is unavailable

### 3. Analytics
- **Trend Analysis**: Long-term pest population trends
- **Predictive Modeling**: Forecast pest outbreaks based on historical data
- **ROI Calculation**: Measure system effectiveness and cost savings

## Technical Specifications

### Minimum System Requirements
- **Server**: Dual-core CPU, 4GB RAM, 100GB storage
- **Network**: Stable internet connection (minimum 10 Mbps)
- **Cameras**: HD resolution (720p minimum), network connectivity
- **Power**: Uninterrupted power supply for 24/7 operation

### Supported Camera Models
- **IP Cameras**: Any ONVIF-compliant IP camera
- **USB Cameras**: Standard UVC-compatible cameras
- **RTSP Streams**: Network video recorders and streaming devices

## Installation and Setup

1. **Database Setup**: Run the updated schema.sql and sample_data.sql
2. **Camera Network**: Configure cameras on the local network
3. **System Configuration**: Set up camera credentials and settings
4. **AI Model**: Deploy YOLOv8 model for pest detection
5. **Testing**: Verify all cameras and detection functionality

## Support and Documentation

For technical support and detailed documentation, refer to:
- System Administrator Guide
- Camera Configuration Manual
- AI Model Training Documentation
- Troubleshooting Guide

---

This camera-based pest detection system represents a significant advancement in precision agriculture, combining IoT sensors with AI-powered computer vision to provide farmers with real-time, actionable insights for effective pest management.
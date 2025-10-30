# IoT Farm Monitoring System - Technical Documentation

## 📋 Table of Contents
1. [Overview](#overview)
2. [Tech Stack](#tech-stack)
3. [System Architecture](#system-architecture)
4. [Installation & Setup](#installation--setup)
5. [How It Works](#how-it-works)
6. [Key Features](#key-features)
7. [File Structure](#file-structure)
8. [Database Schema](#database-schema)
9. [API Endpoints](#api-endpoints)
10. [Development Workflow](#development-workflow)

---

## 🌟 Overview

The **IoT Farm Monitoring System** is a comprehensive smart agriculture solution that combines real-time sensor monitoring, AI-powered pest detection, and data analytics to help farmers optimize their agricultural operations.

### Core Capabilities:
- **Real-time Sensor Monitoring**: Temperature, humidity, and soil moisture tracking via Arduino
- **AI Pest Detection**: YOLOv8-powered computer vision for identifying 102+ pest types
- **Data Analytics**: Historical data visualization and trend analysis
- **Multi-user System**: Role-based access (Admin, Farmer, Student)
- **Automated Alerts**: Real-time notifications for critical conditions
- **Report Generation**: PDF export capabilities for data analysis

---

## 🛠️ Tech Stack

### Backend Technologies

#### 1. **PHP 7.4+**
- **Purpose**: Server-side application logic and web interface
- **Key Libraries**:
  - `TCPDF` (via Composer) - PDF generation for reports
  - Custom MVC-like structure with logic separation
- **Configuration**: `.env` file for environment variables

#### 2. **Python 3.8+**
- **Purpose**: IoT bridge services and AI processing
- **Key Libraries**:
  ```
  flask          - REST API framework for services
  ultralytics    - YOLOv8 model for pest detection
  pillow         - Image processing
  opencv-python  - Computer vision operations
  pyserial       - Arduino serial communication
  ```

#### 3. **MySQL/MariaDB**
- **Purpose**: Primary data storage
- **Database**: `farm_database`
- **Key Tables**: users, sensors, sensor_readings, pest_alerts, pest_config, cameras

### Frontend Technologies

#### 1. **Tailwind CSS**
- **Purpose**: Utility-first CSS framework
- **Features**: Dark mode support, responsive design, custom design system
- **Configuration**: Inline via CDN with custom theme extensions

#### 2. **JavaScript (Vanilla)**
- **Purpose**: Client-side interactivity and real-time updates
- **Key Features**:
  - AJAX polling for live sensor data
  - Chart.js integration for data visualization
  - Theme switching (light/dark mode)
  - Real-time notifications

#### 3. **Chart.js**
- **Purpose**: Data visualization and analytics charts
- **Usage**: Sensor trends, historical data, analytics dashboards

### IoT & Hardware

#### 1. **Arduino Mega 2560**
- **Purpose**: Physical sensor interface
- **Framework**: PlatformIO with Arduino framework
- **Sensors**:
  - DHT22 (Temperature & Humidity)
  - Soil Moisture Sensor (Analog)
- **Communication**: Serial (9600 baud) → Python bridge

#### 2. **PlatformIO**
- **Purpose**: Arduino development and deployment
- **Configuration**: `platformio.ini`
- **Libraries**: DHT sensor library, Adafruit Unified Sensor

### AI & Machine Learning

#### 1. **YOLOv8 (Ultralytics)**
- **Purpose**: Real-time pest detection
- **Model**: Custom trained `best.pt` (102 pest classes)
- **Input**: Image uploads from users or cameras
- **Output**: Bounding boxes, confidence scores, pest classifications

### Development Tools

#### 1. **Composer**
- **Purpose**: PHP dependency management
- **Dependencies**: TCPDF for PDF generation

#### 2. **Git**
- **Purpose**: Version control
- **Configuration**: `.gitignore` for excluding sensitive files

---

## 🏗️ System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     USER INTERFACE (Browser)                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │Dashboard │  │ Sensors  │  │   Pest   │  │ Reports  │   │
│  │          │  │          │  │Detection │  │          │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↕ HTTP/AJAX
┌─────────────────────────────────────────────────────────────┐
│                    WEB SERVER (Apache/XAMPP)                 │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              PHP Application Layer                    │   │
│  │  • Authentication & Authorization                     │   │
│  │  • Business Logic (MVC Pattern)                       │   │
│  │  • Database Operations (PDO)                          │   │
│  │  • API Integration                                    │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         ↕                    ↕                    ↕
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   MySQL DB   │    │Arduino Bridge│    │ YOLO Service │
│              │    │  (Flask API) │    │  (Flask API) │
│ • Users      │    │              │    │              │
│ • Sensors    │    │ Port: 5000   │    │ Port: 5000   │
│ • Readings   │    │              │    │              │
│ • Pests      │    └──────────────┘    └──────────────┘
└──────────────┘           ↕                    ↕
                  ┌──────────────┐    ┌──────────────┐
                  │Arduino Mega  │    │  YOLOv8      │
                  │   2560       │    │  Model       │
                  │              │    │  (best.pt)   │
                  │ • DHT22      │    │              │
                  │ • Soil       │    │ 102 Classes  │
                  └──────────────┘    └──────────────┘
```

### Data Flow

#### 1. **Sensor Data Flow**
```
Arduino Sensors → Serial (9600 baud) → Python Bridge (Flask) 
    → REST API → PHP Application → MySQL Database 
    → Dashboard Display (Real-time via AJAX)
```

#### 2. **Pest Detection Flow**
```
User Upload Image → PHP Handler → Python YOLO Service (Flask)
    → YOLOv8 Inference → Detection Results + Annotated Image
    → Store in Database → Display Results + Recommendations
```

#### 3. **Authentication Flow**
```
Login Form → PHP Logic → Password Verification (bcrypt)
    → Session Creation → Role-based Access Control
    → Dashboard Redirect
```

---

## 📦 Installation & Setup

### Prerequisites

1. **XAMPP** (Apache + MySQL + PHP)
   - Download: https://www.apachefriends.org/
   - Start Apache and MySQL services

2. **Python 3.8+**
   - Download: https://www.python.org/downloads/
   - ✅ Check "Add Python to PATH" during installation

3. **Composer** (PHP Package Manager)
   - Download: https://getcomposer.org/download/

4. **Arduino IDE or PlatformIO** (for Arduino programming)
   - PlatformIO: https://platformio.org/

### Step-by-Step Installation

#### 1. Clone/Download Project
```bash
# Place project in XAMPP htdocs folder
cd C:\xampp\htdocs\
# Extract or clone project to 'eco-farm' folder
```

#### 2. Install PHP Dependencies
```bash
cd eco-farm
composer install
```

#### 3. Install Python Dependencies
```bash
pip install flask ultralytics pillow opencv-python pyserial
```

#### 4. Database Setup
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database: `farm_database`
3. Import schema:
   ```sql
   -- Import database/schema.sql
   -- Optionally import database/sample_data.sql
   ```

#### 5. Environment Configuration
```bash
# Copy .env.example to .env
copy .env.example .env

# Edit .env and configure:
# - Database credentials
# - Arduino port (COM3 for Windows, /dev/ttyUSB0 for Linux)
# - API keys (OpenWeather, EmailJS)
```

#### 6. Arduino Setup (Optional - for real sensors)
```bash
# If using PlatformIO
pio run --target upload

# Or use Arduino IDE to upload src/main.cpp
```

#### 7. Start Services

**Start Arduino Bridge:**
```bash
# Windows
start_arduino_bridge.bat

# Or manually
python arduino_bridge.py
```

**Start YOLO Service:**
```bash
# Windows
start_yolo_service.bat

# Or manually
python yolo_detect2.py
```

#### 8. Access Application
```
URL: http://localhost/eco-farm/
Login: admin / password
       farmer / password
```

---

## ⚙️ How It Works

### 1. Real-Time Sensor Monitoring

#### Arduino Side (C++)
```cpp
// Reads DHT22 and Soil Moisture sensors every 3 seconds
// Outputs formatted data via Serial:
"Temp: 24.5 °C | Humidity: 65.2 % | Soil Moisture: 45 %"
```

#### Python Bridge (Flask)
```python
# arduino_bridge.py
# - Connects to Arduino via Serial (COM3/USB)
# - Parses sensor data
# - Exposes REST API endpoints:
#   GET /health - Service status
#   GET /data - All sensor readings
#   GET /data/<sensor_type> - Specific sensor
```

#### PHP Application
```php
// arduino_sync.php
// - Polls Python bridge every 5 seconds (AJAX)
// - Stores readings in database (configurable interval)
// - Updates dashboard in real-time
// - Generates alerts for threshold violations
```

### 2. AI Pest Detection

#### Upload & Processing
```php
// pest_detection.php
// 1. User uploads image
// 2. PHP validates and saves image
// 3. Sends to YOLO service via cURL
```

#### YOLO Service (Flask)
```python
# yolo_detect2.py
# 1. Receives image via POST /detect
# 2. Runs YOLOv8 inference
# 3. Detects pests with bounding boxes
# 4. Saves annotated image
# 5. Returns JSON with detections
```

#### Results & Recommendations
```php
// PHP processes results:
// 1. Stores pest alert in database
// 2. Looks up pest info from pest_config table
// 3. Displays:
//    - Pest type & common name
//    - Confidence score
//    - Severity level
//    - Suggested actions
//    - Annotated image with bounding boxes
```

### 3. Data Analytics

#### Historical Data Collection
- Sensor readings stored every 5 seconds (configurable)
- Pest detections logged with timestamps
- User actions tracked

#### Visualization
- Chart.js renders time-series data
- Trend analysis (up/down/stable)
- Comparison charts
- Export to PDF via TCPDF

### 4. User Management

#### Authentication
```php
// login.php + logic/login_logic.php
// - Password hashing with bcrypt
// - Session management
// - Role-based access control (RBAC)
```

#### Roles
- **Admin**: Full system access, user management, configuration
- **Farmer**: Sensor monitoring, pest detection, reports
- **Student**: Read-only access for learning

---

## 🎯 Key Features

### 1. Real-Time Dashboard
- Live sensor readings (updates every 5 seconds)
- Status indicators (online/offline/critical)
- Weather integration (OpenWeatherMap API)
- Recent pest alerts
- Quick statistics

### 2. Sensor Management
- Configure sensor thresholds
- View historical data
- Export sensor logs
- Calibration tools

### 3. Pest Detection
- Upload images for analysis
- AI-powered identification (102 pest types)
- Confidence scoring
- Treatment recommendations
- Detection history

### 4. Pest Configuration
- Database-driven pest information
- Filipino/English common names
- Severity levels
- Economic thresholds
- Suggested control measures

### 5. Reports & Analytics
- Generate PDF reports
- Data visualization
- Trend analysis
- Export capabilities (CSV, PDF, Excel)

### 6. Notifications
- Real-time alerts for critical conditions
- Email notifications (EmailJS)
- In-app notification system
- Daily summary reports

### 7. Multi-Language Support
- English and Filipino translations
- Easy to extend with more languages
- User preference storage

### 8. Dark Mode
- System-wide dark theme
- User preference saved
- Smooth transitions

---

## 📁 File Structure

```
eco-farm/
├── 📁 config/                  # Configuration files
│   ├── database.php            # Database connection & helpers
│   └── env.php                 # Environment variable loader
├── 📁 database/                # SQL scripts
│   ├── schema.sql              # Complete database schema
│   └── sample_data.sql         # Sample data for testing
├── 📁 detections/              # Saved pest detection images
├── 📁 includes/                # Shared PHP components
│   ├── header.php              # HTML head & design system
│   ├── navigation.php          # Sidebar navigation
│   ├── arduino-api.php         # Arduino bridge client
│   ├── weather-api.php         # Weather API integration
│   └── pest-config-helper.php  # Pest database helpers
├── 📁 logic/                   # Business logic layer
│   ├── login_logic.php         # Authentication logic
│   └── reset_password_logic.php
├── 📁 src/                     # Arduino source code
│   └── main.cpp                # Arduino firmware
├── 📁 vendor/                  # Composer dependencies
│   └── tecnickcom/tcpdf/       # PDF generation library
│
├── 📄 .env                     # Environment variables
├── 📄 .gitignore               # Git ignore rules
├── 📄 composer.json            # PHP dependencies
├── 📄 platformio.ini           # Arduino build config
│
├── 📄 index.php                # Landing page
├── 📄 login.php                # Login page
├── 📄 dashboard.php            # Main dashboard
├── 📄 sensors.php              # Sensor management
├── 📄 pest_detection.php       # Pest detection interface
├── 📄 pest_config.php          # Pest configuration (admin)
├── 📄 reports.php              # Reports & analytics
│
├── 📄 arduino_bridge.py        # Python Arduino bridge service
├── 📄 arduino_sync.php         # Arduino data sync handler
├── 📄 yolo_detect2.py          # Python YOLO service
├── 📄 YOLODetector2.php        # PHP YOLO client
├── 📄 best.pt                  # YOLOv8 trained model
│
├── 📄 start_arduino_bridge.bat # Windows: Start Arduino service
├── 📄 start_yolo_service.bat   # Windows: Start YOLO service
│
└── 📄 INSTALL.md               # Installation guide
```

---

## 🗄️ Database Schema

### Core Tables

#### 1. **users**
- User authentication and profile information
- Roles: admin, farmer, student
- Password hashing with bcrypt

#### 2. **sensors**
- Sensor configuration and metadata
- Types: temperature, humidity, soil_moisture
- Alert thresholds and calibration

#### 3. **sensor_readings**
- Historical sensor data
- Timestamped readings
- Links to sensor configuration

#### 4. **pest_alerts**
- Pest detection records
- Severity levels and status tracking
- Image paths and recommendations

#### 5. **pest_config**
- Pest information database
- 102 pest types with Filipino/English names
- Treatment recommendations and thresholds

#### 6. **cameras**
- Camera configuration
- IP cameras and detection settings

---

## 🔌 API Endpoints

### Arduino Bridge Service (Port 5000)

#### GET /health
Check service status and Arduino connection

#### GET /data
Get all current sensor readings

#### GET /data/{sensor_type}
Get specific sensor reading (temperature, humidity, soil_moisture)

### YOLO Detection Service (Port 5000)

#### POST /detect
Upload image for pest detection

#### GET /health
Check YOLO service status

#### GET /info
Get model information and supported pest classes

---

## 🔄 Development Workflow

### 1. Local Development

```bash
# Start XAMPP (Apache + MySQL)
# Start Arduino Bridge: python arduino_bridge.py
# Start YOLO Service: python yolo_detect2.py
# Access: http://localhost/eco-farm/
```

### 2. Arduino Development

```bash
# Build and upload firmware
pio run --target upload

# Monitor serial output
pio device monitor
```

### 3. Testing

- Test Arduino connection via Python bridge
- Test YOLO service with sample images
- Verify database connections
- Check all user roles and permissions

### 4. Deployment

- Update `.env` with production settings
- Set `APP_DEBUG=false`
- Configure SSL certificate
- Set up automated backups
- Monitor system logs

---

## 🔧 Troubleshooting

### Arduino Bridge Issues
- Check if service is running on port 5000
- Verify Arduino port in `.env` file
- Check serial connection and baud rate

### YOLO Service Issues
- Verify `best.pt` model file exists
- Check Python package installations
- Test service health endpoint

### Database Issues
- Verify database exists and tables are created
- Check connection credentials in `.env`
- Review MySQL error logs

---

## 📚 Additional Resources

- **Arduino**: https://www.arduino.cc/reference/en/
- **Flask**: https://flask.palletsprojects.com/
- **YOLOv8**: https://docs.ultralytics.com/
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Chart.js**: https://www.chartjs.org/docs/

---

**Last Updated:** October 30, 2024

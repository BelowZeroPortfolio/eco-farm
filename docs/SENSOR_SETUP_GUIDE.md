# Sensor Setup Guide - IoT Farm Monitoring System

## Overview

This guide explains how to set up and configure sensors for the IoT Farm Monitoring System. The system supports multiple sensor types and provides a solution for Arduino sensor recognition through manual configuration.

## Supported Sensors

### 1. Temperature Sensors
- **Type**: DHT22 (recommended) or DHT11
- **Measurement**: Celsius (°C)
- **Optimal Range**: 15-35°C for most crops
- **Arduino Connection**: Digital pins or analog pins A0-A5

### 2. Humidity Sensors
- **Type**: DHT22 (recommended) or DHT11
- **Measurement**: Relative Humidity (%)
- **Optimal Range**: 60-80% for most crops
- **Arduino Connection**: Digital pins or analog pins A0-A5

### 3. Soil Moisture Sensors
- **Type**: Capacitive or resistive soil moisture sensors
- **Measurement**: Moisture percentage (%)
- **Optimal Range**: 40-60% for most vegetables
- **Arduino Connection**: Analog pins A0-A5
- **Recommended Quantity**: 4 sensors for comprehensive coverage

## Arduino Pin Configuration

The system uses Arduino analog pins A0-A5 for sensor connections:

```
A0 - Temperature Sensor (DHT22)
A1 - Humidity Sensor (DHT22)
A2 - Soil Moisture Sensor #1
A3 - Soil Moisture Sensor #2
A4 - Soil Moisture Sensor #3
A5 - Soil Moisture Sensor #4
```

## Solving Arduino Sensor Recognition

### The Problem
Arduino doesn't automatically recognize how many sensors are connected or their types. This creates challenges for dynamic sensor management.

### Our Solution: Manual Configuration with Pin Mapping

1. **Web-Based Configuration**: Use the sensor management interface to manually configure each sensor
2. **Pin Assignment**: Assign each sensor to a specific Arduino pin (A0-A5)
3. **Unique Identification**: Each sensor gets a unique ID and name for tracking
4. **Code Generation**: The system generates Arduino code based on your configuration

### Configuration Process

1. **Access Sensor Management**
   - Navigate to the Sensor Management page
   - Click "Add Sensor" to configure a new sensor

2. **Configure Sensor Details**
   - **Sensor Name**: Descriptive name (e.g., "Greenhouse Temp 1")
   - **Sensor Type**: Temperature, Humidity, or Soil Moisture
   - **Location**: Physical location (e.g., "Greenhouse Zone A")
   - **Arduino Pin**: Select from A0-A5 (system prevents conflicts)
   - **Sensor ID**: Optional unique identifier for tracking

3. **Set Calibration and Alerts**
   - **Calibration Offset**: Adjustment value for accuracy
   - **Alert Thresholds**: Min/max values for notifications

4. **Generate Arduino Code**
   - Click "Preview Code" to see the Arduino sketch
   - Click "Export Config" to download the complete Arduino code
   - Upload the generated code to your Arduino

## Hardware Setup

### Required Components
- Arduino Uno/Nano/ESP32
- DHT22 sensors (for temperature/humidity)
- Soil moisture sensors (capacitive recommended)
- Jumper wires
- Breadboard or PCB
- Power supply (5V for Arduino)

### Wiring Diagram

```
DHT22 Temperature Sensor (A0):
- VCC → 5V
- GND → GND
- DATA → A0

DHT22 Humidity Sensor (A1):
- VCC → 5V
- GND → GND
- DATA → A1

Soil Moisture Sensors (A2-A5):
- VCC → 5V
- GND → GND
- AOUT → A2/A3/A4/A5 respectively
```

## Software Configuration

### 1. Database Setup
The system automatically creates the necessary database tables with enhanced sensor support:

```sql
-- Enhanced sensors table with pin mapping
CREATE TABLE sensors (
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
    -- ... other fields
);
```

### 2. Arduino Code Structure
The generated Arduino code follows this pattern:

```cpp
// Include required libraries
#include <DHT.h>

// Define pins and sensor objects
#define DHT_PIN_0 A0
DHT dht0(DHT_PIN_0, DHT22);

void setup() {
    Serial.begin(9600);
    dht0.begin();
}

void loop() {
    // Read and send sensor data
    float temp = dht0.readTemperature();
    Serial.print("TEMP_A0:");
    Serial.println(temp);
    delay(5000);
}
```

### 3. Data Communication Protocol
Sensors communicate with the web system using this format:
- `TEMP_A[pin]:[value]` - Temperature readings
- `HUM_A[pin]:[value]` - Humidity readings  
- `SOIL_A[pin]:[value]` - Soil moisture readings

## Calibration Process

### 1. Initial Setup
- Install sensors in their final locations
- Let them stabilize for 30 minutes
- Take reference measurements with calibrated instruments

### 2. Web-Based Calibration
- Use the "Calibrate" function in the sensor management interface
- Enter the expected (correct) value
- System calculates and applies the offset automatically

### 3. Ongoing Maintenance
- Recalibrate monthly or when readings seem inaccurate
- Clean sensors regularly to maintain accuracy
- Replace sensors showing consistent drift

## Troubleshooting

### Common Issues

1. **Sensor Not Detected**
   - Check wiring connections
   - Verify Arduino pin assignment in web interface
   - Ensure Arduino code matches web configuration

2. **Inaccurate Readings**
   - Perform calibration using known reference values
   - Check for loose connections
   - Verify sensor placement (avoid direct sunlight, etc.)

3. **Pin Conflicts**
   - System prevents assigning same pin to multiple active sensors
   - Deactivate unused sensors to free up pins
   - Use pin map in web interface to track assignments

4. **Communication Issues**
   - Check serial connection (USB cable)
   - Verify baud rate (9600)
   - Ensure Arduino is powered and running

### Best Practices

1. **Sensor Placement**
   - Temperature: Away from direct heat sources
   - Humidity: In representative air circulation areas
   - Soil Moisture: At root depth, multiple locations per zone

2. **Maintenance Schedule**
   - Weekly: Check web interface for offline sensors
   - Monthly: Clean sensors and check calibration
   - Seasonally: Replace batteries, inspect connections

3. **Data Management**
   - Monitor alert thresholds and adjust seasonally
   - Export data regularly for analysis
   - Set up automated alerts for critical conditions

## Advanced Features

### Multiple Soil Moisture Sensors
The system supports up to 4 soil moisture sensors for comprehensive monitoring:
- Zone-based monitoring
- Average calculations across sensors
- Individual sensor alerts and thresholds

### Automated Code Generation
- Dynamic Arduino code generation based on configuration
- Automatic pin conflict detection
- Template-based code structure for reliability

### Real-time Monitoring
- Live sensor status indicators
- Historical data visualization
- Automated alert system for threshold violations

## Support and Maintenance

For additional support:
1. Check the sensor management interface for diagnostic information
2. Use the Arduino code generator for configuration updates
3. Monitor the system logs for communication issues
4. Refer to individual sensor documentation for specific troubleshooting
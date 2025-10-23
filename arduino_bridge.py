#!/usr/bin/env python3
"""
Arduino Bridge Service for IoT Farm Monitoring System
Reads sensor data from Arduino and provides REST API endpoints
"""

from flask import Flask, jsonify, request
import serial
import threading
import time
import json
import logging
from datetime import datetime

app = Flask(__name__)

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Arduino configuration
ARDUINO_PORT = "COM3"  # Change this to your Arduino port
BAUD_RATE = 9600
TIMEOUT = 1

# Global variables
arduino = None
latest_readings = {
    "temperature": {"value": None, "timestamp": None, "status": "offline"},
    "humidity": {"value": None, "timestamp": None, "status": "offline"},
    "soil_moisture": {"value": None, "timestamp": None, "status": "offline"}
}

def connect_arduino():
    """Initialize Arduino connection"""
    global arduino
    try:
        arduino = serial.Serial(ARDUINO_PORT, BAUD_RATE, timeout=TIMEOUT)
        time.sleep(2)  # Wait for Arduino to initialize
        logger.info(f"✅ Arduino connected on {ARDUINO_PORT}")
        return True
    except Exception as e:
        arduino = None
        logger.warning(f"⚠️ Arduino not found on {ARDUINO_PORT} - running in simulation mode: {e}")
        return False

def parse_arduino_data(line):
    """
    Parse Arduino data from DHT + Soil sensor
    Expected format: "Temp: 24.5 °C | Humidity: 65.2 % | Soil Moisture: 45 % [SIMULATED]"
    """
    try:
        line = line.strip()
        
        # Skip empty lines and error messages
        if not line or "DHT read error" in line or "WARNING:" in line or "DEBUG" in line:
            return None
            
        # Skip system messages
        if any(msg in line for msg in ["Arduino Farm Monitor", "DHT22 + Soil", "====", "Initializing", "detected", "ready", "starting"]):
            return None
            
        # Parse the formatted string
        data = {}
        is_simulated = "[SIMULATED]" in line
        
        # Extract temperature
        if "Temp:" in line:
            temp_start = line.find("Temp:") + 5
            temp_end = line.find("°C", temp_start)
            if temp_end != -1:
                temp_str = line[temp_start:temp_end].strip()
                try:
                    data['temperature'] = float(temp_str)
                except ValueError:
                    pass
        
        # Extract humidity
        if "Humidity:" in line:
            hum_start = line.find("Humidity:") + 9
            hum_end = line.find("%", hum_start)
            if hum_end != -1:
                hum_str = line[hum_start:hum_end].strip()
                try:
                    data['humidity'] = float(hum_str)
                except ValueError:
                    pass
        
        # Extract soil moisture
        if "Soil Moisture:" in line:
            soil_start = line.find("Soil Moisture:") + 14
            soil_end = line.find("%", soil_start)
            if soil_end != -1:
                soil_str = line[soil_start:soil_end].strip()
                try:
                    data['soil_moisture'] = float(soil_str)
                except ValueError:
                    pass
        
        # Add simulation status
        if data and is_simulated:
            logger.info("📊 Using simulated sensor data (DHT22 not connected)")
        
        return data if data else None
            
    except Exception as e:
        logger.error(f"Error parsing Arduino data '{line}': {e}")
    
    return None

def map_value(value, in_min, in_max, out_min, out_max):
    """Map value from one range to another"""
    return (value - in_min) * (out_max - out_min) / (in_max - in_min) + out_min

def read_from_arduino():
    """Continuously read data from Arduino"""
    global latest_readings
    
    while True:
        if arduino and arduino.in_waiting > 0:
            try:
                line = arduino.readline().decode('utf-8').strip()
                if line:
                    logger.info(f"📊 Raw Arduino data: {line}")
                    
                    parsed_data = parse_arduino_data(line)
                    if parsed_data:
                        current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                        
                        for sensor_type, value in parsed_data.items():
                            if sensor_type in latest_readings:
                                latest_readings[sensor_type] = {
                                    "value": value,
                                    "timestamp": current_time,
                                    "status": "online"
                                }
                                if sensor_type == 'temperature':
                                    logger.info(f"🌡️ Temperature: {value}°C")
                                elif sensor_type == 'humidity':
                                    logger.info(f"💧 Humidity: {value}%")
                                elif sensor_type == 'soil_moisture':
                                    logger.info(f"🌱 Soil Moisture: {value}%")
                        
            except Exception as e:
                logger.error(f"⚠️ Error reading serial: {e}")
                # Mark all sensors as offline on error
                for sensor_type in latest_readings:
                    latest_readings[sensor_type]["status"] = "error"
        
        # Simulate data if no Arduino (for testing)
        elif arduino is None:
            simulate_sensor_data()
        
        time.sleep(1)

def simulate_sensor_data():
    """Simulate sensor data for testing without Arduino"""
    import random
    current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    
    # Simulate realistic sensor values
    simulated_data = {
        "temperature": round(random.uniform(20, 30), 1),
        "humidity": round(random.uniform(60, 80), 1),
        "soil_moisture": round(random.uniform(40, 60), 1)
    }
    
    for sensor_type, value in simulated_data.items():
        latest_readings[sensor_type] = {
            "value": value,
            "timestamp": current_time,
            "status": "simulated"
        }

@app.route("/health", methods=["GET"])
def health():
    """Health check endpoint"""
    return jsonify({
        "status": "healthy",
        "arduino_connected": arduino is not None,
        "port": ARDUINO_PORT,
        "version": "1.0"
    })

@app.route("/data", methods=["GET"])
def get_all_data():
    """Get all sensor readings"""
    response = jsonify({
        "status": "success",
        "data": latest_readings,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    })
    response.headers.add("Access-Control-Allow-Origin", "*")
    return response

@app.route("/data/<sensor_type>", methods=["GET"])
def get_sensor_data(sensor_type):
    """Get specific sensor reading"""
    if sensor_type in latest_readings:
        response = jsonify({
            "status": "success",
            "sensor_type": sensor_type,
            "data": latest_readings[sensor_type]
        })
    else:
        response = jsonify({
            "status": "error",
            "message": f"Sensor type '{sensor_type}' not found"
        }), 404
    
    response.headers.add("Access-Control-Allow-Origin", "*")
    return response

@app.route("/simulate", methods=["POST"])
def simulate_data():
    """Manually trigger data simulation (for testing)"""
    simulate_sensor_data()
    return jsonify({
        "status": "success",
        "message": "Simulation data generated",
        "data": latest_readings
    })

if __name__ == "__main__":
    print("=" * 60)
    print("Arduino Bridge Service - IoT Farm Monitoring")
    print("=" * 60)
    
    # Try to connect to Arduino
    arduino_connected = connect_arduino()
    
    # Start background thread for reading data
    threading.Thread(target=read_from_arduino, daemon=True).start()
    
    print(f"🚀 Service starting on http://127.0.0.1:5000")
    print(f"📡 Arduino Status: {'Connected' if arduino_connected else 'Simulation Mode'}")
    print("=" * 60)
    
    app.run(host="127.0.0.1", port=5000, debug=False)
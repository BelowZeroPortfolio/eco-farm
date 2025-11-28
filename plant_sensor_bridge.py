#!/usr/bin/env python3
"""
Plant Sensor Bridge
Reads data from Arduino bridge and syncs with plant monitoring system
"""

import requests
import time
import json
import logging
from datetime import datetime

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Configuration
ARDUINO_BRIDGE_URL = "http://127.0.0.1:5000"
PLANT_API_URL = "http://localhost/api/plant_sensor_sync.php"
SENSOR_INTERVAL_API = "http://localhost/api/get_sensor_interval.php"
DEFAULT_SYNC_INTERVAL = 30  # seconds (fallback)

class PlantSensorBridge:
    def __init__(self):
        self.running = False
        self.active_plant = None
        self.sync_interval = DEFAULT_SYNC_INTERVAL
        
    def get_sensor_interval(self):
        """Get sensor logging interval from API"""
        try:
            response = requests.get(SENSOR_INTERVAL_API, timeout=5)
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    self.sync_interval = data.get('interval_seconds', DEFAULT_SYNC_INTERVAL)
                    logger.info(f"Sync interval: {self.sync_interval} seconds ({data.get('display', 'N/A')})")
                    return True
            return False
        except Exception as e:
            logger.error(f"Failed to get sensor interval: {e}")
            self.sync_interval = DEFAULT_SYNC_INTERVAL
            return False
    
    def get_active_plant(self):
        """Get active plant configuration from API"""
        try:
            response = requests.get(PLANT_API_URL, timeout=5)
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    self.active_plant = data.get('active_plant')
                    logger.info(f"Active plant: {self.active_plant['name']} ({self.active_plant['local_name']})")
                    return True
            return False
        except Exception as e:
            logger.error(f"Failed to get active plant: {e}")
            return False
    
    def get_sensor_data(self):
        """Get sensor data from Arduino bridge"""
        try:
            response = requests.get(f"{ARDUINO_BRIDGE_URL}/data", timeout=5)
            if response.status_code == 200:
                data = response.json()
                if data.get('status') == 'success':
                    return data.get('data')
            return None
        except Exception as e:
            logger.error(f"Failed to get sensor data: {e}")
            return None
    
    def sync_sensor_data(self, sensor_data):
        """Sync sensor data with plant monitoring system"""
        try:
            # Extract sensor values
            temperature = sensor_data.get('temperature', {}).get('value')
            humidity = sensor_data.get('humidity', {}).get('value')
            soil_moisture = sensor_data.get('soil_moisture', {}).get('value')
            
            if temperature is None or humidity is None or soil_moisture is None:
                logger.warning("Incomplete sensor data, skipping sync")
                return False
            
            # Send to plant monitoring API
            payload = {
                'temperature': temperature,
                'humidity': humidity,
                'soil_moisture': soil_moisture
            }
            
            response = requests.post(
                PLANT_API_URL,
                json=payload,
                headers={'Content-Type': 'application/json'},
                timeout=5
            )
            
            if response.status_code == 200:
                result = response.json()
                if result.get('success'):
                    logger.info(f"✓ Synced: T={temperature}°C, H={humidity}%, SM={soil_moisture}%")
                    
                    # Check for violations
                    if result.get('violations'):
                        logger.warning(f"⚠ {len(result['violations'])} threshold violation(s) detected")
                        for violation in result['violations']:
                            logger.warning(f"  - {violation['sensor']}: {violation['status']} (Current: {violation['current']}, Range: {violation['range']})")
                    
                    # Check if notification triggered
                    if result.get('notification_triggered'):
                        logger.warning(f"🔔 Notification triggered! Warning level: {result.get('warning_level')}")
                    
                    return True
                else:
                    logger.error(f"Sync failed: {result.get('message')}")
            else:
                logger.error(f"HTTP error: {response.status_code}")
            
            return False
            
        except Exception as e:
            logger.error(f"Failed to sync sensor data: {e}")
            return False
    
    def check_thresholds(self, sensor_data):
        """Display threshold comparison"""
        if not self.active_plant:
            return
        
        temperature = sensor_data.get('temperature', {}).get('value')
        humidity = sensor_data.get('humidity', {}).get('value')
        soil_moisture = sensor_data.get('soil_moisture', {}).get('value')
        
        thresholds = self.active_plant.get('thresholds', {})
        
        logger.info("=" * 60)
        logger.info(f"Plant: {self.active_plant['name']} ({self.active_plant['local_name']})")
        logger.info("-" * 60)
        
        # Temperature
        temp_range = thresholds.get('temperature', {})
        temp_status = "✓" if temp_range.get('min') <= temperature <= temp_range.get('max') else "✗"
        logger.info(f"{temp_status} Temperature: {temperature}°C (Range: {temp_range.get('min')}-{temp_range.get('max')}°C)")
        
        # Humidity
        hum_range = thresholds.get('humidity', {})
        hum_status = "✓" if hum_range.get('min') <= humidity <= hum_range.get('max') else "✗"
        logger.info(f"{hum_status} Humidity: {humidity}% (Range: {hum_range.get('min')}-{hum_range.get('max')}%)")
        
        # Soil Moisture
        soil_range = thresholds.get('soil_moisture', {})
        soil_status = "✓" if soil_range.get('min') <= soil_moisture <= soil_range.get('max') else "✗"
        logger.info(f"{soil_status} Soil Moisture: {soil_moisture}% (Range: {soil_range.get('min')}-{soil_range.get('max')}%)")
        
        logger.info("=" * 60)
    
    def run(self):
        """Main loop"""
        logger.info("=" * 60)
        logger.info("Plant Sensor Bridge - Starting")
        logger.info("=" * 60)
        
        # Get sensor interval from database settings
        self.get_sensor_interval()
        
        # Get active plant configuration
        if not self.get_active_plant():
            logger.error("Failed to get active plant configuration. Exiting.")
            return
        
        self.running = True
        logger.info(f"Sync interval: {self.sync_interval} seconds")
        logger.info("Press Ctrl+C to stop")
        logger.info("")
        
        last_interval_check = time.time()
        interval_check_frequency = 60  # Check for interval changes every 60 seconds
        
        try:
            while self.running:
                # Periodically refresh the sync interval from database
                if time.time() - last_interval_check >= interval_check_frequency:
                    old_interval = self.sync_interval
                    self.get_sensor_interval()
                    if old_interval != self.sync_interval:
                        logger.info(f"Sync interval updated: {old_interval}s → {self.sync_interval}s")
                    last_interval_check = time.time()
                
                # Get sensor data from Arduino bridge
                sensor_data = self.get_sensor_data()
                
                if sensor_data:
                    # Display threshold comparison
                    self.check_thresholds(sensor_data)
                    
                    # Sync with plant monitoring system
                    self.sync_sensor_data(sensor_data)
                else:
                    logger.warning("No sensor data available from Arduino bridge")
                
                # Wait for next sync using dynamic interval
                time.sleep(self.sync_interval)
                
        except KeyboardInterrupt:
            logger.info("\nShutting down gracefully...")
            self.running = False
        except Exception as e:
            logger.error(f"Unexpected error: {e}")
            self.running = False

if __name__ == "__main__":
    bridge = PlantSensorBridge()
    bridge.run()

/*
 * IoT Farm Monitoring System - Arduino Sensor Template
 * 
 * This template shows how to set up multiple sensors with the farm monitoring system.
 * The system supports:
 * - Temperature sensors (DHT22)
 * - Humidity sensors (DHT22) 
 * - Soil moisture sensors (analog)
 * 
 * Pin Configuration:
 * A0-A5: Analog pins for sensors
 * 
 * Serial Communication Format:
 * TEMP_A[pin]:[value]
 * HUM_A[pin]:[value]
 * SOIL_A[pin]:[value]
 */

#include <DHT.h>

// Sensor Configuration - Update these based on your setup
#define TEMP_SENSOR_PIN A0
#define HUM_SENSOR_PIN A1
#define SOIL_SENSOR_1_PIN A2
#define SOIL_SENSOR_2_PIN A3
#define SOIL_SENSOR_3_PIN A4
#define SOIL_SENSOR_4_PIN A5

#define DHT_TYPE DHT22

// Initialize DHT sensors
DHT tempSensor(TEMP_SENSOR_PIN, DHT_TYPE);
DHT humSensor(HUM_SENSOR_PIN, DHT_TYPE);

void setup() {
  Serial.begin(9600);
  Serial.println("IoT Farm Monitoring System Starting...");
  
  // Initialize DHT sensors
  tempSensor.begin();
  humSensor.begin();
  
  Serial.println("Sensors initialized:");
  Serial.println("- Temperature sensor on A0");
  Serial.println("- Humidity sensor on A1");
  Serial.println("- Soil moisture sensors on A2-A5");
  Serial.println("Ready to collect data...");
}

void loop() {
  // Read temperature
  float temperature = tempSensor.readTemperature();
  if (!isnan(temperature)) {
    Serial.print("TEMP_A0:");
    Serial.println(temperature);
  }
  
  // Read humidity
  float humidity = humSensor.readHumidity();
  if (!isnan(humidity)) {
    Serial.print("HUM_A1:");
    Serial.println(humidity);
  }
  
  // Read soil moisture sensors
  readSoilMoisture(SOIL_SENSOR_1_PIN, 2);
  readSoilMoisture(SOIL_SENSOR_2_PIN, 3);
  readSoilMoisture(SOIL_SENSOR_3_PIN, 4);
  readSoilMoisture(SOIL_SENSOR_4_PIN, 5);
  
  delay(5000); // Read every 5 seconds
}

void readSoilMoisture(int pin, int pinNumber) {
  int sensorValue = analogRead(pin);
  // Convert to percentage (0-100%)
  // Note: You may need to calibrate these values based on your specific sensors
  float moisture = map(sensorValue, 0, 1023, 0, 100);
  
  Serial.print("SOIL_A");
  Serial.print(pinNumber);
  Serial.print(":");
  Serial.println(moisture);
}

/*
 * Calibration Notes:
 * 
 * For soil moisture sensors:
 * - Dry soil typically reads around 1023 (0% moisture)
 * - Wet soil typically reads around 300-500 (100% moisture)
 * - You may need to adjust the map() function values based on your sensors
 * 
 * For temperature/humidity sensors:
 * - DHT22 sensors are generally accurate out of the box
 * - If calibration is needed, apply offset in the web interface
 * 
 * Serial Communication:
 * - The web system expects data in the format: SENSOR_TYPE_PIN:VALUE
 * - Make sure your Arduino is connected via USB or WiFi module
 * - Baud rate should be 9600 to match the web system expectations
 */
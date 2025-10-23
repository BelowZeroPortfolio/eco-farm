/*
 * DHT22 + Soil Moisture Sensor for IoT Farm Monitoring System
 * Arduino Mega 2560 R3 Compatible
 * 
 * Reads DHT22 (temperature & humidity) and soil moisture sensor
 * Sends formatted data to Python bridge via Serial
 * 
 * ROBUST VERSION - Works with or without sensors connected
 */

#include <Arduino.h>  // Essential for PlatformIO
#include <DHT.h>      // DHT sensor library

// ----- SENSOR CONFIGURATION -----
#define DHT_PIN 2           // DHT22 data pin
#define DHT_TYPE DHT22      // Use DHT22, not DHT11 (better accuracy)
#define SOIL_PIN A0         // Soil sensor analog pin (A0 is universally available)

// ----- SENSOR OBJECTS -----
DHT dht(DHT_PIN, DHT_TYPE);

// ----- TIMING VARIABLES -----
unsigned long lastReading = 0;
const unsigned long READING_INTERVAL = 3000; // 3 seconds between readings (slower for stability)

// ----- CALIBRATION VALUES -----
const int SOIL_WET = 300;    // Sensor value when soil is wet
const int SOIL_DRY = 700;    // Sensor value when soil is dry

// ----- ERROR HANDLING -----
int dhtErrorCount = 0;
const int MAX_DHT_ERRORS = 5;
bool dhtSensorAvailable = true;
bool systemInitialized = false;

void setup() {
  // Initialize serial communication
  Serial.begin(9600);
  
  // Wait for serial to be ready
  while (!Serial) {
    delay(10);
  }
  
  // Initialize DHT sensor
  dht.begin();
  
  // Longer stabilization time for DHT22
  delay(3000);
  
  Serial.println("Arduino Farm Monitor Started");
  Serial.println("DHT22 + Soil Moisture Sensor");
  Serial.println("============================");
  Serial.println("Initializing sensors...");
  
  // Test DHT sensor on startup
  float testTemp = dht.readTemperature();
  float testHum = dht.readHumidity();
  
  if (isnan(testTemp) || isnan(testHum)) {
    Serial.println("WARNING: DHT22 sensor not detected - using simulation mode");
    dhtSensorAvailable = false;
  } else {
    Serial.println("DHT22 sensor detected and working");
    dhtSensorAvailable = true;
  }
  
  Serial.println("System ready - starting data collection");
  systemInitialized = true;
}

void loop() {
  unsigned long currentTime = millis();
  
  // Check if it's time for a new reading
  if (currentTime - lastReading >= READING_INTERVAL) {
    lastReading = currentTime;
    
    float humidity, temperature;
    
    // Try to read DHT22 sensor
    if (dhtSensorAvailable) {
      humidity = dht.readHumidity();
      temperature = dht.readTemperature();
      
      // Check if readings are valid
      if (isnan(humidity) || isnan(temperature)) {
        dhtErrorCount++;
        
        if (dhtErrorCount >= MAX_DHT_ERRORS) {
          Serial.println("DHT22 sensor failed - switching to simulation mode");
          dhtSensorAvailable = false;
          dhtErrorCount = 0;
        } else {
          Serial.print("DHT read error (");
          Serial.print(dhtErrorCount);
          Serial.print("/");
          Serial.print(MAX_DHT_ERRORS);
          Serial.println(") - retrying...");
          return; // Skip this reading
        }
      } else {
        dhtErrorCount = 0; // Reset error count on successful read
      }
    }
    
    // Use simulated data if DHT not available
    if (!dhtSensorAvailable) {
      // Generate realistic simulated values
      temperature = 22.0 + (millis() % 100) / 10.0; // 22.0 to 32.0°C
      humidity = 50.0 + (millis() % 300) / 10.0;    // 50.0 to 80.0%
    }
    
    // Read soil moisture sensor (always works)
    int soilRaw = analogRead(SOIL_PIN);
    
    // Convert soil reading to percentage (0% = dry, 100% = wet)
    int soilPercent = map(soilRaw, SOIL_DRY, SOIL_WET, 0, 100);
    soilPercent = constrain(soilPercent, 0, 100); // Ensure 0-100% range
    
    // Output formatted data for Python bridge (EXACT format expected)
    Serial.print("Temp: ");
    Serial.print(temperature, 1); // 1 decimal place
    Serial.print(" °C | Humidity: ");
    Serial.print(humidity, 1);    // 1 decimal place
    Serial.print(" % | Soil Moisture: ");
    Serial.print(soilPercent);
    Serial.print(" %");
    
    // Add status indicator
    if (!dhtSensorAvailable) {
      Serial.print(" [SIMULATED]");
    }
    
    Serial.println();
    
    // Debug info every 10th reading
    static int readingCount = 0;
    readingCount++;
    if (readingCount % 10 == 0) {
      Serial.print("DEBUG - Soil Raw: ");
      Serial.print(soilRaw);
      Serial.print(" | DHT Status: ");
      Serial.println(dhtSensorAvailable ? "REAL" : "SIMULATED");
    }
  }
}

/*
 * WIRING INSTRUCTIONS FOR ARDUINO MEGA 2560:
 * 
 * DHT22 Sensor:
 * - Pin 1 (VCC) → Arduino 5V
 * - Pin 2 (DATA) → Arduino Digital Pin 2
 * - Pin 3 (NC) → Not connected
 * - Pin 4 (GND) → Arduino GND
 * - 10kΩ resistor between Pin 1 (VCC) and Pin 2 (DATA)
 * 
 * Soil Moisture Sensor:
 * - VCC → Arduino 5V
 * - GND → Arduino GND
 * - A0 → Arduino Analog Pin A0
 * 
 * CALIBRATION STEPS:
 * 1. Insert sensor in completely dry soil, note the raw value
 * 2. Insert sensor in wet soil, note the raw value  
 * 3. Update SOIL_WET and SOIL_DRY constants above
 * 
 * EXPECTED OUTPUT FORMAT:
 * "Temp: 24.5 °C | Humidity: 65.2 % | Soil Moisture: 45 %"
 * 
 * This format is parsed by arduino_bridge.py
 */
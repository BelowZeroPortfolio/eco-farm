/*
 * DHT22 + Soil Moisture Sensor for IoT Farm Monitoring System
 * 
 * Reads DHT22 (temperature & humidity) and soil moisture sensor
 * Sends formatted data to Python bridge
 */

#include <Arduino.h>

// ----- CONFIG -----
#define DHT_PIN 2     // DHT data pin
#define SOIL_PIN A10  // Soil sensor analog pin

// ----- VARIABLES -----
uint8_t dhtData[5];   // 5 bytes from DHT

// ----- FUNCTIONS -----
bool readDHT() {
  uint8_t bits[5] = {0};
  uint8_t cnt = 7;
  uint8_t idx = 0;

  // Send start signal
  pinMode(DHT_PIN, OUTPUT);
  digitalWrite(DHT_PIN, LOW);
  delay(20);  // at least 18ms
  digitalWrite(DHT_PIN, HIGH);
  delayMicroseconds(40);
  pinMode(DHT_PIN, INPUT);

  // Wait for response
  unsigned int loopCnt = 10000;
  while (digitalRead(DHT_PIN) == LOW) {
    if (--loopCnt == 0) return false;
  }

  loopCnt = 10000;
  while (digitalRead(DHT_PIN) == HIGH) {
    if (--loopCnt == 0) return false;
  }

  // Read 40 bits
  for (int i = 0; i < 40; i++) {
    loopCnt = 10000;
    while (digitalRead(DHT_PIN) == LOW) {
      if (--loopCnt == 0) return false;
    }

    unsigned long t = micros();
    loopCnt = 10000;
    while (digitalRead(DHT_PIN) == HIGH) {
      if (--loopCnt == 0) return false;
    }

    if ((micros() - t) > 40) bits[idx] |= (1 << cnt);
    if (cnt == 0) {
      cnt = 7;
      idx++;
    } else cnt--;
  }

  for (int i = 0; i < 5; i++) dhtData[i] = bits[i];
  return true;
}

float getHumidity() {
  return dhtData[0]; // Integer part of humidity
}

float getTemperature() {
  return dhtData[2]; // Integer part of temperature
}

// ----- MAIN -----
void setup() {
  Serial.begin(9600);
}

void loop() {
  if (readDHT()) {
    float humidity = getHumidity();
    float temperature = getTemperature();
    int soilValue = analogRead(SOIL_PIN);
    int soilPercent = map(soilValue, 1023, 0, 0, 100); // UNO: 10-bit ADC (0–1023)

    Serial.print("Temp: ");
    Serial.print(temperature);
    Serial.print(" °C | Humidity: ");
    Serial.print(humidity);
    Serial.print(" % | Soil Moisture: ");
    Serial.print(soilPercent);
    Serial.println(" %");
  } else {
    Serial.println("DHT read error!");
  }
  delay(2000);
}

/*
 * Wiring Instructions:
 * 
 * DHT22 Sensor:
 * - VCC to 5V
 * - GND to GND
 * - DATA to Digital Pin 2
 * - Add 10kΩ pull-up resistor between VCC and DATA
 * 
 * Soil Moisture Sensor:
 * - VCC to 5V
 * - GND to GND
 * - A0 to Analog Pin A10
 * 
 * Expected Output Format:
 * "Temp: 24.5 °C | Humidity: 65.2 % | Soil Moisture: 45 %"
 */
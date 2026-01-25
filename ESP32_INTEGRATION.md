# ESP32 Integration Guide

This guide explains how to connect your ESP32 device to the MTICS Bin It to Win It website.

## Overview

The ESP32 device should send HTTP POST requests to the recycling API endpoint whenever a bottle is detected by the sensor. The API will automatically:
1. Verify the API key
2. Validate the student ID
3. Award Eco-Tokens to the student's account
4. Record the recycling activity

## API Endpoint

**URL**: `http://your-domain.com/BinItToWinIt/api/recycle.php`

**Method**: POST

**Content-Type**: application/json

## Request Format

```json
{
    "api_key": "your_64_character_api_key",
    "student_id": "STUDENT123",
    "sensor_id": "BIN001",
    "bottle_type": "plastic",
    "device_timestamp": "2026-01-25 10:30:00"
}
```

### Required Fields

- `api_key` (string): Your device's API key (64 characters, hex)
- `student_id` (string): The student's ID or email
- `sensor_id` (string): Unique identifier for your sensor/bin

### Optional Fields

- `bottle_type` (string): Type of bottle (default: "plastic")
- `device_timestamp` (string): Timestamp from device (format: "YYYY-MM-DD HH:MM:SS")

## Response Format

### Success Response (200)

```json
{
    "success": true,
    "message": "Recycling activity recorded successfully",
    "data": {
        "activity_id": 123,
        "tokens_earned": 5.00,
        "new_balance": 25.50,
        "student_id": "STUDENT123",
        "student_name": "John Doe"
    }
}
```

### Error Responses

**400 Bad Request** - Missing or invalid data:
```json
{
    "success": false,
    "message": "Missing required field: student_id"
}
```

**401 Unauthorized** - Invalid API key:
```json
{
    "success": false,
    "message": "Invalid or inactive API key"
}
```

**404 Not Found** - Student not found:
```json
{
    "success": false,
    "message": "Student not found or account inactive"
}
```

**500 Internal Server Error** - Server error:
```json
{
    "success": false,
    "message": "Database error occurred"
}
```

## Example ESP32 Code

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// WiFi credentials
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// API configuration
const char* apiUrl = "http://your-domain.com/BinItToWinIt/api/recycle.php";
const char* apiKey = "your_64_character_api_key_here";
const char* sensorId = "BIN001";

// Pin configuration
const int sensorPin = 2; // GPIO pin connected to sensor
const int ledPin = 4;    // LED indicator

// State variables
bool lastSensorState = HIGH;
unsigned long lastDebounceTime = 0;
unsigned long debounceDelay = 50;

void setup() {
    Serial.begin(115200);
    pinMode(sensorPin, INPUT_PULLUP);
    pinMode(ledPin, OUTPUT);
    
    // Connect to WiFi
    WiFi.begin(ssid, password);
    Serial.print("Connecting to WiFi");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("\nWiFi connected!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
}

void loop() {
    // Read sensor state
    int sensorState = digitalRead(sensorPin);
    
    // Debounce
    if (sensorState != lastSensorState) {
        lastDebounceTime = millis();
    }
    
    if ((millis() - lastDebounceTime) > debounceDelay) {
        // Sensor triggered (bottle detected)
        if (sensorState == LOW && lastSensorState == HIGH) {
            Serial.println("Bottle detected!");
            digitalWrite(ledPin, HIGH);
            
            // Send data to API
            sendRecyclingData("STUDENT123"); // Replace with actual student ID
            
            delay(1000); // Prevent multiple triggers
            digitalWrite(ledPin, LOW);
        }
    }
    
    lastSensorState = sensorState;
    delay(10);
}

void sendRecyclingData(const char* studentId) {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi not connected!");
        return;
    }
    
    HTTPClient http;
    http.begin(apiUrl);
    http.addHeader("Content-Type", "application/json");
    
    // Create JSON payload
    StaticJsonDocument<200> doc;
    doc["api_key"] = apiKey;
    doc["student_id"] = studentId;
    doc["sensor_id"] = sensorId;
    doc["bottle_type"] = "plastic";
    
    // Get current timestamp
    struct tm timeinfo;
    if (getLocalTime(&timeinfo)) {
        char timestamp[20];
        strftime(timestamp, sizeof(timestamp), "%Y-%m-%d %H:%M:%S", &timeinfo);
        doc["device_timestamp"] = timestamp;
    }
    
    String jsonPayload;
    serializeJson(doc, jsonPayload);
    
    Serial.print("Sending: ");
    Serial.println(jsonPayload);
    
    int httpResponseCode = http.POST(jsonPayload);
    
    if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.print("Response code: ");
        Serial.println(httpResponseCode);
        Serial.print("Response: ");
        Serial.println(response);
        
        // Parse response
        StaticJsonDocument<300> responseDoc;
        deserializeJson(responseDoc, response);
        
        if (responseDoc["success"]) {
            float tokensEarned = responseDoc["data"]["tokens_earned"];
            float newBalance = responseDoc["data"]["new_balance"];
            Serial.print("Success! Tokens earned: ");
            Serial.print(tokensEarned);
            Serial.print(", New balance: ");
            Serial.println(newBalance);
        } else {
            Serial.print("Error: ");
            Serial.println(responseDoc["message"]);
        }
    } else {
        Serial.print("Error on HTTP request: ");
        Serial.println(httpResponseCode);
    }
    
    http.end();
}
```

## Getting an API Key

1. **Through Database** (Manual):
   - Access phpMyAdmin
   - Go to `mtics_db` database
   - Navigate to `api_keys` table
   - Insert a new record with:
     - `device_id`: Unique identifier (e.g., "BIN001")
     - `api_key`: Generate using: `SELECT HEX(RANDOM_BYTES(32))`
     - `device_name`: Friendly name (e.g., "Main Entrance Bin")
     - `location`: Physical location
     - `is_active`: 1

2. **Through Code** (Programmatic):
   - Use the `api/generate_api_key.php` endpoint (requires authentication)
   - Or create a simple admin script

## Security Considerations

1. **Store API Key Securely**: Never hardcode API keys in your source code if sharing. Use EEPROM or secure storage.

2. **HTTPS in Production**: Use HTTPS instead of HTTP for production deployments.

3. **Rate Limiting**: Implement delays between requests to prevent spam.

4. **Error Handling**: Always handle network errors and API failures gracefully.

5. **Student ID Input**: Consider using RFID/NFC cards or QR codes for student identification instead of hardcoding.

## Testing

### Using cURL

```bash
curl -X POST http://localhost/BinItToWinIt/api/recycle.php \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "your_api_key",
    "student_id": "STUDENT123",
    "sensor_id": "BIN001",
    "bottle_type": "plastic"
  }'
```

### Using Postman

1. Set method to POST
2. URL: `http://localhost/BinItToWinIt/api/recycle.php`
3. Headers: `Content-Type: application/json`
4. Body (raw JSON):
```json
{
    "api_key": "your_api_key",
    "student_id": "STUDENT123",
    "sensor_id": "BIN001",
    "bottle_type": "plastic"
}
```

## Troubleshooting

### WiFi Connection Issues
- Verify SSID and password
- Check signal strength
- Ensure 2.4GHz network (ESP32 doesn't support 5GHz)

### API Connection Issues
- Verify the API URL is correct
- Check that the web server is running
- Ensure CORS is properly configured
- Check server error logs

### Authentication Failures
- Verify API key is correct and active
- Check that student ID exists in database
- Ensure student account is active

### Sensor Issues
- Implement proper debouncing
- Check sensor wiring
- Verify sensor is working with serial output

## Advanced Features

### RFID/NFC Student Identification

```cpp
#include <MFRC522.h>

MFRC522 mfrc522(SS_PIN, RST_PIN);

void loop() {
    // Check for RFID card
    if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
        String studentId = getStudentIdFromCard(); // Your function
        sendRecyclingData(studentId.c_str());
        delay(2000); // Prevent multiple reads
    }
}
```

### Multiple Sensors

Use different `sensor_id` values for each sensor:
- `BIN001` - Main entrance
- `BIN002` - Library
- `BIN003` - Cafeteria
- etc.

## Support

For issues or questions:
- Check the main README.md
- Review server error logs
- Contact MTICS technical support

---

**Happy Recycling! ♻️**

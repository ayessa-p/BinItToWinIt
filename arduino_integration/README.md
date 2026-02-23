# Arduino/ESP32 Integration Guide

## 🚀 Complete Integration Steps

### **Step 1: Database Setup**
1. Run SQL migration: `http://localhost/BinItToWinIt/database/create_sensor_table.php`
2. This creates `sensor_readings` table for storing sensor data

### **Step 2: Arduino IDE Setup**
1. Install required libraries:
   - `HX711` by Bogdan Necula
   - `ESP32Servo` by Kevin Harrington
   - `ArduinoJson` by Benoit Blanchon
   - `WiFi` (built-in)
   - `HTTPClient` (built-in)

2. Open `esp32_web_integration.ino` in Arduino IDE

### **Step 3: Configure WiFi**
Replace these lines in Arduino code:
```cpp
const char* ssid = "YOUR_WIFI_SSID";        // Your WiFi name
const char* password = "YOUR_WIFI_PASSWORD";  // Your WiFi password
const char* server_url = "http://192.168.1.100/BinItToWinIt/api/recycling_data.php"; // Your server IP
```

### **Step 4: Upload to ESP32**
1. Connect ESP32 to computer
2. Select correct board in Arduino IDE (ESP32 Dev Module)
3. Upload code

### **Step 5: Test Connection**
1. Open Serial Monitor (115200 baud)
2. Should see "WiFi connected!" and "System Ready"
3. Test with weight between 10-25g, distance <10cm, no metal

### **Step 6: Monitor in Web Panel**
1. Go to Admin Panel → Sensor Monitor
2. Real-time data will appear every 5 seconds
3. Accepted items automatically create recycling activities

## 📊 What the System Does

### **Hardware Detection:**
- **Weight**: 10-25g range triggers acceptance
- **Distance**: Must be <10cm from ultrasonic sensor
- **Metal Detection**: Must be non-metal (inductive sensor)
- **Servo**: Activates when all conditions met

### **Web Integration:**
- **Data Upload**: Sends JSON data every 5 seconds
- **Token Rewards**: Automatically awards tokens for accepted items
- **Real-time Monitoring**: Live dashboard shows all readings
- **Database Storage**: All sensor data saved for analysis

### **Admin Features:**
- **Live Dashboard**: Real-time sensor readings
- **Statistics**: Today's readings, accepted items, average weight
- **Auto-refresh**: Updates every 5 seconds
- **Device Tracking**: Multiple ESP32 devices supported

## 🔧 Troubleshooting

### **WiFi Issues:**
- Check SSID and password
- Ensure ESP32 is within WiFi range
- Try restarting ESP32

### **Data Not Appearing:**
- Check server URL in Arduino code
- Verify API endpoint exists
- Check PHP error logs

### **Sensor Issues:**
- Calibrate weight sensor (HX711)
- Check ultrasonic sensor connections
- Verify inductive sensor wiring

## 🎯 Next Steps

1. **Multiple Devices**: Add device-to-user mapping
2. **Mobile App**: Create app for users to view their recycling
3. **Analytics**: Add charts and trends
4. **Notifications**: Email/SMS alerts for accepted items

## 📁 Files Created

- `esp32_web_integration.ino` - Enhanced Arduino code with WiFi
- `api/recycling_data.php` - Web endpoint for data reception
- `database/create_sensor_table.sql` - Database migration script
- `admin/sensor_monitor.php` - Admin dashboard for monitoring
- `includes/admin_header.php` - Updated with Sensor Monitor menu

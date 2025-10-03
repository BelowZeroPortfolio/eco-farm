# Database Setup for IoT Farm Monitoring System

This directory contains the database schema and sample data for the IoT Farm Monitoring System.

## Files

- `schema.sql` - Complete database schema with all required tables
- `sample_data.sql` - Static sample data for demonstration purposes
- `setup.sql` - Combined setup script (drops existing tables and recreates)
- `README.md` - This documentation file

## Database Tables

### Users Table

- Stores user accounts with role-based access control
- Roles: admin, student, farmer
- Includes email, status, and login tracking

### Sensors Table

- IoT sensor device information
- Types: temperature, humidity, soil_moisture
- Includes location and online/offline status

### Sensor Readings Table

- Historical sensor data with timestamps
- Linked to sensors via foreign key
- Includes value, unit, and recording time

### Pest Alerts Table

- Pest detection events and alerts
- Severity levels: low, medium, high, critical
- Status tracking: new, acknowledged, resolved
- Includes descriptions and suggested actions

### User Settings Table

- User preferences and configuration
- Theme, layout, notification preferences
- Extensible key-value structure

## Sample Data Overview

### Users (5 accounts)

- 1 Admin: `admin` / `admin@farm.com`
- 2 Farmers: `farmer1`, `farmer2`
- 2 Students: `student1`, `student2` (one inactive)

### Sensors (10 sensors)

- 3 Temperature sensors (1 offline)
- 3 Humidity sensors (all online)
- 4 Soil moisture sensors (1 offline)
- Located in greenhouses and fields

### Sensor Readings

- 7 days of historical data
- Realistic value ranges:
  - Temperature: 18°C - 35°C
  - Humidity: 40% - 85%
  - Soil Moisture: 20% - 80%

### Pest Alerts (8 alerts)

- Various pest types: aphids, caterpillars, fungal infections, etc.
- Different severity levels and status states
- Realistic descriptions and action recommendations

## Setup Instructions

### Method 1: Using PHP Setup Script (Recommended)

```bash
php install/setup_database.php
```

### Method 2: Manual MySQL Setup

```sql
-- Create database
CREATE DATABASE farm_database;
USE farm_database;

-- Run schema
SOURCE database/schema.sql;

-- Insert sample data
SOURCE database/sample_data.sql;
```

### Method 3: Complete Reset

```sql
-- Use this if you need to completely reset
SOURCE database/setup.sql;
```

## Configuration

Update the database connection settings in `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'farm_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

## Sample Login Credentials

All sample users use the password: `password`

- **Admin**: `admin` / `admin@farm.com`
- **Farmer**: `farmer1` / `farmer1@farm.com`
- **Student**: `student1` / `student1@university.edu`

## Data Relationships

```
users (1) ←→ (n) user_settings
sensors (1) ←→ (n) sensor_readings
```

- Users can have multiple settings
- Sensors can have multiple readings
- Pest alerts are independent entities
- Foreign key constraints ensure data integrity

## Notes

- All timestamps use MySQL's TIMESTAMP type with automatic updates
- Passwords are hashed using PHP's `password_hash()` function
- Sample data includes realistic farm scenarios
- Database uses UTF-8 character encoding
- Indexes are optimized for common query patterns

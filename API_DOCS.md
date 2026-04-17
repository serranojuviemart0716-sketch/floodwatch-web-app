# FloodWatch API Integration Guide

## Overview
The FloodWatch system now uses a REST API for all data operations, making it fully scalable and suitable for mobile apps, IoT devices, and external integrations.

## API Endpoints

### Reports
- **GET** `/api.php/reports` - Get all reports
- **POST** `/api.php/reports` - Create new report

### Alerts
- **GET** `/api.php/alerts` - Get active alerts

### Sensor Data
- **GET** `/api.php/sensors` - Get recent sensor readings
- **POST** `/api.php/sensors` - Log new sensor data

### Statistics
- **GET** `/api.php/stats` - Get dashboard statistics

## Frontend Integration

### API Client (`api-client.js`)
The frontend uses a dedicated API client that provides:

#### Functions
- `getReports()` - Fetch all reports
- `submitReport(location, severity, description, latitude, longitude)` - Submit new report
- `getAlerts()` - Fetch active alerts
- `getSensorData()` - Fetch sensor readings
- `logSensorData(...)` - Log sensor reading
- `getDashboardStats()` - Get statistics

#### Auto-Refresh
The API client automatically refreshes data at intervals:
- Reports: every 30 seconds
- Alerts: every 20 seconds
- Dashboard stats: every 15 seconds
- Sensor data: every 60 seconds

### Usage Example

```javascript
// Submit a report via API
const result = await submitReport(
    'Barangay 1, Bacolod City',
    'High',
    'River overflowing',
    10.3917,
    123.8854
);

if (result.success) {
    console.log('Report created with ID:', result.report_id);
}
```

## Database Schema

### Users Table
```sql
- id (INT PRIMARY KEY)
- username (VARCHAR UNIQUE)
- email (VARCHAR UNIQUE)
- password (VARCHAR - hashed)
- location (VARCHAR)
- phone (VARCHAR)
- role (ENUM: user, moderator, admin)
- created_at, updated_at (TIMESTAMP)
```

### Reports Table
```sql
- id (INT PRIMARY KEY)
- user_id (INT FOREIGN KEY)
- location (VARCHAR)
- latitude, longitude (DECIMAL)
- severity (ENUM: Low, Medium, High, Critical)
- description (TEXT)
- status (ENUM: pending, verified, resolved)
- image_url (VARCHAR)
- created_at, updated_at (TIMESTAMP)
```

### Alerts Table
```sql
- id (INT PRIMARY KEY)
- message (TEXT)
- alert_type (ENUM: flood, warning, info)
- priority (ENUM: low, medium, high, critical)
- is_active (BOOLEAN)
- created_at, expires_at (TIMESTAMP)
```

### Sensor Data Table
```sql
- id (INT PRIMARY KEY)
- sensor_id (VARCHAR)
- water_level, rainfall, temperature, humidity (DECIMAL)
- location (VARCHAR)
- latitude, longitude (DECIMAL)
- recorded_at (TIMESTAMP)
```

### Activity Logs Table
```sql
- id (INT PRIMARY KEY)
- user_id (INT)
- action (VARCHAR)
- details (TEXT)
- ip_address (VARCHAR)
- user_agent (TEXT)
- created_at (TIMESTAMP)
```

## PHP Files

### Core Files
- `index.html` - Main frontend
- `styles.css` - Styling
- `api-client.js` - JavaScript API client

### Backend Files
- `database.php` - Database connection & table initialization
- `auth.php` - User authentication & session management
- `handle-reports.php` - Report handling (session-based fallback)
- `api.php` - REST API endpoints
- `utils.php` - Utility functions (email, sanitization, calculations)
- `admin.php` - Admin functions (moderation, analytics)

## Setup Instructions

### 1. Database Setup
```php
// In handle-reports.php or through API
require_once 'database.php';
initializeDatabaseTables();
```

### 2. Configuration
Edit `database.php` to set your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'floodwatch_db');
```

### 3. Enable Database
The system currently uses sessions for demo mode. To use database:
1. Create MySQL database named `floodwatch_db`
2. Run table initialization
3. Update API endpoints to use database queries

## Security Considerations

1. **Input Validation** - All inputs are sanitized
2. **SQL Injection Prevention** - Prepared statements used
3. **Password Security** - Bcrypt hashing
4. **CORS** - API allows cross-origin requests
5. **Rate Limiting** - Available in `utils.php`
6. **Activity Logging** - All user actions logged

## Deployment Notes

- Ensure PHP version 7.4+
- Enable JSON extension
- Configure XAMPP MySQL
- Set proper file permissions
- Use HTTPS in production
- Set environment variables for sensitive data

## Future Enhancements

- [ ] Mobile app (React Native)
- [ ] Real-time WebSocket updates
- [ ] Machine learning flood prediction
- [ ] IoT sensor integration
- [ ] SMS/Email notifications
- [ ] Advanced analytics dashboard
- [ ] Multi-language support
- [ ] Offline mode with sync

## Support

For issues or questions, check:
1. Browser console for JavaScript errors
2. PHP error logs for backend issues
3. API responses in Network tab
4. Database connection in `database.php`

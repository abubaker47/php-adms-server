# PHP ADMS Server

A comprehensive Attendance Device Management System (ADMS) server built with PHP and SQLite for managing ZKTeco biometric attendance devices.

## Features

- **Device Management**: Auto-detect, register, and manage ZKTeco biometric devices
- **Attendance Synchronization**: Retrieve and store attendance logs from devices
- **Remote Operations**: Restart, time sync, and shutdown devices remotely
- **Branch Assignment**: Organize devices by branches/locations
- **RESTful API**: Complete REST API for device and attendance management
- **Duplicate Prevention**: Automatic detection and prevention of duplicate attendance records
- **Logging**: Comprehensive system and device event logging
- **SQLite Database**: Lightweight, file-based database for easy deployment

## Requirements

- PHP 7.4 or higher
- PHP SQLite extension (php-sqlite3)
- PHP sockets extension (php-sockets)
- Composer (for dependency management)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/abubaker47/php-adms-server.git
cd php-adms-server
```

2. Install dependencies:
```bash
composer install
```

3. Set up the database:
```bash
php database/migrate.php
```

4. Configure the server (optional):
Edit `config/config.php` to customize settings:
- Database path
- Server host and port
- API authentication
- Logging settings

5. Set environment variables (optional):
```bash
export ADMS_API_KEY="your-secure-api-key"
```

## Running the Server

### Using PHP Built-in Server

```bash
php -S localhost:8080 -t public
```

The server will be available at `http://localhost:8080`

### Using Apache/Nginx

Point your web server document root to the `public` directory.

Example Apache configuration:
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/php-adms-server/public
    
    <Directory /path/to/php-adms-server/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## API Documentation

### Authentication

All API requests require authentication using an API key in the header:

```
X-API-Key: your-api-key
```

To disable authentication for development, set `enable_auth` to `false` in `config/config.php`.

### Device Management Endpoints

#### Register a Device
```http
POST /api/devices/register
Content-Type: application/json

{
    "serial_number": "ABC123456",
    "device_name": "Main Entrance",
    "ip_address": "192.168.1.100",
    "port": 4370,
    "model": "ZKTeco K40",
    "branch_id": 1
}
```

#### Get All Devices
```http
GET /api/devices
GET /api/devices?status=online
```

#### Get Device by ID
```http
GET /api/devices/{id}
```

#### Update Device
```http
PUT /api/devices/{id}
Content-Type: application/json

{
    "device_name": "Updated Name",
    "ip_address": "192.168.1.101"
}
```

#### Delete Device
```http
DELETE /api/devices/{id}
```

### Device Operations Endpoints

#### Sync Attendance
```http
POST /api/devices/{id}/sync
```

#### Restart Device
```http
POST /api/devices/{id}/restart
```

#### Sync Device Time
```http
POST /api/devices/{id}/sync-time
```

#### Shutdown Device
```http
POST /api/devices/{id}/shutdown
```

#### Assign Device to Branch
```http
POST /api/devices/{id}/assign-branch
Content-Type: application/json

{
    "branch_id": 1
}
```

### Attendance Endpoints

#### Get Attendance by Device
```http
GET /api/attendance/device/{deviceId}?limit=100&offset=0
```

#### Get Attendance by User
```http
GET /api/attendance/user/{userId}?limit=100&offset=0
```

#### Get Attendance by Date Range
```http
GET /api/attendance/range?start_date=2024-01-01&end_date=2024-01-31&limit=1000
```

#### Create Attendance Record
```http
POST /api/attendance
Content-Type: application/json

{
    "device_id": 1,
    "user_id": "12345",
    "timestamp": "2024-01-15 09:30:00",
    "verify_mode": 1,
    "in_out_mode": 0
}
```

### Branch Management Endpoints

#### Get All Branches
```http
GET /api/branches
```

#### Create Branch
```http
POST /api/branches
Content-Type: application/json

{
    "name": "Main Office",
    "code": "MO01",
    "location": "Downtown"
}
```

### Administrative Endpoints

#### Get System Logs
```http
GET /api/admin/logs/system?limit=100&level=error
```

#### Get Device Logs
```http
GET /api/admin/logs/device/{deviceId}?limit=100
```

#### Health Check
```http
GET /api/health
```

## Database Schema

### Tables

- **devices**: Device information and connection status
- **branches**: Branch/location information
- **attendance**: Attendance records
- **device_logs**: Device-specific event logs
- **system_logs**: System-wide logs

## ZKTeco Device Configuration

1. Configure your ZKTeco device to use ADMS protocol
2. Set the device to communicate with the server IP and port
3. Register the device using the `/api/devices/register` endpoint
4. The server will automatically sync attendance logs

## Project Structure

```
php-adms-server/
├── config/              # Configuration files
│   └── config.php
├── database/            # Database files and migrations
│   └── migrate.php
├── logs/                # Application logs
├── public/              # Public web root
│   └── index.php        # Main entry point
├── src/                 # Source code
│   ├── API/             # API and routing
│   ├── Database/        # Database layer
│   ├── Models/          # Data models
│   ├── Protocol/        # ADMS protocol implementation
│   ├── Repositories/    # Data repositories
│   ├── Services/        # Business logic
│   └── Utilities/       # Utility classes
├── composer.json
└── README.md
```

## Security Considerations

1. **Change the default API key** in production
2. Use environment variables for sensitive configuration
3. Enable HTTPS for production deployments
4. Regularly backup the SQLite database
5. Monitor system logs for suspicious activity
6. Restrict network access to trusted devices only

## Troubleshooting

### Device Connection Issues

1. Verify device IP address and port configuration
2. Check network connectivity between server and device
3. Review device logs: `GET /api/admin/logs/device/{deviceId}`
4. Ensure device is configured for ADMS protocol

### Database Issues

1. Check database file permissions
2. Verify SQLite extension is installed: `php -m | grep sqlite`
3. Re-run migrations if needed: `php database/migrate.php`

### API Authentication Issues

1. Verify API key in request headers
2. Check `config/config.php` for correct API key
3. Disable authentication for testing: set `enable_auth` to `false`

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues and questions, please use the GitHub issue tracker.
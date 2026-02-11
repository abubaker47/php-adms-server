# PHP ADMS Server

A comprehensive Attendance Device Management System (ADMS) server built with PHP and SQLite for managing ZKTeco biometric attendance devices.

## 📚 Documentation

- **[Quick Start Guide](QUICKSTART.md)** - Get started in minutes
- **[API Reference](API.md)** - Complete API documentation
- **[Deployment Guide](DEPLOYMENT.md)** - Production deployment options

## Features

- **Device Management**: Auto-detect, register, and manage ZKTeco biometric devices
- **Attendance Synchronization**: Retrieve and store attendance logs from devices
- **Remote Operations**: Restart, time sync, and shutdown devices remotely
- **Branch Assignment**: Organize devices by branches/locations
- **RESTful API**: Complete REST API for device and attendance management
- **Duplicate Prevention**: Automatic detection and prevention of duplicate attendance records
- **Logging**: Comprehensive system and device event logging
- **SQLite Database**: Lightweight, file-based database for easy deployment
- **Docker Support**: Easy deployment with Docker and Docker Compose

## Requirements

- PHP 7.4 or higher
- PHP SQLite extension (php-sqlite3)
- PHP sockets extension (php-sockets)
- Composer (for dependency management)

Or use Docker (no PHP installation required)

## Quick Start

### Option 1: Traditional Installation

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

4. Start the server:
```bash
php -S localhost:8080 -t public
```

The server will be available at `http://localhost:8080`

### Option 2: Docker

```bash
# Set your API key
export ADMS_API_KEY="your-secure-api-key"

# Start with Docker Compose
docker-compose up -d
```

See [QUICKSTART.md](QUICKSTART.md) for detailed setup instructions.

## Running the Server

### Using PHP Built-in Server

```bash
php -S localhost:8080 -t public
```

The server will be available at `http://localhost:8080`

### Using Docker

```bash
docker-compose up -d
```

### Using Apache/Nginx

Point your web server document root to the `public` directory.

See [DEPLOYMENT.md](DEPLOYMENT.md) for production deployment options.

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

For complete API documentation, see **[API.md](API.md)**.

### Quick API Examples

All API requests require authentication using an API key:

```bash
curl -H "X-API-Key: your-api-key" http://localhost:8080/api/health
```

## API Overview

For complete API documentation, see **[API.md](API.md)**.

### Key Endpoints

- **Device Management**: `/api/devices/*`
- **Attendance**: `/api/attendance/*`
- **Branch Management**: `/api/branches/*`
- **Administration**: `/api/admin/*`
- **Health Check**: `/api/health`

### Quick Example

```bash
# Register a device
curl -X POST -H "X-API-Key: change_this_in_production" \
  -H "Content-Type: application/json" \
  -d '{"serial_number":"DEV001","device_name":"Main Entrance","ip_address":"192.168.1.100"}' \
  http://localhost:8080/api/devices/register
```

## Testing

Run the automated API test suite:

```bash
./test-api.sh
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
├── database/            # Database and migrations
├── logs/                # Application logs
├── public/              # Web root (index.php)
├── src/                 # Source code
│   ├── API/             # Routing and middleware
│   ├── Database/        # Database layer
│   ├── Models/          # Data models
│   ├── Protocol/        # ADMS protocol
│   ├── Repositories/    # Data access
│   ├── Services/        # Business logic
│   └── Utilities/       # Helpers and logging
├── API.md               # API documentation
├── DEPLOYMENT.md        # Deployment guide
├── QUICKSTART.md        # Quick start guide
└── README.md            # This file
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
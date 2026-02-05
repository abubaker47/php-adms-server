# Quick Start Guide

This guide will help you get the ADMS server up and running quickly.

## Prerequisites

- PHP 7.4 or higher
- PHP extensions: pdo, pdo_sqlite, sockets
- Composer

## Quick Setup

1. **Install dependencies:**
```bash
composer install
```

2. **Initialize the database:**
```bash
php database/migrate.php
```

3. **Start the server:**
```bash
php -S localhost:8080 -t public
```

The server will be running at `http://localhost:8080`

## Testing the API

### Health Check
```bash
curl -H "X-API-Key: change_this_in_production" http://localhost:8080/api/health
```

### Register a Device
```bash
curl -X POST -H "X-API-Key: change_this_in_production" \
  -H "Content-Type: application/json" \
  -d '{
    "serial_number": "DEVICE001",
    "device_name": "Main Entrance",
    "ip_address": "192.168.1.100",
    "port": 4370,
    "model": "ZKTeco K40"
  }' \
  http://localhost:8080/api/devices/register
```

### Create a Branch
```bash
curl -X POST -H "X-API-Key: change_this_in_production" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Main Office",
    "code": "HQ001",
    "location": "Downtown"
  }' \
  http://localhost:8080/api/branches
```

### Assign Device to Branch
```bash
curl -X POST -H "X-API-Key: change_this_in_production" \
  -H "Content-Type: application/json" \
  -d '{"branch_id": 1}' \
  http://localhost:8080/api/devices/1/assign-branch
```

### Create Attendance Record
```bash
curl -X POST -H "X-API-Key: change_this_in_production" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": 1,
    "user_id": "EMP001",
    "timestamp": "2024-01-15 09:00:00",
    "verify_mode": 1,
    "in_out_mode": 0
  }' \
  http://localhost:8080/api/attendance
```

### Get Attendance Records
```bash
curl -H "X-API-Key: change_this_in_production" \
  http://localhost:8080/api/attendance/device/1
```

### View Logs
```bash
# System logs
curl -H "X-API-Key: change_this_in_production" \
  http://localhost:8080/api/admin/logs/system?limit=10

# Device logs
curl -H "X-API-Key: change_this_in_production" \
  http://localhost:8080/api/admin/logs/device/1
```

## Common Operations

### Sync Attendance from Device
```bash
curl -X POST -H "X-API-Key: change_this_in_production" \
  http://localhost:8080/api/devices/1/sync
```

### Restart Device
```bash
curl -X POST -H "X-API-Key: change_this_in_production" \
  http://localhost:8080/api/devices/1/restart
```

### Sync Device Time
```bash
curl -X POST -H "X-API-Key: change_this_in_production" \
  http://localhost:8080/api/devices/1/sync-time
```

## Configuration

Edit `config/config.php` to customize:
- Database location
- Server settings
- API authentication
- Logging level

## Production Deployment

1. **Set a secure API key:**
```bash
export ADMS_API_KEY="your-secure-random-key-here"
```

2. **Update config/config.php** to read from environment:
```php
'api_key' => getenv('ADMS_API_KEY') ?: 'default_key',
```

3. **Use a production web server** (Apache/Nginx) instead of PHP's built-in server

4. **Enable HTTPS** for all communication

5. **Set appropriate file permissions:**
```bash
chmod 755 database/
chmod 644 database/adms.db
chmod 755 logs/
```

## Troubleshooting

### "Failed to create socket" error
- Ensure PHP sockets extension is installed: `php -m | grep sockets`

### "Database connection failed"
- Check write permissions on `database/` directory
- Verify SQLite extension is installed: `php -m | grep sqlite`

### Authentication fails
- Verify API key matches the one in `config/config.php`
- Check the `X-API-Key` header is being sent correctly

## Next Steps

- Configure your ZKTeco devices to communicate with the server
- Set up automated attendance synchronization
- Create frontend application to visualize data
- Set up automated backups of the SQLite database

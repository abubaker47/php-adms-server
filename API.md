# API Reference

Complete API documentation for the ADMS Server.

## Base URL

```
http://localhost:8080
```

## Authentication

All API endpoints require authentication using an API key in the request header:

```
X-API-Key: your-api-key-here
```

Default API key: `change_this_in_production`

To disable authentication (development only), edit `config/config.php`:
```php
'enable_auth' => false,
```

## Response Format

All responses are in JSON format.

### Success Response
```json
{
  "success": true,
  "data": {...}
}
```

### Error Response
```json
{
  "error": "Error message description"
}
```

## Endpoints

### Device Management

#### POST /api/devices/register
Register a new device in the system.

**Request Body:**
```json
{
  "serial_number": "ABC123456",     // Required, unique
  "device_name": "Main Entrance",   // Optional
  "ip_address": "192.168.1.100",    // Optional
  "port": 4370,                     // Optional, default: 4370
  "model": "ZKTeco K40",            // Optional
  "firmware_version": "v2.4.1",     // Optional
  "branch_id": 1                    // Optional
}
```

**Response:**
```json
{
  "success": true,
  "device": {
    "id": 1,
    "serial_number": "ABC123456",
    "device_name": "Main Entrance",
    "branch_id": null,
    "ip_address": "192.168.1.100",
    "port": 4370,
    "model": "ZKTeco K40",
    "firmware_version": "v2.4.1",
    "status": "offline",
    "last_connected_at": null,
    "registered_at": "2024-01-15 10:30:00",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

#### GET /api/devices
Get list of all devices.

**Query Parameters:**
- `status` (optional): Filter by device status (online, offline)

**Response:**
```json
{
  "success": true,
  "devices": [...],
  "count": 5
}
```

#### GET /api/devices/{id}
Get details of a specific device.

**Response:**
```json
{
  "success": true,
  "device": {...}
}
```

#### PUT /api/devices/{id}
Update device information.

**Request Body:**
```json
{
  "device_name": "Updated Name",
  "ip_address": "192.168.1.101",
  "firmware_version": "v2.5.0"
}
```

**Response:**
```json
{
  "success": true,
  "device": {...}
}
```

#### DELETE /api/devices/{id}
Delete a device from the system.

**Response:**
```json
{
  "success": true,
  "message": "Device deleted successfully"
}
```

### Device Operations

#### POST /api/devices/{id}/sync
Synchronize attendance data from the device.

**Response:**
```json
{
  "success": true,
  "total_retrieved": 150,
  "inserted": 145,
  "duplicates": 5
}
```

#### POST /api/devices/{id}/restart
Send restart command to the device.

**Response:**
```json
{
  "success": true,
  "message": "Device restart command sent"
}
```

#### POST /api/devices/{id}/sync-time
Synchronize device time with server time.

**Response:**
```json
{
  "success": true,
  "message": "Device time synchronized"
}
```

#### POST /api/devices/{id}/shutdown
Send shutdown command to the device.

**Response:**
```json
{
  "success": true,
  "message": "Device shutdown command sent"
}
```

#### POST /api/devices/{id}/assign-branch
Assign device to a specific branch.

**Request Body:**
```json
{
  "branch_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "device": {...}
}
```

### Attendance Management

#### GET /api/attendance/device/{deviceId}
Get attendance records for a specific device.

**Query Parameters:**
- `limit` (optional): Number of records to return (default: 100, max: 1000)
- `offset` (optional): Pagination offset (default: 0)

**Response:**
```json
{
  "success": true,
  "records": [
    {
      "id": 1,
      "device_id": 1,
      "user_id": "EMP001",
      "timestamp": "2024-01-15 09:00:00",
      "verify_mode": 1,
      "in_out_mode": 0,
      "work_code": null,
      "raw_data": null,
      "synced_at": "2024-01-15 09:05:00",
      "created_at": "2024-01-15 09:05:00"
    }
  ],
  "count": 1
}
```

#### GET /api/attendance/user/{userId}
Get attendance records for a specific user.

**Query Parameters:**
- `limit` (optional): Number of records (default: 100)
- `offset` (optional): Pagination offset (default: 0)

**Response:**
```json
{
  "success": true,
  "records": [...],
  "count": 10
}
```

#### GET /api/attendance/range
Get attendance records within a date range.

**Query Parameters:**
- `start_date` (required): Start date (YYYY-MM-DD format)
- `end_date` (required): End date (YYYY-MM-DD format)
- `limit` (optional): Number of records (default: 1000)
- `offset` (optional): Pagination offset (default: 0)

**Example:**
```
GET /api/attendance/range?start_date=2024-01-01&end_date=2024-01-31&limit=500
```

**Response:**
```json
{
  "success": true,
  "records": [...],
  "count": 250
}
```

#### POST /api/attendance
Create a manual attendance record.

**Request Body:**
```json
{
  "device_id": 1,
  "user_id": "EMP001",
  "timestamp": "2024-01-15 09:00:00",
  "verify_mode": 1,      // Optional: 1=fingerprint, 2=face, etc.
  "in_out_mode": 0,      // Optional: 0=in, 1=out
  "work_code": null      // Optional
}
```

**Response:**
```json
{
  "success": true,
  "record": {...}
}
```

**Duplicate Prevention:**
If the same device_id, user_id, and timestamp already exist:
```json
{
  "success": false,
  "message": "Duplicate record - not inserted"
}
```

### Branch Management

#### GET /api/branches
Get all branches.

**Response:**
```json
{
  "success": true,
  "branches": [
    {
      "id": 1,
      "name": "Main Office",
      "code": "HQ001",
      "location": "Downtown",
      "created_at": "2024-01-15 10:00:00",
      "updated_at": "2024-01-15 10:00:00"
    }
  ],
  "count": 1
}
```

#### POST /api/branches
Create a new branch.

**Request Body:**
```json
{
  "name": "Main Office",      // Required
  "code": "HQ001",            // Required, unique
  "location": "Downtown"      // Optional
}
```

**Response:**
```json
{
  "success": true,
  "branch": {...}
}
```

### Administrative Endpoints

#### GET /api/admin/logs/system
Get system-wide logs.

**Query Parameters:**
- `limit` (optional): Number of logs (default: 100)
- `level` (optional): Filter by level (debug, info, warning, error)

**Response:**
```json
{
  "success": true,
  "logs": [
    {
      "id": 1,
      "level": "info",
      "message": "Device registered",
      "context": "{\"device_id\":1}",
      "created_at": "2024-01-15 10:00:00"
    }
  ],
  "count": 1
}
```

#### GET /api/admin/logs/device/{deviceId}
Get logs for a specific device.

**Query Parameters:**
- `limit` (optional): Number of logs (default: 100)

**Response:**
```json
{
  "success": true,
  "logs": [
    {
      "id": 1,
      "device_id": 1,
      "event_type": "registered",
      "message": "Device registered successfully",
      "details": null,
      "created_at": "2024-01-15 10:00:00"
    }
  ],
  "count": 1
}
```

#### GET /api/health
Health check endpoint.

**Response:**
```json
{
  "success": true,
  "status": "healthy",
  "timestamp": "2024-01-15 10:00:00"
}
```

## Error Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request (missing required fields, validation error)
- `401` - Unauthorized (invalid or missing API key)
- `404` - Not Found (resource doesn't exist)
- `500` - Internal Server Error

## Field Reference

### Device Status Values
- `online` - Device is connected and responsive
- `offline` - Device is not connected

### Verify Mode Values
- `1` - Fingerprint
- `2` - Face recognition
- `3` - Password
- `4` - Card

### In/Out Mode Values
- `0` - Check in
- `1` - Check out
- `2` - Break out
- `3` - Break in
- `4` - Overtime in
- `5` - Overtime out

## Rate Limiting

Currently, there is no rate limiting implemented. For production use, consider implementing rate limiting based on your requirements.

## Examples

See `test-api.sh` for comprehensive testing examples covering all endpoints.

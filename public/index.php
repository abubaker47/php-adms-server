<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ADMS\Database\Database;
use ADMS\Database\Schema;
use ADMS\Repositories\DeviceRepository;
use ADMS\Repositories\AttendanceRepository;
use ADMS\Repositories\BranchRepository;
use ADMS\Services\DeviceService;
use ADMS\Services\AttendanceService;
use ADMS\Protocol\ADMSProtocol;
use ADMS\Utilities\Logger;
use ADMS\API\Router;
use ADMS\API\Middleware;

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// Initialize database
$db = Database::getInstance($config['database']);

// Run migrations
$schema = new Schema($db);
$schema->migrate();

// Initialize logger
$logger = new Logger($db, $config['logging']);

// Initialize repositories
$deviceRepo = new DeviceRepository($db);
$attendanceRepo = new AttendanceRepository($db);
$branchRepo = new BranchRepository($db);

// Initialize protocol
$protocol = new ADMSProtocol();

// Initialize services
$deviceService = new DeviceService($deviceRepo, $attendanceRepo, $protocol, $logger);
$attendanceService = new AttendanceService($attendanceRepo, $logger);

// Initialize router
$router = new Router();
$middleware = new Middleware($config);

// Apply middleware
$router->use([$middleware, 'cors']);
$router->use([$middleware, 'authenticate']);

// ============================================
// Device Management Endpoints
// ============================================

// Register a new device
$router->post('/api/devices/register', function($params) use ($router, $deviceService, $logger) {
    try {
        $data = $router->getRequestBody();
        
        if (!isset($data['serial_number'])) {
            return $router->sendError('serial_number is required', 400);
        }

        $device = $deviceService->registerDevice($data);
        $router->sendResponse([
            'success' => true,
            'device' => $device->toArray()
        ], 201);
    } catch (\Exception $e) {
        $logger->error('Device registration failed', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Get all devices
$router->get('/api/devices', function($params) use ($router, $deviceService, $logger) {
    try {
        $queryParams = $router->getQueryParams();
        $status = $queryParams['status'] ?? null;
        
        $devices = $deviceService->getDeviceList($status);
        $deviceArray = array_map(function($device) {
            return $device->toArray();
        }, $devices);
        
        $router->sendResponse([
            'success' => true,
            'devices' => $deviceArray,
            'count' => count($deviceArray)
        ]);
    } catch (\Exception $e) {
        $logger->error('Failed to get devices', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Get device by ID
$router->get('/api/devices/{id}', function($params) use ($router, $deviceService, $logger) {
    try {
        $device = $deviceService->getDevice($params['id']);
        
        if (!$device) {
            return $router->sendError('Device not found', 404);
        }
        
        $router->sendResponse([
            'success' => true,
            'device' => $device->toArray()
        ]);
    } catch (\Exception $e) {
        $logger->error('Failed to get device', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Update device
$router->put('/api/devices/{id}', function($params) use ($router, $deviceService, $logger) {
    try {
        $data = $router->getRequestBody();
        $device = $deviceService->updateDevice($params['id'], $data);
        
        if (!$device) {
            return $router->sendError('Device not found', 404);
        }
        
        $router->sendResponse([
            'success' => true,
            'device' => $device->toArray()
        ]);
    } catch (\Exception $e) {
        $logger->error('Failed to update device', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Delete device
$router->delete('/api/devices/{id}', function($params) use ($router, $deviceService, $logger) {
    try {
        $result = $deviceService->deleteDevice($params['id']);
        
        $router->sendResponse([
            'success' => true,
            'message' => 'Device deleted successfully'
        ]);
    } catch (\Exception $e) {
        $logger->error('Failed to delete device', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// ============================================
// Device Operations Endpoints
// ============================================

// Sync attendance from device
$router->post('/api/devices/{id}/sync', function($params) use ($router, $deviceService, $logger) {
    try {
        $result = $deviceService->syncAttendance($params['id']);
        $router->sendResponse($result);
    } catch (\Exception $e) {
        $logger->error('Sync failed', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Restart device
$router->post('/api/devices/{id}/restart', function($params) use ($router, $deviceService, $logger) {
    try {
        $result = $deviceService->restartDevice($params['id']);
        $router->sendResponse([
            'success' => true,
            'message' => 'Device restart command sent'
        ]);
    } catch (\Exception $e) {
        $logger->error('Restart failed', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Sync device time
$router->post('/api/devices/{id}/sync-time', function($params) use ($router, $deviceService, $logger) {
    try {
        $result = $deviceService->syncTime($params['id']);
        $router->sendResponse([
            'success' => true,
            'message' => 'Device time synchronized'
        ]);
    } catch (\Exception $e) {
        $logger->error('Time sync failed', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Shutdown device
$router->post('/api/devices/{id}/shutdown', function($params) use ($router, $deviceService, $logger) {
    try {
        $result = $deviceService->shutdownDevice($params['id']);
        $router->sendResponse([
            'success' => true,
            'message' => 'Device shutdown command sent'
        ]);
    } catch (\Exception $e) {
        $logger->error('Shutdown failed', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Assign device to branch
$router->post('/api/devices/{id}/assign-branch', function($params) use ($router, $deviceService, $logger) {
    try {
        $data = $router->getRequestBody();
        
        if (!isset($data['branch_id'])) {
            return $router->sendError('branch_id is required', 400);
        }
        
        $device = $deviceService->assignToBranch($params['id'], $data['branch_id']);
        $router->sendResponse([
            'success' => true,
            'device' => $device->toArray()
        ]);
    } catch (\Exception $e) {
        $logger->error('Branch assignment failed', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// ============================================
// Attendance Endpoints
// ============================================

// Get attendance by device
$router->get('/api/attendance/device/{deviceId}', function($params) use ($router, $attendanceService, $logger) {
    try {
        $queryParams = $router->getQueryParams();
        $limit = $queryParams['limit'] ?? 100;
        $offset = $queryParams['offset'] ?? 0;
        
        $records = $attendanceService->getAttendanceByDevice($params['deviceId'], $limit, $offset);
        $recordArray = array_map(function($record) {
            return $record->toArray();
        }, $records);
        
        $router->sendResponse([
            'success' => true,
            'records' => $recordArray,
            'count' => count($recordArray)
        ]);
    } catch (\Exception $e) {
        $logger->error('Failed to get attendance', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Get attendance by user
$router->get('/api/attendance/user/{userId}', function($params) use ($router, $attendanceService, $logger) {
    try {
        $queryParams = $router->getQueryParams();
        $limit = $queryParams['limit'] ?? 100;
        $offset = $queryParams['offset'] ?? 0;
        
        $records = $attendanceService->getAttendanceByUser($params['userId'], $limit, $offset);
        $recordArray = array_map(function($record) {
            return $record->toArray();
        }, $records);
        
        $router->sendResponse([
            'success' => true,
            'records' => $recordArray,
            'count' => count($recordArray)
        ]);
    } catch (\Exception $e) {
        $logger->error('Failed to get attendance', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Get attendance by date range
$router->get('/api/attendance/range', function($params) use ($router, $attendanceService, $logger) {
    try {
        $queryParams = $router->getQueryParams();
        
        if (!isset($queryParams['start_date']) || !isset($queryParams['end_date'])) {
            return $router->sendError('start_date and end_date are required', 400);
        }
        
        $limit = $queryParams['limit'] ?? 1000;
        $offset = $queryParams['offset'] ?? 0;
        
        $records = $attendanceService->getAttendanceByDateRange(
            $queryParams['start_date'],
            $queryParams['end_date'],
            $limit,
            $offset
        );
        
        $recordArray = array_map(function($record) {
            return $record->toArray();
        }, $records);
        
        $router->sendResponse([
            'success' => true,
            'records' => $recordArray,
            'count' => count($recordArray)
        ]);
    } catch (\Exception $e) {
        $logger->error('Failed to get attendance', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Create attendance record
$router->post('/api/attendance', function($params) use ($router, $attendanceService, $logger) {
    try {
        $data = $router->getRequestBody();
        $record = $attendanceService->createAttendanceRecord($data);
        
        if (!$record) {
            return $router->sendResponse([
                'success' => false,
                'message' => 'Duplicate record - not inserted'
            ]);
        }
        
        $router->sendResponse([
            'success' => true,
            'record' => $record->toArray()
        ], 201);
    } catch (\Exception $e) {
        $logger->error('Failed to create attendance', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// ============================================
// Branch Management Endpoints
// ============================================

// Get all branches
$router->get('/api/branches', function($params) use ($router, $branchRepo, $logger) {
    try {
        $branches = $branchRepo->findAll();
        $branchArray = array_map(function($branch) {
            return $branch->toArray();
        }, $branches);
        
        $router->sendResponse([
            'success' => true,
            'branches' => $branchArray,
            'count' => count($branchArray)
        ]);
    } catch (\Exception $e) {
        $logger->error('Failed to get branches', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// Create branch
$router->post('/api/branches', function($params) use ($router, $branchRepo, $logger) {
    try {
        $data = $router->getRequestBody();
        
        if (!isset($data['name']) || !isset($data['code'])) {
            return $router->sendError('name and code are required', 400);
        }
        
        $branch = $branchRepo->create($data);
        $router->sendResponse([
            'success' => true,
            'branch' => $branch->toArray()
        ], 201);
    } catch (\Exception $e) {
        $logger->error('Failed to create branch', ['error' => $e->getMessage()]);
        $router->sendError($e->getMessage(), 500);
    }
});

// ============================================
// Administrative Endpoints
// ============================================

// Get system logs
$router->get('/api/admin/logs/system', function($params) use ($router, $logger) {
    try {
        $queryParams = $router->getQueryParams();
        $limit = $queryParams['limit'] ?? 100;
        $level = $queryParams['level'] ?? null;
        
        $logs = $logger->getSystemLogs($limit, $level);
        $router->sendResponse([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs)
        ]);
    } catch (\Exception $e) {
        $router->sendError($e->getMessage(), 500);
    }
});

// Get device logs
$router->get('/api/admin/logs/device/{deviceId}', function($params) use ($router, $logger) {
    try {
        $queryParams = $router->getQueryParams();
        $limit = $queryParams['limit'] ?? 100;
        
        $logs = $logger->getDeviceLogs($params['deviceId'], $limit);
        $router->sendResponse([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs)
        ]);
    } catch (\Exception $e) {
        $router->sendError($e->getMessage(), 500);
    }
});

// Health check
$router->get('/api/health', function($params) use ($router) {
    $router->sendResponse([
        'success' => true,
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Dispatch the request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$router->dispatch($method, $uri);

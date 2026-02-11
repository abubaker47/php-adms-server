<?php

namespace ADMS\Services;

use ADMS\Repositories\DeviceRepository;
use ADMS\Repositories\AttendanceRepository;
use ADMS\Protocol\ADMSProtocol;
use ADMS\Utilities\Logger;

class DeviceService
{
    private $deviceRepo;
    private $attendanceRepo;
    private $protocol;
    private $logger;

    public function __construct(
        DeviceRepository $deviceRepo,
        AttendanceRepository $attendanceRepo,
        ADMSProtocol $protocol,
        Logger $logger
    ) {
        $this->deviceRepo = $deviceRepo;
        $this->attendanceRepo = $attendanceRepo;
        $this->protocol = $protocol;
        $this->logger = $logger;
    }

    public function registerDevice($data)
    {
        // Check if device already exists
        $existing = $this->deviceRepo->findBySerialNumber($data['serial_number']);
        if ($existing) {
            $this->logger->info("Device already registered", ['serial' => $data['serial_number']]);
            return $existing;
        }

        // Register new device
        $device = $this->deviceRepo->create($data);
        $this->logger->info("New device registered", [
            'id' => $device->id,
            'serial' => $device->serial_number
        ]);
        
        $this->logger->logDeviceEvent(
            $device->id,
            'registered',
            'Device registered successfully'
        );

        return $device;
    }

    public function updateDeviceStatus($deviceId, $status)
    {
        $this->deviceRepo->updateStatus($deviceId, $status);
        $this->logger->logDeviceEvent(
            $deviceId,
            'status_change',
            "Device status changed to: $status"
        );
    }

    public function assignToBranch($deviceId, $branchId)
    {
        $device = $this->deviceRepo->update($deviceId, ['branch_id' => $branchId]);
        $this->logger->logDeviceEvent(
            $deviceId,
            'branch_assigned',
            "Device assigned to branch ID: $branchId"
        );
        return $device;
    }

    public function syncAttendance($deviceId)
    {
        $device = $this->deviceRepo->findById($deviceId);
        if (!$device) {
            throw new \Exception("Device not found");
        }

        if (!$device->ip_address) {
            throw new \Exception("Device IP address not configured");
        }

        try {
            // Connect to device
            $this->protocol->connect($device->ip_address, $device->port);
            
            // Get attendance logs
            $logs = $this->protocol->getAttendanceLogs($device->ip_address, $device->port);
            
            // Disconnect
            $this->protocol->disconnect();

            // Save attendance records
            $inserted = 0;
            foreach ($logs as $log) {
                $log['device_id'] = $deviceId;
                $result = $this->attendanceRepo->create($log);
                if ($result) {
                    $inserted++;
                }
            }

            $this->updateDeviceStatus($deviceId, 'online');
            $this->logger->logDeviceEvent(
                $deviceId,
                'sync_completed',
                "Attendance sync completed. Retrieved: " . count($logs) . ", Inserted: $inserted"
            );

            return [
                'success' => true,
                'total_retrieved' => count($logs),
                'inserted' => $inserted,
                'duplicates' => count($logs) - $inserted
            ];

        } catch (\Exception $e) {
            $this->updateDeviceStatus($deviceId, 'offline');
            $this->logger->error("Attendance sync failed", [
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function restartDevice($deviceId)
    {
        $device = $this->deviceRepo->findById($deviceId);
        if (!$device || !$device->ip_address) {
            throw new \Exception("Device not found or IP not configured");
        }

        try {
            $this->protocol->connect($device->ip_address, $device->port);
            $result = $this->protocol->restartDevice($device->ip_address, $device->port);
            $this->protocol->disconnect();

            if ($result) {
                $this->logger->logDeviceEvent($deviceId, 'restart', 'Device restart command sent');
                return true;
            }

            throw new \Exception("Restart command failed");
        } catch (\Exception $e) {
            $this->logger->error("Device restart failed", [
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function syncTime($deviceId)
    {
        $device = $this->deviceRepo->findById($deviceId);
        if (!$device || !$device->ip_address) {
            throw new \Exception("Device not found or IP not configured");
        }

        try {
            $this->protocol->connect($device->ip_address, $device->port);
            $result = $this->protocol->setTime(
                $device->ip_address,
                $device->port,
                time()
            );
            $this->protocol->disconnect();

            if ($result) {
                $this->logger->logDeviceEvent($deviceId, 'time_sync', 'Device time synchronized');
                return true;
            }

            throw new \Exception("Time sync command failed");
        } catch (\Exception $e) {
            $this->logger->error("Time sync failed", [
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function shutdownDevice($deviceId)
    {
        $device = $this->deviceRepo->findById($deviceId);
        if (!$device || !$device->ip_address) {
            throw new \Exception("Device not found or IP not configured");
        }

        try {
            $this->protocol->connect($device->ip_address, $device->port);
            $result = $this->protocol->powerOff($device->ip_address, $device->port);
            $this->protocol->disconnect();

            if ($result) {
                $this->logger->logDeviceEvent($deviceId, 'shutdown', 'Device shutdown command sent');
                return true;
            }

            throw new \Exception("Shutdown command failed");
        } catch (\Exception $e) {
            $this->logger->error("Device shutdown failed", [
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getDeviceList($status = null)
    {
        return $this->deviceRepo->findAll($status);
    }

    public function getDevice($deviceId)
    {
        return $this->deviceRepo->findById($deviceId);
    }

    public function updateDevice($deviceId, $data)
    {
        return $this->deviceRepo->update($deviceId, $data);
    }

    public function deleteDevice($deviceId)
    {
        $this->logger->logDeviceEvent($deviceId, 'deleted', 'Device removed from system');
        return $this->deviceRepo->delete($deviceId);
    }
}

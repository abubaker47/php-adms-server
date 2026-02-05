<?php

namespace ADMS\Services;

use ADMS\Repositories\AttendanceRepository;
use ADMS\Utilities\Logger;

class AttendanceService
{
    private $attendanceRepo;
    private $logger;

    public function __construct(AttendanceRepository $attendanceRepo, Logger $logger)
    {
        $this->attendanceRepo = $attendanceRepo;
        $this->logger = $logger;
    }

    public function getAttendanceByDevice($deviceId, $limit = 100, $offset = 0)
    {
        return $this->attendanceRepo->findByDevice($deviceId, $limit, $offset);
    }

    public function getAttendanceByUser($userId, $limit = 100, $offset = 0)
    {
        return $this->attendanceRepo->findByUser($userId, $limit, $offset);
    }

    public function getAttendanceByDateRange($startDate, $endDate, $limit = 1000, $offset = 0)
    {
        return $this->attendanceRepo->findByDateRange($startDate, $endDate, $limit, $offset);
    }

    public function getAttendanceCount($deviceId)
    {
        return $this->attendanceRepo->countByDevice($deviceId);
    }

    public function createAttendanceRecord($data)
    {
        // Validate required fields
        if (!isset($data['device_id']) || !isset($data['user_id']) || !isset($data['timestamp'])) {
            throw new \Exception('Missing required fields: device_id, user_id, timestamp');
        }

        // Check for duplicates
        if ($this->attendanceRepo->exists($data['device_id'], $data['user_id'], $data['timestamp'])) {
            $this->logger->warning('Duplicate attendance record prevented', $data);
            return null;
        }

        $record = $this->attendanceRepo->create($data);
        if ($record) {
            $this->logger->info('Attendance record created', [
                'id' => $record->id,
                'device_id' => $record->device_id,
                'user_id' => $record->user_id
            ]);
        }

        return $record;
    }

    public function bulkCreateAttendance($records)
    {
        try {
            $inserted = $this->attendanceRepo->bulkInsert($records);
            $this->logger->info('Bulk attendance insert completed', [
                'total' => count($records),
                'inserted' => $inserted
            ]);

            return [
                'success' => true,
                'total' => count($records),
                'inserted' => $inserted,
                'duplicates' => count($records) - $inserted
            ];
        } catch (\Exception $e) {
            $this->logger->error('Bulk attendance insert failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

<?php

namespace ADMS\Repositories;

use ADMS\Database\Database;
use ADMS\Models\Attendance;

class AttendanceRepository
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($data)
    {
        $sql = "INSERT INTO attendance (
            device_id, user_id, timestamp, verify_mode, 
            in_out_mode, work_code, raw_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $this->db->execute($sql, [
                $data['device_id'],
                $data['user_id'],
                $data['timestamp'],
                $data['verify_mode'] ?? null,
                $data['in_out_mode'] ?? null,
                $data['work_code'] ?? null,
                $data['raw_data'] ?? null
            ]);

            return $this->findById($this->db->lastInsertId());
        } catch (\Exception $e) {
            // Handle duplicate entry (unique constraint violation)
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                return null; // Record already exists
            }
            throw $e;
        }
    }

    public function findById($id)
    {
        $stmt = $this->db->query(
            "SELECT * FROM attendance WHERE id = ?",
            [$id]
        );
        $data = $stmt->fetch();
        return $data ? new Attendance($data) : null;
    }

    public function findByDevice($deviceId, $limit = 100, $offset = 0)
    {
        $stmt = $this->db->query(
            "SELECT * FROM attendance WHERE device_id = ? ORDER BY timestamp DESC LIMIT ? OFFSET ?",
            [$deviceId, $limit, $offset]
        );
        
        $records = [];
        while ($data = $stmt->fetch()) {
            $records[] = new Attendance($data);
        }
        return $records;
    }

    public function findByUser($userId, $limit = 100, $offset = 0)
    {
        $stmt = $this->db->query(
            "SELECT * FROM attendance WHERE user_id = ? ORDER BY timestamp DESC LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
        
        $records = [];
        while ($data = $stmt->fetch()) {
            $records[] = new Attendance($data);
        }
        return $records;
    }

    public function findByDateRange($startDate, $endDate, $limit = 1000, $offset = 0)
    {
        $stmt = $this->db->query(
            "SELECT * FROM attendance 
             WHERE timestamp BETWEEN ? AND ? 
             ORDER BY timestamp DESC 
             LIMIT ? OFFSET ?",
            [$startDate, $endDate, $limit, $offset]
        );
        
        $records = [];
        while ($data = $stmt->fetch()) {
            $records[] = new Attendance($data);
        }
        return $records;
    }

    public function countByDevice($deviceId)
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) as count FROM attendance WHERE device_id = ?",
            [$deviceId]
        );
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function exists($deviceId, $userId, $timestamp)
    {
        $stmt = $this->db->query(
            "SELECT id FROM attendance WHERE device_id = ? AND user_id = ? AND timestamp = ?",
            [$deviceId, $userId, $timestamp]
        );
        return $stmt->fetch() !== false;
    }

    public function bulkInsert($records)
    {
        $this->db->beginTransaction();
        $inserted = 0;
        
        try {
            foreach ($records as $record) {
                $result = $this->create($record);
                if ($result !== null) {
                    $inserted++;
                }
            }
            $this->db->commit();
            return $inserted;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}

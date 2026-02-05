<?php

namespace ADMS\Repositories;

use ADMS\Database\Database;
use ADMS\Models\Device;

class DeviceRepository
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findBySerialNumber($serialNumber)
    {
        $stmt = $this->db->query(
            "SELECT * FROM devices WHERE serial_number = ?",
            [$serialNumber]
        );
        $data = $stmt->fetch();
        return $data ? new Device($data) : null;
    }

    public function findById($id)
    {
        $stmt = $this->db->query(
            "SELECT * FROM devices WHERE id = ?",
            [$id]
        );
        $data = $stmt->fetch();
        return $data ? new Device($data) : null;
    }

    public function findAll($status = null)
    {
        if ($status) {
            $stmt = $this->db->query(
                "SELECT * FROM devices WHERE status = ? ORDER BY created_at DESC",
                [$status]
            );
        } else {
            $stmt = $this->db->query("SELECT * FROM devices ORDER BY created_at DESC");
        }
        
        $devices = [];
        while ($data = $stmt->fetch()) {
            $devices[] = new Device($data);
        }
        return $devices;
    }

    public function create($data)
    {
        $sql = "INSERT INTO devices (
            serial_number, device_name, branch_id, ip_address, port, 
            model, firmware_version, status, registered_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))";
        
        $this->db->execute($sql, [
            $data['serial_number'],
            $data['device_name'] ?? null,
            $data['branch_id'] ?? null,
            $data['ip_address'] ?? null,
            $data['port'] ?? 4370,
            $data['model'] ?? null,
            $data['firmware_version'] ?? null,
            $data['status'] ?? 'offline'
        ]);

        return $this->findById($this->db->lastInsertId());
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'serial_number') {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }

        $fields[] = "updated_at = datetime('now')";
        $params[] = $id;

        $sql = "UPDATE devices SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->execute($sql, $params);

        return $this->findById($id);
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE devices SET 
                status = ?, 
                last_connected_at = datetime('now'),
                updated_at = datetime('now')
                WHERE id = ?";
        $this->db->execute($sql, [$status, $id]);
    }

    public function delete($id)
    {
        return $this->db->execute("DELETE FROM devices WHERE id = ?", [$id]);
    }

    public function getByBranch($branchId)
    {
        $stmt = $this->db->query(
            "SELECT * FROM devices WHERE branch_id = ? ORDER BY created_at DESC",
            [$branchId]
        );
        
        $devices = [];
        while ($data = $stmt->fetch()) {
            $devices[] = new Device($data);
        }
        return $devices;
    }
}

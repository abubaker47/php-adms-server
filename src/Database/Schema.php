<?php

namespace ADMS\Database;

class Schema
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function migrate()
    {
        $this->createDevicesTable();
        $this->createBranchesTable();
        $this->createAttendanceTable();
        $this->createDeviceLogsTable();
        $this->createSystemLogsTable();
    }

    private function createBranchesTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS branches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            code TEXT UNIQUE NOT NULL,
            location TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->execute($sql);
    }

    private function createDevicesTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            serial_number TEXT UNIQUE NOT NULL,
            device_name TEXT,
            branch_id INTEGER,
            ip_address TEXT,
            port INTEGER DEFAULT 4370,
            model TEXT,
            firmware_version TEXT,
            status TEXT DEFAULT 'offline',
            last_connected_at TEXT,
            registered_at TEXT DEFAULT CURRENT_TIMESTAMP,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
        )";
        $this->db->execute($sql);
        
        // Create index on serial number for faster lookups
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_devices_serial ON devices(serial_number)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_devices_status ON devices(status)");
    }

    private function createAttendanceTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id INTEGER NOT NULL,
            user_id TEXT NOT NULL,
            timestamp TEXT NOT NULL,
            verify_mode INTEGER,
            in_out_mode INTEGER,
            work_code INTEGER,
            raw_data TEXT,
            synced_at TEXT DEFAULT CURRENT_TIMESTAMP,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            UNIQUE(device_id, user_id, timestamp)
        )";
        $this->db->execute($sql);
        
        // Create indexes for faster queries
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_attendance_device ON attendance(device_id)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_attendance_user ON attendance(user_id)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_attendance_timestamp ON attendance(timestamp)");
    }

    private function createDeviceLogsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS device_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id INTEGER NOT NULL,
            event_type TEXT NOT NULL,
            message TEXT,
            details TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
        )";
        $this->db->execute($sql);
        
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_device_logs_device ON device_logs(device_id)");
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_device_logs_event ON device_logs(event_type)");
    }

    private function createSystemLogsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS system_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            level TEXT NOT NULL,
            message TEXT NOT NULL,
            context TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->execute($sql);
        
        $this->db->execute("CREATE INDEX IF NOT EXISTS idx_system_logs_level ON system_logs(level)");
    }

    public function dropAllTables()
    {
        $tables = ['attendance', 'device_logs', 'system_logs', 'devices', 'branches'];
        foreach ($tables as $table) {
            $this->db->execute("DROP TABLE IF EXISTS $table");
        }
    }
}

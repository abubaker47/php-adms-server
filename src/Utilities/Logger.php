<?php

namespace ADMS\Utilities;

use ADMS\Database\Database;

class Logger
{
    private $db;
    private $config;
    private $logPath;

    public function __construct(Database $db, $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->logPath = $config['path'];
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function log($level, $message, $context = [])
    {
        if (!$this->config['enabled']) {
            return;
        }

        // Write to database
        $this->logToDatabase($level, $message, $context);
        
        // Write to file
        $this->logToFile($level, $message, $context);
    }

    public function debug($message, $context = [])
    {
        $this->log('debug', $message, $context);
    }

    public function info($message, $context = [])
    {
        $this->log('info', $message, $context);
    }

    public function warning($message, $context = [])
    {
        $this->log('warning', $message, $context);
    }

    public function error($message, $context = [])
    {
        $this->log('error', $message, $context);
    }

    public function logDeviceEvent($deviceId, $eventType, $message, $details = null)
    {
        $sql = "INSERT INTO device_logs (device_id, event_type, message, details) 
                VALUES (?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $deviceId,
            $eventType,
            $message,
            $details ? json_encode($details) : null
        ]);
    }

    private function logToDatabase($level, $message, $context)
    {
        try {
            $sql = "INSERT INTO system_logs (level, message, context) VALUES (?, ?, ?)";
            $this->db->execute($sql, [
                $level,
                $message,
                json_encode($context)
            ]);
        } catch (\Exception $e) {
            // Silently fail to avoid infinite loops
        }
    }

    private function logToFile($level, $message, $context)
    {
        $logFile = $this->logPath . '/adms-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    public function getSystemLogs($limit = 100, $level = null)
    {
        if ($level) {
            $stmt = $this->db->query(
                "SELECT * FROM system_logs WHERE level = ? ORDER BY created_at DESC LIMIT ?",
                [$level, $limit]
            );
        } else {
            $stmt = $this->db->query(
                "SELECT * FROM system_logs ORDER BY created_at DESC LIMIT ?",
                [$limit]
            );
        }
        
        return $stmt->fetchAll();
    }

    public function getDeviceLogs($deviceId, $limit = 100)
    {
        $stmt = $this->db->query(
            "SELECT * FROM device_logs WHERE device_id = ? ORDER BY created_at DESC LIMIT ?",
            [$deviceId, $limit]
        );
        
        return $stmt->fetchAll();
    }
}

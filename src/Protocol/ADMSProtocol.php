<?php

namespace ADMS\Protocol;

class ADMSProtocol
{
    const CMD_CONNECT = 1000;
    const CMD_EXIT = 1001;
    const CMD_ENABLEDEVICE = 1002;
    const CMD_DISABLEDEVICE = 1003;
    const CMD_RESTART = 1004;
    const CMD_POWEROFF = 1005;
    const CMD_GET_TIME = 1201;
    const CMD_SET_TIME = 1202;
    const CMD_GET_ATTLOG = 1100;
    const CMD_CLEAR_ATTLOG = 1102;
    const CMD_GET_DEVICEINFO = 1201;
    const CMD_ACK_OK = 2000;
    const CMD_ACK_ERROR = 2001;
    const CMD_ACK_DATA = 2002;

    private $socket;
    private $sessionId;
    private $replyId;

    public function connect($host, $port = 4370, $timeout = 5)
    {
        $this->socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$this->socket) {
            throw new \Exception('Failed to create socket');
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $timeout,
            'usec' => 0
        ]);

        // Send connection command
        $command = $this->buildCommand(self::CMD_CONNECT);
        $this->sendCommand($host, $port, $command);
        
        $response = $this->receiveResponse();
        if (!$response || !$this->validateResponse($response)) {
            throw new \Exception('Connection failed or invalid response');
        }

        return true;
    }

    public function disconnect()
    {
        if ($this->socket) {
            $command = $this->buildCommand(self::CMD_EXIT);
            socket_close($this->socket);
        }
    }

    public function getAttendanceLogs($host, $port = 4370)
    {
        $command = $this->buildCommand(self::CMD_GET_ATTLOG);
        $this->sendCommand($host, $port, $command);
        
        $response = $this->receiveResponse();
        if (!$response) {
            return [];
        }

        return $this->parseAttendanceData($response);
    }

    public function setTime($host, $port, $timestamp)
    {
        $command = $this->buildCommand(self::CMD_SET_TIME, pack('N', $timestamp));
        $this->sendCommand($host, $port, $command);
        
        $response = $this->receiveResponse();
        return $this->isAckOk($response);
    }

    public function restartDevice($host, $port)
    {
        $command = $this->buildCommand(self::CMD_RESTART);
        $this->sendCommand($host, $port, $command);
        
        $response = $this->receiveResponse();
        return $this->isAckOk($response);
    }

    public function powerOff($host, $port)
    {
        $command = $this->buildCommand(self::CMD_POWEROFF);
        $this->sendCommand($host, $port, $command);
        
        $response = $this->receiveResponse();
        return $this->isAckOk($response);
    }

    public function enableDevice($host, $port)
    {
        $command = $this->buildCommand(self::CMD_ENABLEDEVICE);
        $this->sendCommand($host, $port, $command);
        
        $response = $this->receiveResponse();
        return $this->isAckOk($response);
    }

    public function disableDevice($host, $port)
    {
        $command = $this->buildCommand(self::CMD_DISABLEDEVICE);
        $this->sendCommand($host, $port, $command);
        
        $response = $this->receiveResponse();
        return $this->isAckOk($response);
    }

    private function buildCommand($commandId, $data = '')
    {
        $this->replyId = $this->replyId ?? 0;
        $this->replyId = ($this->replyId + 1) % 65536;
        $this->sessionId = $this->sessionId ?? 0;

        // ADMS protocol packet structure
        $header = pack('v', $commandId);           // Command ID (2 bytes)
        $header .= pack('v', 0);                   // Checksum placeholder (2 bytes)
        $header .= pack('v', $this->sessionId);    // Session ID (2 bytes)
        $header .= pack('v', $this->replyId);      // Reply ID (2 bytes)
        $header .= $data;

        // Calculate checksum
        $checksum = $this->calculateChecksum($header);
        $header = substr($header, 0, 2) . pack('v', $checksum) . substr($header, 4);

        return $header;
    }

    private function calculateChecksum($data)
    {
        $checksum = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $checksum += ord($data[$i]);
        }
        return $checksum % 65536;
    }

    private function sendCommand($host, $port, $command)
    {
        if (!$this->socket) {
            throw new \Exception('Socket not connected');
        }

        $sent = @socket_sendto($this->socket, $command, strlen($command), 0, $host, $port);
        if ($sent === false) {
            throw new \Exception('Failed to send command');
        }

        return $sent;
    }

    private function receiveResponse()
    {
        if (!$this->socket) {
            return false;
        }

        $buffer = '';
        $from = '';
        $port = 0;

        $received = @socket_recvfrom($this->socket, $buffer, 1024, 0, $from, $port);
        if ($received === false || $received === 0) {
            return false;
        }

        return $buffer;
    }

    private function validateResponse($response)
    {
        if (strlen($response) < 8) {
            return false;
        }

        $header = unpack('vcommand/vchecksum/vsession/vreply', substr($response, 0, 8));
        $this->sessionId = $header['session'];

        return true;
    }

    private function isAckOk($response)
    {
        if (!$response || strlen($response) < 2) {
            return false;
        }

        $header = unpack('vcommand', substr($response, 0, 2));
        return $header['command'] === self::CMD_ACK_OK;
    }

    private function parseAttendanceData($response)
    {
        $records = [];
        
        if (strlen($response) < 8) {
            return $records;
        }

        // Skip header (8 bytes)
        $data = substr($response, 8);
        $recordSize = 40; // Typical attendance record size

        for ($i = 0; $i < strlen($data); $i += $recordSize) {
            if ($i + $recordSize > strlen($data)) {
                break;
            }

            $record = substr($data, $i, $recordSize);
            
            // Parse attendance record (simplified - actual format may vary)
            $parsed = unpack(
                'Vuser_id/Vtimestamp/Cverify_mode/Cin_out_mode/Vwork_code',
                $record
            );

            if ($parsed && $parsed['timestamp'] > 0) {
                $records[] = [
                    'user_id' => (string)$parsed['user_id'],
                    'timestamp' => date('Y-m-d H:i:s', $parsed['timestamp']),
                    'verify_mode' => $parsed['verify_mode'],
                    'in_out_mode' => $parsed['in_out_mode'],
                    'work_code' => $parsed['work_code'],
                    'raw_data' => bin2hex($record)
                ];
            }
        }

        return $records;
    }
}

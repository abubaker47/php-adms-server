<?php

namespace ADMS\Models;

class Device
{
    public $id;
    public $serial_number;
    public $device_name;
    public $branch_id;
    public $ip_address;
    public $port;
    public $model;
    public $firmware_version;
    public $status;
    public $last_connected_at;
    public $registered_at;
    public $created_at;
    public $updated_at;

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'device_name' => $this->device_name,
            'branch_id' => $this->branch_id,
            'ip_address' => $this->ip_address,
            'port' => $this->port,
            'model' => $this->model,
            'firmware_version' => $this->firmware_version,
            'status' => $this->status,
            'last_connected_at' => $this->last_connected_at,
            'registered_at' => $this->registered_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

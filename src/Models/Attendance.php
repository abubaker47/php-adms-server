<?php

namespace ADMS\Models;

class Attendance
{
    public $id;
    public $device_id;
    public $user_id;
    public $timestamp;
    public $verify_mode;
    public $in_out_mode;
    public $work_code;
    public $raw_data;
    public $synced_at;
    public $created_at;

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
            'device_id' => $this->device_id,
            'user_id' => $this->user_id,
            'timestamp' => $this->timestamp,
            'verify_mode' => $this->verify_mode,
            'in_out_mode' => $this->in_out_mode,
            'work_code' => $this->work_code,
            'raw_data' => $this->raw_data,
            'synced_at' => $this->synced_at,
            'created_at' => $this->created_at,
        ];
    }
}

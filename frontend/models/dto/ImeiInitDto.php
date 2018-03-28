<?php

namespace frontend\models\dto;

class ImeiInitDto
{
    public $imei;
    public $firmware_version;
    public $type_bill_acceptance;
    public $serial_number_kp;
    public $phone_module_number;
    public $crash_event_sms;
    public $critical_amount;
    public $time_out;

    public function __construct($data)
    {
        if (array_key_exists('imei', $data)) {
            $this->imei = (string)$data['imei'];
        }

        if (array_key_exists('firmware_version', $data)) {
            $this->firmware_version = (string)$data['firmware_version'];
        }

        if (array_key_exists('type_bill_acceptance', $data)) {
            $this->type_bill_acceptance = (string)$data['type_bill_acceptance'];
        }

        if (array_key_exists('serial_number_kp', $data)) {
            $this->serial_number_kp = (string)$data['serial_number_kp'];
        }

        if (array_key_exists('phone_module_number', $data)) {
            $this->phone_module_number = (string)$data['phone_module_number'];
        }

        if (array_key_exists('crash_event_sms', $data)) {
            $this->crash_event_sms = (string)$data['crash_event_sms'];
        }

        if (array_key_exists('critical_amount', $data)) {
            $this->critical_amount = (int)$data['critical_amount'];
        }

        if (array_key_exists('time_out', $data)) {
            $this->time_out = (int)$data['time_out'];
        }
    }
}

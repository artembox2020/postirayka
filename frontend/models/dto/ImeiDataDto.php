<?php

namespace frontend\models\dto;

/**
 * Imei data Dto
 */
class ImeiDataDto
{
    public $date;
    public $imei;
    public $level_signal;
    public $on_modem_account;
    public $in_banknotes;
    public $money_in_banknotes;
    public $fireproof_residue;
    public $price_regim;
    public $evt_bill_validator;
//    public $tiem_out;

    public function __construct($data)
    {
        if (array_key_exists('date', $data)) {
            $this->date = (integer)$data['date'];
        }

        if (array_key_exists('imei', $data)) {
            $this->imei = (string)$data['imei'];
        }

        if (array_key_exists('level_signal', $data)) {
            $this->level_signal = (double)$data['level_signal'];
        }

        if (array_key_exists('on_modem_account', $data)) {
            $this->on_modem_account = (float)$data['on_modem_account'];
        }

        if (array_key_exists('in_banknotes', $data)) {
            $this->in_banknotes = (double)$data['in_banknotes'];
        }

        if (array_key_exists('money_in_banknotes', $data)) {
            $this->money_in_banknotes = (float)$data['money_in_banknotes'];
        }

        if (array_key_exists('fireproof_residue', $data)) {
            $this->fireproof_residue = (float)$data['fireproof_residue'];
        }

        if (array_key_exists('price_regim', $data)) {
            $this->price_regim = (double)$data['price_regim'];
        }

        if (array_key_exists('evt_bill_validator', $data)) {
            $this->evt_bill_validator = (integer)$data['evt_bill_validator'];
        }

//        if (array_key_exists('time_out', $data)) {
//            $this->time_out = (integer)$data['time_out'];
//        }
    }
}

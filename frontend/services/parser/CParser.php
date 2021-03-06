<?php

namespace frontend\services\parser;

use frontend\controllers\CController;
use frontend\models\ImeiData;
use Yii;
use api\modules\v2d00\controllers\JsonController;
use api\modules\v2d00\UseCase\Log\Log;

/**
 * parsing initialization and data packet types
 * Class CParser
 * @package frontend\services\parser
 */
class CParser implements CParserInterface
{
    const SEVEN = 7;
    const FOUR = 4;

    /**
     * Parses the packet type data of TYPE_PACKET_INITIALIZATION
     * 
     * @param $p
     * @return array|bool
     */
    public function iParse($p)
    {
        $arrOut = array();

        // old initialization packet version
        $column = [
            'imei',
            'firmware_version',
            'firmware_version_cpu',
            'firmware_6lowpan',
            'number_channel',
            'pcb_version',
            'type_bill_acceptance',
            'serial_number_kp',
            'phone_module_number',
            'crash_event_sms',
            'critical_amount',
            'time_out'
        ];

        // new initialization packet version
        $columnNew = [
            'imei',
            'firmware_version',
            'firmware_version_cpu',
            'firmware_6lowpan',
            'number_channel',
            'pcb_version',
            'phone_module_number',
            'on_modem_account',
            'level_signal'
        ];

        // newest initialization packet version
        $columnNewest = [
            'imei',
            'firmware_version',
            'firmware_version_cpu',
            'firmware_6lowpan',
            'number_channel',
            'pcb_version',
            'phone_module_number',
            'on_modem_account',
            'level_signal',
            'traffic'
        ];

        $array = array_map("str_getcsv", explode('*', $p));

        foreach ($array as $subArr) {
            $arrOut = array_merge($arrOut, $subArr);
        }

        // pick up the appropriate parser
        if (count($column) == count($arrOut)) {
            $result = array_combine($column, $arrOut);
            $result['on_modem_account'] = null;
            $result['level_signal'] = null;
            $result['traffic'] = null;
        } elseif (count($columnNew) == count($arrOut)) {
            $result = array_combine($columnNew, $arrOut);
            $result['type_bill_acceptance'] = null;
            $result['serial_number_kp'] = null;
            $result['crash_event_sms'] = null;
            $result['critical_amount'] = null;
            $result['time_out'] = null;
            $result['traffic'] = null;
        } elseif (count($columnNewest) == count($arrOut)) {
            $result = array_combine($columnNewest, $arrOut);
            $result['type_bill_acceptance'] = null;
            $result['serial_number_kp'] = null;
            $result['crash_event_sms'] = null;
            $result['critical_amount'] = null;
            $result['time_out'] = null;
        } else {

            return false;
        }

        return $result;
    }

    /**
     * Parsers the packet type data of TYPE_PACKET_DATA
     * 
     * @param $p
     * @return array
     */
    public function dParse($p)
    {

        return $this->getImeiData($p)['imeiData'];
    }

    /**
     * Gets imeiData the packet type data of TYPE_PACKET_DATA
     * 
     * @param $p
     * @return array
     */
    public function getImeiData($p)
    {
        if (empty($p)) {

            return ['imeiData' => null, 'packet' => null];
        }

        $param = explode('_', $p);

        $imeiData = explode(CController::STAR, $param[0]);

        // get index according to packet data version
        $indexOldVersion = $this->getIndexVersionByImeiData($imeiData);

        /** new version for imei */
        $diff = '';
        foreach ($imeiData as $key => $value) {
            if ($key > $indexOldVersion) {
                $diff .= $value . '*';
                unset ($imeiData[$key]);
            }
        }

        $packet = substr($diff, 0, -1);

        $imeiData = CController::setImeiData($imeiData);

        return [
            'imeiData' => $imeiData,
            'packet' => $packet
        ];
    }

    /**
     * Gets index data packet version by imei data
     * 
     * @param array $imeiData
     * @return integer
     */
    public function getIndexVersionByImeiData($imeiData)
    {
        // get timestamp year for old data packet version
        $year = date("Y", (integer)$imeiData[0]);

        // switch index according to packet data version
        if (count($imeiData) >= 8 && (integer)$year > 1969 && (integer)$year < 2100) {

            return $indexOldVersion = self::SEVEN;
        } else {

            return $indexOldVersion = self::FOUR;
        }
    }

    /**
     * Gets cental board status value from 'packet' field (present at tables 'imei_data', 'j_log')
     * 
     * @param string $packet
     * @param ImeiData $imeiData
     * @return string|bool
     */
    public function getCPStatusFromPacketField($packet, $imeiData = false)
    {
        if (empty($packet)) {

            return false;
        }
        
        if (!$imeiData) {
            
            $imeiData = new ImeiData();
        }

        $centalBoardId = explode('*', $packet)[0];

        if (in_array($centalBoardId, array_keys($imeiData->status_central_board))) {

            return Yii::t('imeiData', $imeiData->status_central_board[$centalBoardId]);
        }

        return false;
    }
    
    /**
     * Gets cental board status value from packet'p'
     * 
     * @param string $p
     * @return string|bool
     */
    public function getCPStatusFromDataPacket($p)
    {
        $packet = $this->getImeiData($p)['packet'];

        if (empty($packet)) {

            return false;
        }

        $centalBoardId = explode('*', $packet)[0];
        $imeiData = new ImeiData();

        if (in_array($centalBoardId, array_keys($imeiData->status_central_board))) {

            return Yii::t('imeiData', $imeiData->status_central_board[$centalBoardId]);
        }

        return false;
    }

    /**
     * Gets event bill validator value from packet 'p'
     * $useAsCpStatus means to apply EventCentalBoard statuses insead of EventBillValidator ones
     * 
     * @param string $packet
     * @param bool $useAsCpStatus
     * @return string|bool
     */
    public function getEvtBillValidatorFromDataPacket($p, $useAsCpStatus = false)
    {
        $data = $this->getImeiData($p);
        $data = $data['imeiData'];

        if (isset($data['evt_bill_validator'])) {

            if ($useAsCpStatus) {
                $imeiData = new ImeiData();

                if (in_array($data['evt_bill_validator'], array_keys($imeiData->status_central_board))) {

                    return  Yii::t('imeiData', $imeiData->status_central_board[$data['evt_bill_validator']]);
                }

                return false;
            }

            if (in_array($data['evt_bill_validator'], array_keys(ImeiData::evtBillValidator))) {

                return  Yii::t('imeiData', ImeiData::evtBillValidator[$data['evt_bill_validator']]);
            }

            return false;
        }

        return false;
    }

    public function getMashineData($p)
    {
        $array = array();
        $param = explode('_', $p);
        $mashineData = array();

        foreach ($param as $item) {
            if (strripos($item, CController::STAR_DOLLAR)) {
                $item = str_replace(CController::STAR_DOLLAR, '', $item);
            }
            $array[] = explode(CController::STAR, $item);
        }

        /**
         * allocate the machine to an array $mashineData
         */
        foreach ($array as $key => $value) {
            foreach ($value as $item => $val) {
                if (!is_numeric($val)) {
                    $mashineData[$val][] = $value;
                }
            }
        }

        return $mashineData;
    }

    /**
     * Check is new initialization packet
     * 
     * @param string $p
     *
     * @return bool
     */
    public function checkNewInitializationPacket($p)
    {
        if (count(explode("*", $p)) == 9) {

            return true;
        }

        return false;
    }

    /**
     * Replaces level signal
     * 
     * @param string $p
     * @param int $levelSignal
     *
     * @return string
     */
    public function replaceLevelSignal($p, $signalLevel)
    {
        $dataParts = explode("*", $p);
        $count = count($dataParts);
        $dataParts[$count-1] = $signalLevel;

        return implode("*", $dataParts);
    }

    /**
     * Replaces CB status
     * 
     * @param string $p
     * @param int $cpStatus
     *
     * @return string
     */
    public function replaceCpStatus($p, $cpStatus)
    {
        $imeiString = explode("_", $p)[0];
        $restString = substr($p, strlen($imeiString));
        $imeiParts = explode("*", $imeiString);
        $imeiParts[self::SEVEN - 2] = $cpStatus;
        $newImeiString = implode("*", $imeiParts);

        return $newImeiString.$restString;
    }

    /**
     * Gets CB status
     * 
     * @param string $p
     *
     * @return string
     */
    public function getCpStatus($p)
    {
        $imeiString = explode("_", $p)[0];
        $imeiParts = explode("*", $imeiString);

        return $imeiParts[self::SEVEN - 2];
    }

    /**
     * Gets level signal
     * 
     * @param string $p
     *
     * @return string
     */
    public function getLevelSignal($p)
    {
        $packetData = $this->iParse($p);

        return $packetData['level_signal'];
    }

    /**
     * Gets init packet string
     * 
     * @param Imei $imei
     * 
     * @return string
     */
    public function getInitPacket($imei)
    {
        $traffic = empty($imei->traffic) ? null : $imei->traffic;
        $firmware_version_cpu = empty($imei->firmware_version_cpu) ? null : $imei->firmware_version_cpu;

        $p =$imei->imei.'*'.$imei->firmware_version.'*'.
            $firmware_version_cpu.'*'.$imei->firmware_6lowpan.'*'.
            $imei->number_channel.'*'.$imei->pcb_version.'*'.
            $imei->phone_module_number.'*'.$imei->on_modem_account.'*'.
            $imei->level_signal.'*'.$traffic;

        return $p;
    }

    /**
     * Gets state packet string by imei data and WMs data
     * 
     * @param Imei $imei
     * @param ImeiData $imeiData
     * @param array $devices
     * 
     * @return string
     */
    public function getStatePacket($imei, $imeiData, $devices)
    {
        // make CP state string
        $pCp =  $imei->imei.'*'.$imeiData->in_banknotes.'*'.$imeiData->on_modem_account.'*'.
                $imeiData->fireproof_residue.'*'.$imeiData->evt_bill_validator.'*'.$imeiData->packet;
        $pWms = '';

        // make WMs state string
        foreach ($devices as $device) {
            $pWms.= "_".$device->type.'*'.$device->number.'*'.$device->rssi.'*'.$device->money.'*'.
                    $device->door.'*'.$device->checkLEDDoor.'*'.$device->state.'*'.$device->display;
        }

        return $pCp.$pWms;
    }

    /**
     * Converts init package string representation into json
     *
     * @param string $p
     *
     * @return string
     */
    public function convertInitPacketFromStringToJson(string $p): string
    {
        $data = $this->iParse($p);
        $pac = [
            'bootloader' => $data['firmware_version'],
            'firmware' => empty($data['firmware_version_cpu']) ? "" : $data['firmware_version_cpu'],
            'radio' => $data['firmware_6lowpan'],
            'PCB' => $data['pcb_version'],
            'channel' => $data['number_channel'],
            'telephone' => $data['phone_module_number'] ?? "",
            'rssi' => $data['level_signal'],
            'modemCash' => $data['on_modem_account'],
            'traffic' => empty($data['traffic']) ? "0" : $data['traffic']
        ];

        $packetData = ['imei' => $data['imei'], 'type' => JsonController::INI, 'pac' => $pac];

        return json_encode($packetData);
    }

    /**
     * Converts state package string representation into json
     *
     * @param string $p
     *
     * @return string
     */
    public function convertDataPacketFromStringToJson(string $p): string
    {
        $imeiData = $this->getImeiData($p);
        $mashineData = $this->getMashineData($p)['WM'];
        $data = array_merge($imeiData, $mashineData);

        $device = [];

        foreach ($mashineData as $item) {
            $array_keys = ['type', 'number', 'rssi', 'money', 'door', 'checkLEDDoor', 'state', 'display'];
            $wmData = array_merge(array_combine($array_keys, $item), ['total_cash' => 0]);

            foreach (array_keys($wmData) as $key) {
                $wmData[$key] = trim($wmData[$key]);
            }

            $device[] = $wmData;
        }

        $pac = [
            'numberNotes' => $imeiData['imeiData']['in_banknotes'],
            'collection' => $imeiData['imeiData']['money_in_banknotes'],
            'totalCash' => $imeiData['imeiData']['fireproof_residue'],
            'workStatus' => [
                'CenBoard' => $imeiData['packet'],
                'validState' => $imeiData['imeiData']['evt_bill_validator']
            ],
            'device' => $device
        ];

        return json_encode([
            'imei' => $imeiData['imeiData']['imei'],
            'type' => JsonController::STATUS,
            'pac' => $pac
        ]);
    }
}

<?php

namespace frontend\controllers;

use api\modules\t1\UseCase\ResponseMonitoring;
use frontend\services\imei\ImeiService;
use frontend\services\parser\CParser;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use frontend\models\Imei;
use frontend\models\ImeiData;
use frontend\models\ImeiAction;
use frontend\models\dto\GdDto;
use frontend\models\dto\WmDto;
use frontend\models\dto\CentralBoardEncashmentDto;
use frontend\models\GdMashine;
use frontend\models\WmMashine;
use frontend\models\GdMashineData;
use frontend\models\WmMashineData;
use frontend\models\CbLogSearch;
use frontend\models\dto\ImeiDataDto;
use frontend\models\dto\ImeiInitDto;
use frontend\models\Jlog;
use frontend\models\CbEncashment;
use frontend\services\custom\Debugger;
use frontend\services\globals\Entity;
use frontend\services\globals\DateTimeHelper;

/**
 * data processing IMEI, Wash - Machine, Gel - Dispenser
 * Class CController
 * @package frontend\controllers
 */
class CController extends Controller
{
    const TYPE_PACKET = 'p';
    const TYPE_WM = 'WM';
    const TYPE_DM = 'DM';
    const TYPE_DC = 'DC';
    const TYPE_GD = 'GD';
    const STAR = '*';
    const STAR_DOLLAR = '*$';
    const ONE_CONST = 1;
    const SEVEN = 7;

    /**
     * Initialisation method
     * sens.loc/c/i?p=862631033023192*1.60*1.11*2.00*MDB**380733108207*+380936769548*3*60
     * @param $p
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionI($p)
    {
        //$this->retranslatePackage('/c/i', $p);
        $cParser = new CParser();
        $result = $cParser->iParse($p);
        $initDto = new ImeiInitDto($result);

        if (Imei::findOne(['imei' => $initDto->imei])) {
            $imei = Imei::findOne(['imei' => $initDto->imei]);

            if (Imei::getStatus($imei) == self::ONE_CONST) {
                $imei = Imei::findOne(['imei' => $initDto->imei]);
                $imei->imei_central_board = $initDto->imei;
                $imei->firmware_version = $initDto->firmware_version;
                $imei->firmware_version_cpu = $initDto->firmware_version_cpu;
                $imei->firmware_6lowpan = $initDto->firmware_6lowpan;
                $imei->number_channel = $initDto->number_channel;
                $imei->pcb_version = $initDto->pcb_version;
                $imei->type_packet = self::TYPE_PACKET;
                $imei->type_bill_acceptance = $initDto->type_bill_acceptance;
                $imei->serial_number_kp = $initDto->serial_number_kp;
                $imei->phone_module_number = $initDto->phone_module_number;
                $imei->crash_event_sms = $initDto->crash_event_sms;
                $imei->critical_amount = $initDto->critical_amount;
                $imei->time_out = $initDto->time_out;
                $imei->on_modem_account = $initDto->on_modem_account;
                $imei->level_signal = $initDto->level_signal;
                $imei->traffic = $initDto->traffic;
                $imei->ping = time() + Jlog::TYPE_TIME_OFFSET;
                $imei->update();

                $jlog = new Jlog();
                $jlog->createLogFromImei($imei, $p, Jlog::TYPE_PACKET_INITIALIZATION);
                echo 'Success!';
            } else {
                echo 'Imei not Active';exit;
            }
        } else {
            echo 'Imei not exists';exit;
        }
    }

    /**
     * Data method
     * sens.loc/c/d?p=
     * 1467707999*866104020101005*45*110*100*1500*25000*2*1_WM*1*86*15*1*1*22_DM*1*55*45*15_GD*3452*25*5_DC*25*15*-2$
     * $ - ignored
     * @param [type] $p
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionD($p)
    {
        //$this->retranslatePackage('/c/d', $p);
        $array = array();
        $mashineData = array();
        $packetParser = new CParser();

        $param = explode('_', $p);

        $imeiData = explode(self::STAR, $param[0]);

        foreach ($param as $item) {
            if (strripos($item, self::STAR_DOLLAR)) {
                $item = str_replace(self::STAR_DOLLAR, '', $item);
            }
            $array[] = explode(self::STAR, $item);
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

        // get index according to packet data version
        $indexOldVersion = $packetParser->getIndexVersionByImeiData($imeiData);

        /** new version for imei */
        $diff = '';
        foreach ($imeiData as $key => $value) {
            if ($key > $indexOldVersion) {
                $diff .= $value . '*';
                unset ($imeiData[$key]);
            }
        }

        $packet = substr($diff, 0, -1);

//        Debugger::d($packet);
//        Debugger::dd($imeiData);

        $result = self::setImeiData($imeiData);

        $imeiDataDto = new ImeiDataDto($result);
        $imeiData = new ImeiData();

        if (Imei::findOne(['imei' => $imeiDataDto->imei])) {

            $imei = Imei::findOne(['imei' => $imeiDataDto->imei]);
            $imeiData->imei_id = $imei->id;
            $imeiData->created_at = time() + Jlog::TYPE_TIME_OFFSET;
            $imeiData->imei = $imeiDataDto->imei;
            $imeiData->level_signal = $imeiDataDto->level_signal;
            $imeiData->on_modem_account = $imeiDataDto->on_modem_account;
            $imeiData->in_banknotes = $imeiDataDto->in_banknotes;
            $imeiData->money_in_banknotes = $imeiDataDto->money_in_banknotes;
            $imeiData->fireproof_residue = $imeiDataDto->fireproof_residue;
            $imeiData->price_regim = $imeiDataDto->price_regim;
            $imeiData->evt_bill_validator = $imeiDataDto->evt_bill_validator;
            $imeiData->is_deleted = false;

            if (!is_null($imeiData->level_signal)) {
                $imei->level_signal = $imeiData->level_signal;
            }

            $imeiData->cb_version = $imei->firmware_version;
            $imeiData->packet = $packet;
            $imei->update();

            $imeiData->save();

            $imei->ping = time() + Jlog::TYPE_TIME_OFFSET;
            $imei->save();

            $jlog = new Jlog();
            $jlog->createLogFromImei($imei, $p, Jlog::TYPE_PACKET_DATA);

            $this->setTypeMashine($mashineData, $imei);

            // telegram bot begin
            new ResponseMonitoring($mashineData, $imei);
            // telegram bot end

        } else {
            echo 'Imei not init or not exists'; exit;
        }
    }

    /**
     * @param $data
     * @return array
     */
    public static function setImeiData($data)
    {
        // old data packet version  fields
        $array_fields = [
            'date',
            'imei',
            'level_signal',
            'on_modem_account',
            'in_banknotes',
            'money_in_banknotes',
            'fireproof_residue',
            'price_regim',
//            'time_out'
        ];

        // new data packet version fields
        $new_array_fields = [
            'imei',
            'in_banknotes',
            'money_in_banknotes',
            'fireproof_residue',
            'evt_bill_validator'
        ];

        if (count($data) == count($new_array_fields)) {

            return $result = array_combine($new_array_fields, $data);
        } else {

            return $result = array_combine($array_fields, $data);
        }
    }

    /**
     * @param $data array
     * $id - Imei->id
     * @param Imei $imei
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function setTypeMashine($data, Imei $imei)
    {
        /** create or update wash machine (WM) */
        if (array_key_exists(self::TYPE_WM, $data)) {
            $this->serviceWashMachine($data, $imei);
        }

        /** create or update gel dispenser (GD) */
        if (array_key_exists(self::TYPE_GD, $data)) {
            $this->serviceGelDispenser($data, $imei->id);
        }

        /** does not work! */
        // if (array_key_exists(self::TYPE_DC, $data)) {
        //     foreach ($data[self::TYPE_DC] as $key => $value) {
        //         $gd_mashine = new DcDto($this->setDC($data[self::TYPE_DC][$key]));
        //         // $mashine->save();
        //         // Debugger::d($gd_mashine);
        //     }
        // }

        // if (array_key_exists(self::TYPE_DM, $data)) {
        //     foreach ($data[self::TYPE_DM] as $key => $value) {
        //         $dm_mashine = new DmDto($this->setDM($data[self::TYPE_DM][$key]));
        //         // $mashine->save();
        //         // Debugger::d($dm_mashine);
        //     }
        // }
        /** does not work! */
    }

    /**
     * @param $data
     * @return array
     */
    public function setWM($data)
    {
        $array_fields = array();

        $array_fields = [
            'type_mashine',
            'number_device',
            'level_signal',
            'bill_cash',
            'door_position',
            'door_block_led',
            'current_status',
            'display',
        ];

        return $result = array_combine($array_fields, $data);
    }

    /**
     * @param $data
     * @return array
     */
    public function setGd($data)
    {
        $array_fields = array();

        $array_fields = [
            'type_mashine',
            'gel_in_tank',
            'bill_cash',
            'current_status',
        ];

        return $result = array_combine($array_fields, $data);
    }

    /**
     * method setDC() does not work!
     *
     * @param [type] $data
     * @return array
     */
    public function setDC($data)
    {
        $array_fields = array();

        $array_fields = [
            'type_mashine',
            'sum_cards',
            'bill_cash',
            'current_status',
        ];

        return $result = array_combine($array_fields, $data);
    }

    /**
     *  method setDM() does not work!
     * 
     * @param [type] $data
     * @return array
     */
    public function setDM(array $data): array
    {
        $array_fields = array();

        $array_fields = [
            'type_mashine',
            'number_device',
            'level_signal',
            'bill_cash',
            'current_status',
        ];

        return $result = array_combine($array_fields, $data);
    }

    /**
     * @param $wm_machine_dto
     * @param $imei_id
     * @return WmMashine
     */
    public function autoCreateWashMachine($wm_machine_dto, $imei_id)
    {
        $imei = $this->getImei($imei_id);

        $wash_machine = new WmMashine();
        $wash_machine->type_mashine = $wm_machine_dto->type_mashine;
        $wash_machine->number_device = $wm_machine_dto->number_device;
        $wash_machine->level_signal = $wm_machine_dto->level_signal;
        $wash_machine->bill_cash = $wm_machine_dto->bill_cash;
        $wash_machine->door_position = $wm_machine_dto->door_position;
        $wash_machine->door_block_led = $wm_machine_dto->door_block_led;
        $wash_machine->current_status = $wm_machine_dto->current_status;
        $wash_machine->imei_id = $imei_id;
        $wash_machine->company_id = $imei->company_id;
        $wash_machine->balance_holder_id = $imei->balance_holder_id;
        $wash_machine->address_id = $imei->address_id;
        $wash_machine->current_status = $wm_machine_dto->current_status;
        $wash_machine->status = self::ONE_CONST;
        $wash_machine->is_deleted = false;
        $wash_machine->display = $wm_machine_dto->display;
        $wash_machine->save(false);

        return $wash_machine;
    }

    /**
     * @param $wm_mashine_dto
     * @param $imei_id
     * @return GdMashine
     */
    public function autoCreateGelDispenser($gd_mashine_dto, $imei_id)
    {
        $imei = $this->getImei($imei_id);

        $gd_mashine = new GdMashine();
        $gd_mashine->imei_id = $imei_id;
        $gd_mashine->company_id = $imei->company_id;
        $gd_mashine->balance_holder_id = $imei->balance_holder_id;
        $gd_mashine->address_id = $imei->address_id;
        $gd_mashine->current_status = self::ONE_CONST;
        $gd_mashine->is_deleted = false;
        $gd_mashine->type_mashine = $gd_mashine_dto->type_mashine;
        $gd_mashine->gel_in_tank = $gd_mashine_dto->gel_in_tank;
        $gd_mashine->bill_cash = $gd_mashine_dto->bill_cash;
        $gd_mashine->current_status = $gd_mashine_dto->current_status;
        $gd_mashine->is_deleted = false;
        $gd_mashine->save(false);

        return $gd_mashine;
    }

    /**
     * @param $imei_id
     * @return Imei|null
     */
    public function getImei($imei_id)
    {
        $imei = Imei::findOne(['id' => $imei_id]);

        return $imei;
    }

    /**
     * @param $data
     * @param $imei
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function serviceWashMachine($data, $imei)
    {
        // will contain all data mashine ids
        $wm_mashine_ids = [];
        foreach ($data[self::TYPE_WM] as $key => $value) {
            $wm_mashine_dto = new WmDto($this->setWM($data[self::TYPE_WM][$key]));

            if (!WmMashine::find()
            ->where(['number_device' => $wm_mashine_dto->number_device])
            ->andWhere(['imei_id' => $imei->id])->one()) {
                $new_wash_mashine = $this->autoCreateWashMachine($wm_mashine_dto, $imei->id);
                     echo $wm_mashine_dto->number_device . ' WM created!' . '<br>';
                $wm_mashine_ids[] = $new_wash_mashine->id;
            }

            if (WmMashine::find()
                ->where(['number_device' => $wm_mashine_dto->number_device])
                ->andWhere(['imei_id' => $imei->id])->one())  {
                $wm_mashine = WmMashine::find()
                    ->where(['number_device' => $wm_mashine_dto->number_device])
                    ->andWhere(['imei_id' => $imei->id])->one();
                $wm_mashine_ids[] = $wm_mashine->id;    
                $wm_mashine_data = new WmMashineData();
                $wm_mashine_data->mashine_id = $wm_mashine->id;
                $wm_mashine_data->type_mashine = $wm_mashine_dto->type_mashine;
                $wm_mashine_data->number_device = $wm_mashine_dto->number_device;
                $wm_mashine_data->level_signal = $wm_mashine_dto->level_signal;
                $wm_mashine_data->bill_cash = $wm_mashine_dto->bill_cash;
                $wm_mashine_data->door_position = $wm_mashine_dto->door_position;
                $wm_mashine_data->door_block_led = $wm_mashine_dto->door_block_led;
                $wm_mashine_data->current_status = $wm_mashine_dto->current_status;
                $wm_mashine_data->status = self::ONE_CONST;
                $wm_mashine_data->is_deleted = false;
                $wm_mashine_data->display = $wm_mashine_dto->display;
                $wm_mashine_data->ping = $imei->ping;
//                Debugger::dd($wm_mashine_data->display);
                $this->updateWmMashine($wm_mashine, $wm_mashine_data);
                if ($wm_mashine_data->save(false)) {
                    echo $wm_mashine_data->number_device . ' WM data save!' . '<br>';
                } else {
                    echo 'WM data Don\'t save' . '<br>';
                }
            }

//            $this->updateWmDeviceNumberNull($wm_mashine_dto, $imei->id);
        }

        // soft deletes mashines, not available to this data packet(except newly created)
        $this->disableNotAvailableWmMashines($wm_mashine_ids, $imei->id);
    }

    /**
     * @param $wm_mashine
     * @param WmMashineData $wm_mashine_data
     */
    public function updateWmMashine($wm_mashine, $wm_mashine_data)
    {
        $wm_mashine_data->mashine_id = $wm_mashine->id;
        $wm_mashine->setLastPing($wm_mashine_data);
        $wm_mashine->type_mashine = $wm_mashine_data->type_mashine;
        $wm_mashine->number_device = $wm_mashine_data->number_device;
        $wm_mashine->level_signal = $wm_mashine_data->level_signal;
        $wm_mashine->bill_cash = $wm_mashine_data->bill_cash;
        $wm_mashine->door_position = $wm_mashine_data->door_position;
        $wm_mashine->door_block_led = $wm_mashine_data->door_block_led;
        $wm_mashine->current_status = $wm_mashine_data->current_status;
        $wm_mashine->display = $wm_mashine_data->display;
        $wm_mashine_data->is_deleted = false;
        $wm_mashine->is_deleted = false;
        $wm_mashine->status = WmMashine::STATUS_ACTIVE;
        $wm_mashine->update(false);
        echo $wm_mashine->number_device . ' WM updated!' . '<br>';
    }

    /**
     * @param $gd_mashine
     * @param GdMashineData $gd_mashine_data
     */
    public function updateGdMashine($gd_mashine, $gd_mashine_data)
    {
        $gd_mashine_data->mashine_id = $gd_mashine->id;
        $gd_mashine->type_mashine = $gd_mashine_data->type_mashine;
        $gd_mashine->gel_in_tank = $gd_mashine_data->gel_in_tank;
        $gd_mashine->bill_cash = $gd_mashine_data->bill_cash;
        $gd_mashine->current_status = $gd_mashine_data->current_status;
        $gd_mashine_data->is_deleted = false;
        $gd_mashine->is_deleted = false;
        $gd_mashine->status = WmMashine::STATUS_ACTIVE;
        $gd_mashine->update(false);
        echo 'GD updated!' . '<br>';
    }

    /**
     * @param array $data
     * @param int $imei_id
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function serviceGelDispenser($data, $imei_id)
    {
        // will contain all data mashine ids
        $gd_mashine_ids = [];
        foreach ($data[self::TYPE_GD] as $key => $value) {
            $gd_mashine_dto = new GdDto($this->setGd($data[self::TYPE_GD][$key]));

            if (!GdMashine::find()
                ->where(['imei_id' => $imei_id])->one()) {
                $new_gd_mashine = $this->autoCreateGelDispenser($gd_mashine_dto, $imei_id);
                $gd_mashine_ids[] = $new_gd_mashine->id;
                echo 'Gel Dispenser created!' . '<br>';
            }

            if (GdMashine::find()
                ->where(['imei_id' => $imei_id])->one()) {
                $gd_mashine = GdMashine::find()
                    ->where(['imei_id' => $imei_id])->one();

                $gd_mashine_data = new GdMashineData();
                $gd_mashine_ids[] = $gd_mashine->id;
                $gd_mashine_data->mashine_id = $gd_mashine->id;
                $gd_mashine_data->type_mashine = $gd_mashine_dto->type_mashine;
                $gd_mashine_data->gel_in_tank = $gd_mashine_dto->gel_in_tank;
                $gd_mashine_data->bill_cash = $gd_mashine_dto->bill_cash;
                $gd_mashine_data->current_status = $gd_mashine_dto->current_status;
                $gd_mashine_data->is_deleted = false;

                $this->updateGdMashine($gd_mashine, $gd_mashine_data);

                if ($gd_mashine_data->save(false)) {
                    echo 'Gel Dispenser data save!' . '<br>';
                } else {
                    echo 'Gel Dispenser data Don\'t save' . '<br>';
                }
            }
        }
        
        // soft deletes mashines, not available to this data packet(except newly created)
        $this->disableNotAvailableGdMashines($gd_mashine_ids, $imei_id);
    }

    /**
     * Disables WmMashines, except ones in the list
     * 
     * @param array $wm_mashine_ids
     * @param int $imei_id 
     */
    public function disableNotAvailableWmMashines($wm_mashine_ids, $imei_id)
    {
        $entity = new Entity();
        $query = WmMashine::find();
        $query = $query->andFilterWhere(['imei_id' => $imei_id]);
        $query = $query->andFilterWhere(['not in', 'id', $wm_mashine_ids]);

        foreach ($query->all() as $mashine) {
            $mashine->status = WmMashine::STATUS_OFF;
            $mashine->save(false);
        }
    }

    /**
     * Disables GdMashines, except ones in the list
     * 
     * @param array $gd_mashine_ids
     * @param int $imei_id 
     */
    public function disableNotAvailableGdMashines($gd_mashine_ids, $imei_id)
    {
        $entity = new Entity();
        $query = $entity->getUnitsQueryPertainCompany(new GdMashine());
        $query = $query->andFilterWhere(['imei_id' => $imei_id]);
        $query = $query->andFilterWhere(['not in', 'id', $gd_mashine_ids]);

        foreach ($query->all() as $mashine) {
            $mashine->status = WmMashine::STATUS_OFF;
            $mashine->save(false);
        }
    }

    /**
     * Encashment method
     * sens.loc/c/f?p=862643034035000*1549892772*10000*450*300*14*1-0+2-0+5-0+10-10+20-0+50-4*20*1-0+2-0+5-10+10-10
     * @param $p
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionF($p)
    {
        //$this->retranslatePackage('/c/f', $p);
        $result = $this->fParse($p);
        $centralBoardDto = new CentralBoardEncashmentDto($result);
        $cbLogSearch = new CbLogSearch();

        if (Imei::findOne(['imei' => $centralBoardDto->imei])) {
            $imei = $this->getImeiByImei($centralBoardDto->imei);
            if (Imei::getStatus($imei) == self::ONE_CONST) {
                $cbl = new CbEncashment();
                $dateTimeHelper = new DateTimeHelper();
                if ($cbl->checkImeiIdUnixTimeOffsetUnique($imei->id, $centralBoardDto->unix_time_offset)) {
                    $cbl->company_id = $imei->company_id;
                    $cbl->address_id = $imei->address_id;
                    $cbl->imei_id = $imei->id;
                    $cbl->imei = $centralBoardDto->imei;
                    $cbl->device = 'cb';
                    $cbl->status = CbLogSearch::TYPE_ENCASHMENT_STATUS;
                    $cbl->unix_time_offset = $dateTimeHelper->getRightUtcTimestampByLocalTimestamp(
                        $centralBoardDto->unix_time_offset, env('TIMEZONE')
                    );
                    $cbl->fireproof_counter_hrn = $centralBoardDto->fireproof_counter_hrn;
                    $cbl->collection_counter = $centralBoardDto->collection_counter;
                    $cbl->notes_billiards_pcs = $centralBoardDto->notes_billiards_pcs;
                    $cbl->last_collection_counter = $centralBoardDto->last_collection_counter;
                    $cbl->banknote_face_values = $cbLogSearch->normalizeBanknoteFaceValuesString($centralBoardDto->banknote_face_values);
                    $cbl->amount_of_coins = $centralBoardDto->amount_of_coins;
                    $cbl->coin_face_values = $cbLogSearch->normalizeBanknoteFaceValuesString($centralBoardDto->coin_face_values);
                    $cbl->is_deleted = false;

                    if ($this->checkImeiAndTimestampUniquity($cbl)) {
                        $cbl->save();
                    }

                    $imei->ping = time();
                    $imei->save();
                    echo 'cbl encashment data save!';
                    exit;
                } else {
                    echo 'Unique key {imei_id, unix_time_offset} constraint violation';
                }
            } else {
                echo 'Imei not Active';
                exit;
            }
        } else {
            echo 'Imei not exists';exit;
        }
    }

    /**
     * Check whether data  index {imei_id, unix_time_offset} is unique by record item
     *
     * @param CbEncashment $cbl
     *
     * @return bool
     */
    public function checkImeiAndTimestampUniquity(CbEncashment $cbl): bool
    {
        $count = $cbl::find()
            ->andWhere([
                'imei_id' => $cbl->imei_id,
                'unix_time_offset' => $cbl->unix_time_offset
            ])
            ->count();

        return !$count;
    }

    /**
     * Parses the packet encashment type data
     *
     * @param $p
     * @return array
     */
    public function fParse($p)
    {
        $arrOut = [];
        $column = [
            'imei',
            'unix_time_offset',
            'fireproof_counter_hrn',
            'collection_counter',
            'last_collection_counter',
            'notes_billiards_pcs',
            'banknote_face_values',
            'amount_of_coins',
            'coin_face_values'
        ];

        $array = array_map("str_getcsv", explode('*', $p));

        foreach ($array as $subArr) {
            $arrOut = array_merge($arrOut, $subArr);
        }

        $result = array_combine($column, $arrOut);

        return $result;
    }

    /**
     * @param $imei
     * @return Imei|null
     */
    public function getImeiByImei($imei)
    {
        return Imei::findOne(['imei' => $imei]);
    }

    /**
     * Command status method
     * sens.loc/c/q?p=1550513654*862643034118608
     *
     * @param string $p
     * @return @return \yii\web\Response
     */
    public function actionQ($p)
    {
        //$this->retranslatePackage('/c/q', $p);
        $arrOut = [];

        $column = [
            'unix_time_offset',
            'imei'
        ];

        $array = array_map("str_getcsv", explode('*', $p));

        foreach ($array as $subArr) {
            $arrOut = array_merge($arrOut, $subArr);
        }

        $arrResult = array_combine($column, $arrOut);

        $imei = $this->getImeiByImei($arrResult['imei']);

        if ($imei) {

            if (Imei::getStatus($imei) == self::ONE_CONST) {
                $imeiAction = new ImeiAction();
                $action = $imeiAction->getAction($imei->id, $arrResult['unix_time_offset']);

                if ($action) {

                    return 'com='.$action;
                } else {
                    $status = 'Action is not active or not exists';

                    return 'com=0_error='.$status;
                }
            } else {
                $status = 'Imei not active';

                return 'com=0_error='.$status;
            }

        } else {
            $status = 'Imei not exists';

            return 'com=0_error='.$status;
        }
    }

    /** retranslates packets to another server **/
    public function retranslatePackage(string $url, string $p): void
    {
        $url = 'http://mypostirayka.pp.ua'.$url.'?p='.$p;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_exec($ch);

        curl_close($ch);
    }
}

<?php

namespace api\modules\v2d00\UseCase\StatePackage;

use api\modules\v2d00\UseCase\ImeiInit\ImeiInit;
use api\modules\v2d00\UseCase\Command\Command;
use Exception;
use frontend\models\Imei;
use frontend\models\ImeiData;
use frontend\models\Jlog;
use frontend\models\WmMashine;
use frontend\models\WmMashineData;
use frontend\services\custom\Debugger;
use Throwable;
use Yii;
use frontend\services\parser\CParser;

/**
 * Class StatePackage
 * @package api\modules\v2d00\UseCase\StatePackage
 */
class StatePackage
{
    const TYPE_WM = 'WM';

    /**
     * Добавление данных об имее и машинках
     * @param $items
     * @return Exception|string
     * @throws Throwable
     */
    public function create($items)
    {
        try {
            $this->addImei($items);
            $this->addWm($items);
        } catch (\Exception $exception) {
            Yii::$app->response->statusCode = 500;
            return $exception;
        }

        Yii::$app->response->statusCode = 201;

        return Command::getCommand($items->imei);
    }

    /**
     * Добавление данных имея из пакета "Стан"
     * В табилцу imei_data
     * @param $items
     * @return string
     * @throws Throwable
     */
    public function addImei($items)
    {
        $imei = ImeiInit::getImei($items->imei);

//        Debugger::dd($items->pac->totalCash);
        $imeiData = new ImeiData();
        $imeiData->imei_id = $imei->id;
        $imeiData->created_at = time() + Jlog::TYPE_TIME_OFFSET;
        $imeiData->imei = $items->imei;
        $imeiData->fireproof_residue = $items->pac->totalCash;
        $imeiData->in_banknotes = $items->pac->numberNotes;
        $imeiData->on_modem_account = $items->pac->collection;
        $imeiData->cb_version = $imei->firmware_version;
        $imeiData->evt_bill_validator = $items->pac->workStatus->validState;
        $imeiData->packet = $items->pac->workStatus->CenBoard;
        $imeiData->is_deleted = false;
        $imeiData->insert();

        $imei->on_modem_account = $items->pac->collection;
        $imei->ping = time() + Jlog::TYPE_TIME_OFFSET;
        $imei->save();

        $this->addJlog($imei, $imeiData, $items->pac->device);

        return 'Ok';
    }

    /**
     * Обновление данных в таблице wash_machine
     * И добавление данных в таблицу wm_mashine_data
     * По необходимости авто-создание машинки
     * @param $items
     * @return string
     * @throws Throwable
     */
    public function addWm($items)
    {
        $wm_machine_array = [];
        $imei = ImeiInit::getImei($items->imei);
        $wm_machine_array = $this->createArrayWm($items->pac->device);
        $this->ifNeedAutoCreateWm($wm_machine_array, $imei);
        $this->insertWmData($wm_machine_array, $imei);

        return 'Ok';
    }

    /**
     * Проход по массиву объектов машинок
     * и вызов метода добавления машинок в т. wm_mashine_data
     * @param array $wm_machine_array
     * @param Imei $imei
     * @throws Throwable
     */
    public function insertWmData(array $wm_machine_array, Imei $imei)
    {
        foreach ($wm_machine_array as $value) {
            $this->insertWD($value, $imei);
        }
    }

    /**
     * Добавление данных в таблицу wm_mashine_data
     * @param $washMachine
     * @param Imei $imei
     * @throws Throwable
     */
    public function insertWD($washMachine, Imei $imei): void
    {
        $wash_machine = WmMashine::find()
            ->where(['number_device' => $washMachine->number])
            ->andWhere(['imei_id' => $imei->id])->one();

        $wm_mashine_data = new WmMashineData();
        $wm_mashine_data->mashine_id = $wash_machine->id;
        $wm_mashine_data->type_mashine = $washMachine->type;
        $wm_mashine_data->number_device = $washMachine->number;
        $wm_mashine_data->level_signal = $washMachine->rssi;
        $wm_mashine_data->bill_cash = $washMachine->money;
        $wm_mashine_data->total_cash = $washMachine->total_cash_dev;
        $wm_mashine_data->door_position = $washMachine->door;
        $wm_mashine_data->current_status = $washMachine->state;
        $wm_mashine_data->display = $washMachine->display;
        $wm_mashine_data->status = WmMashine::STATUS_ACTIVE;
        $wm_mashine_data->is_deleted = false;
        $wm_mashine_data->ping = $imei->ping;
        $wm_mashine_data->insert(false);

        $this->updateWashMachine($washMachine, $imei, $wm_mashine_data);
    }

    /**
     * Обновление состояния машинки в т. wm_machine
     * @param $washMachine
     * @param Imei $imei
     * @param WmMashineData $wm_mashine_data
     * @throws Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function updateWashMachine($washMachine, Imei $imei, $wm_mashine_data)
    {
        $wash_machine = WmMashine::find()
            ->andWhere(['number_device' => $washMachine->number])
            ->andWhere(['imei_id' => $imei->id])->one();

        $wash_machine->ping = $imei->ping;
        $wash_machine->level_signal = $washMachine->rssi;
        $wash_machine->bill_cash = $washMachine->money;
        $wash_machine->door_position = $washMachine->door;
        $wash_machine->door_block_led = $washMachine->checkLEDDoor;
        $wash_machine->current_status = $washMachine->state;
        $wash_machine->display = $washMachine->display;
        $wash_machine->status = WmMashine::STATUS_ACTIVE;
        $wash_machine->total_cash = $washMachine->total_cash_dev;
        $wash_machine->update(false);
    }

    /**
     * Если массив объектов(машинок), пришедший в пакете "стан",
     * больше чем массив объектов(машинок) имеющихся в базе данных
     * вызывается метода поиска машики
     * @param array $wm_machine_array
     * @param Imei $imei
     * @throws Throwable
     */
    public function ifNeedAutoCreateWm(array $wm_machine_array, Imei $imei): void
    {
        foreach ($wm_machine_array as $wm) {
            $this->checkIsWashMachine($wm, $imei);
        }
    }

    /**
     * Если искомая машинка не найдена вызывается метод
     * авто-создания машинки
     * @param $washMachine
     * @param Imei $imei
     * @throws Throwable
     */
    public function checkIsWashMachine($washMachine, Imei $imei): void
    {
        if (!WmMashine::find()
            ->andWhere(['number_device' => $washMachine->number])
            ->andWhere(['imei_id' => $imei->id])->one()) {
            $this->autoCreateWm($washMachine, $imei);
        }
    }

    /**
     * Метод для авто-создания машинки
     * @param $washMachine
     * @param Imei $imei
     * @return WmMashine
     * @throws Throwable
     */
    public function autoCreateWm($washMachine, Imei $imei)
    {
        $wash_machine = new WmMashine();
        $wash_machine->ping = $imei->ping;
        $wash_machine->type_mashine = $washMachine->type;
        $wash_machine->number_device = $washMachine->number;
        $wash_machine->level_signal = $washMachine->rssi;
        $wash_machine->bill_cash = $washMachine->money;
        $wash_machine->door_position = $washMachine->door;
        $wash_machine->door_block_led = $washMachine->checkLEDDoor;
        $wash_machine->current_status = $washMachine->state;
        $wash_machine->display = $washMachine->display;
        $wash_machine->imei_id = $imei->id;
        $wash_machine->company_id = $imei->company_id;
        $wash_machine->balance_holder_id = $imei->balance_holder_id;
        $wash_machine->address_id = $imei->address_id;
        $wash_machine->status = WmMashine::ONE;
        $wash_machine->is_deleted = false;
        $wash_machine->insert(false);

        return $wash_machine;
    }

    /**
     * Подсчет кол-ва объектов машинок в базе данных
     * @param Imei $imei
     * @return int
     */
    public function countWm(Imei $imei): int
    {
        $wash_machine_count = WmMashine::find()
            ->andWhere(['imei_id' => $imei->id])
            ->andWhere(['is_deleted' => false])
            ->count();

        return $wash_machine_count;
    }

    /**
     * Создания массива объектов машинок (type:WM) из объекта
     * "Стан"
     * @param array $items
     * @return array
     */
    public function createArrayWm(array $items): array
    {
        $wm_machine_array = [];
        foreach ($items as $value) {
            if ($value->type == self::TYPE_WM) {
                $wm_machine_array[] = $value;
            }
        }

        return $wm_machine_array;
    }

    /**
     * Adds item to `j_log` table
     * 
     * @param Imei $imei
     * @param ImeiData $imeiData
     * @param array $devices
     */
    public function addJlog($imei, $imeiData, $devices)
    {
        $jlog = new Jlog();
        $parser = new CParser();
        $p = $parser->getStatePacket($imei, $imeiData, $devices);
        $jlog->createLogFromImei($imei, $p, Jlog::TYPE_PACKET_DATA);
    }
}

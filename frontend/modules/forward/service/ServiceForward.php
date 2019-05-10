<?php

namespace frontend\modules\forward\service;

use frontend\models\AddressBalanceHolder;
use frontend\models\BalanceHolder;
use frontend\models\Imei;
use frontend\models\ImeiData;
use frontend\models\WmMashine;
use frontend\modules\forward\interfaces\ServiceForwardInterface;
use frontend\services\custom\Debugger;
use Yii;

/**
 * Class ServiceForward
 * @package frontend\modules\forward\service
 */
class ServiceForward implements ServiceForwardInterface
{
    const DATE_FORMAT = 'h:i d.m.Y';
    public $array = array();
    public $result = array();

    /**
     * getStaff принимает строку (короткий адрес) возвращает связанные объекты в сформированном
     * массиве
     * array(3) {
    ["param"]=>array(2) {
       ["central_board_status"]=>string(2) "OK"
       ["bill_acceptor_status"]=>string(2) "OK"
        }
    ["BalanceHolder"]=>string(8) "КІМО"
    ["вул. Мельникова 36/1 5"]=>array(2) {
    [1]=>array(4) {
    ["device_number"]=>float(1)
    ["display"]=>string(0) ""
    ["date"]=>string(16) "09:48 11.04.2019"
    ["status"]=>string(32) "Немає зв'язку з ПМ"
    }
    [2]=>array(4) {
    ["device_number"]=>float(2)
    ["display"]=>string(2) "  "
    ["date"]=>string(16) "12:31 12.04.2019"
    ["status"]=>string(32) "Готовий до роботи"
            }
        }
    }
    ...
     * @param string $address_name
     * @return array
     */
    public function getStaff(string $address_name): array
    {
        $address = AddressBalanceHolder::find()
            ->andWhere(['name' => $address_name])
            ->one();

        $balanceHolderName = $address->balanceHolder->name;

        $imei = Imei::find()
            ->andWhere(['address_id' => $address->id])
            ->andWhere(['imei.status' => Imei::STATUS_ACTIVE])
            ->one();

        $wm_machine = WmMashine::find()
            ->andWhere(['imei_id' => $imei->id])
            ->andWhere(['wm_mashine.status' => WmMashine::STATUS_ACTIVE])
            ->all();

        $imeiData = ImeiData::find()
            ->andWhere(['imei_id' => $imei->id])
            ->orderBy('created_at DESC')
            ->one();

        $this->result['param'] = [
            'central_board_status' => Yii::t('imeiData', $imeiData->status_central_board[$imeiData->packet]),
            'bill_acceptor_status' => Yii::t('imeiData', $imeiData->status_bill_acceptor[$imeiData->evt_bill_validator])
        ];

        $this->result['BalanceHolder'] = $balanceHolderName;

        foreach ($wm_machine as $key => $value) {
            $this->array[$value->number_device] = [
                'device_number' => $value->number_device,
                'display' => $value->display,
                'date' => date(self::DATE_FORMAT, $value->ping),
                'status' => Yii::t('frontend', $value->current_state[$value->current_status])];
        }

        $this->result[$address->address . ' ' . $address->floor] = $this->array;
        
        return $this->result;
    }
}
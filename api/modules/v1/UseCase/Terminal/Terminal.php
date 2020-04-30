<?php

namespace api\modules\v1\UseCase\Terminal;

use DateTime;
use frontend\models\AddressBalanceHolder;
use frontend\models\BalanceHolder;
use frontend\models\Imei;
use frontend\models\ImeiData;
use frontend\models\Jlog;
use frontend\models\WmMashine;
use frontend\modules\forward\interfaces\ServiceForwardInterface;
use frontend\services\custom\Debugger;
use http\Exception;
use Yii;
use yii\db\Query;

/**
 * Class Imei
 * @package api\modules\v1\UseCase
 */
class Terminal
{
    const DATE_FORMAT = 'H:i d.m.Y';
    const TYPE_HALF_HOUR = 1800;
    public $array = array();
    public $result = array();

    /**
     *
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
     * @param string $address_name
     * @return array
     * @throws \yii\db\Exception
     */
    public function getStaff(string $address_name): array
    {
        $address = AddressBalanceHolder::find()
            ->andWhere(['name' => $address_name])
            ->one();

        $imei = Imei::find()
            ->andWhere(['address_id' => $address->id])
            ->andWhere(['imei.status' => Imei::STATUS_ACTIVE])
            ->limit(1)
            ->one();

        $wm_machine = WmMashine::find()
            ->andWhere(['imei_id' => $imei->id])
            ->andWhere(['wm_mashine.status' => WmMashine::STATUS_ACTIVE])
            ->all();

        $res = Yii::$app->db->createCommand('SELECT *
          FROM imei_data WHERE imei_id = :imei_id ORDER BY created_at DESC LIMIT 1')
            ->bindValue(':imei_id', $imei->id)
            ->queryOne();

        $imeiData = new ImeiData();

        $status_central_board = $imeiData->status_central_board[$res['packet']];
        $status_central_board = Yii::t('imeiData', $status_central_board);

        $cb_code = (int)$res['packet'];

        if ($this->getRelevanceStatusCB($res['created_at'])) {
            $status_central_board = $this->getRelevanceStatusCB($res['created_at']);
            $cb_code = array_search('ErrTerminalNotInTouch', $imeiData->status_central_board);
        }

        $this->result['param'] = [
            'balance_holder' => $address->balanceHolder->name,
            'address' => $address->address,
            'floor' => $address->floor,
            'central_board_status' => Yii::t('imeiData', $status_central_board),
            'CB code' => $cb_code,
            'bill_acceptor_status' => Yii::t('imeiData', $imeiData->status_bill_acceptor[$res['evt_bill_validator']]),
            'BA code' => (int)$res['evt_bill_validator']
        ];

        foreach ($wm_machine as $key => $value) {
            $d = new DateTime(date(self::DATE_FORMAT, $value->ping)); // добавили один час(зимее время)
            $this->array[$value->number_device] = [
                'device_number' => $value->number_device,
                'display' => $value->display,
//                'date' => date(self::DATE_FORMAT, $value->ping + 60*60), // добавили один час(зимее время)
                'date' => $d->format('Y-m-d\TH:i:s.u+03:00'),
                'status' => Yii::t('frontend', $value->current_state[$value->current_status]),
                'status code' => $value->current_status
            ];
        }
//        $d = new DateTime(date(self::DATE_FORMAT, $value->ping));
//        Debugger::dd($d->format('Y-m-d\TH:i:s.u+03:00'));  // 2011-01-01T15:03:01.012345

        $this->result['wash_machine'] = $this->array;

        if (!$address) {
            $this->result = ['address' => Yii::t('imeiData', 'This terminal is not online')];
        }

        return $this->result;
    }

    /**
     * @param $time
     * @return string
     */
    public function getRelevanceStatusCB($time)
    {
        if (time() + Jlog::TYPE_TIME_OFFSET - $time > self::TYPE_HALF_HOUR) {

            return 'ErrTerminal';
        }
    }
}

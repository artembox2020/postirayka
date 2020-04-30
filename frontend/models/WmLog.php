<?php

namespace frontend\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Class WmLog
 * @package frontend\models
 * @property int $id
 * @property int $company_id
 * @property int $address_id
 * @property int $imei_id
 * @property integer $date
 * @property integer $imei
 * @property string $device
 * @property integer $unix_time_offset
 * @property integer $number
 * @property integer $signal
 * @property integer $status
 * @property float $price
 * @property float $account_money
 * @property integer $washing_mode
 * @property integer $wash_temperature
 * @property integer $spin_type
 * @property double $prewash
 * @property double $rinsing
 * @property double $intensive_wash
 */
class WmLog extends ActiveRecord
{
    /** @var array $current_state */
    public $current_state = [
        '-22' => 'bill_reject',
        '-21' => 'cashless_vend_denied',
        '-20' => 'error_b2',
        '-19' => 'error_ue',
        '-18' => 'error_te',
        '-17' => 'error_oe_of',
        '-16' => 'error_le',
        '-15' => 'error_he',
        '-14' => 'error_fe',
        '-13' => 'error_de',
        '-12' => 'error_ce',
        '-11' => 'error_be',
        '-10' => 'error_ae',
        '-9' => 'error_9e_uc',
        '-8' => 'error_8e',
        '-7' => 'error_5e',
        '-6' => 'error_4e',
        '-5' => 'error_3e',
        '-4' => 'error_1e',
        '-3' => 'zero_work',
        '-2' => 'freeze_with_water',
        '-1' => 'no_connect_mcd',
         '0' => 'no_power',
         '1' => 'power_on_washer',
         '2' => 'refill_washer',
         '3' => 'washing_dress',
         '4' => 'rising_dress',
         '5' => 'extraction_dress',
         '6' => 'washing_end',
         '7' => 'washer_free',
         '8' => 'nulling_washer',
         '9' => 'connect_mcd',
         '10' => 'sub_by_work',
         '11' => 'refill_washer_cashless',
         '12' => 'mkd_reboot',
         '13' => 'washing_start',
         '14' =>'max_washer_event'
    ];

    /** @var $model */
    private $model;

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'is_deleted' => true,
                    'deleted_at' => time() + Jlog::TYPE_TIME_OFFSET
                ],
            ],
            [
                'class' => TimestampBehavior::className()
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getModel()
    {
        if (!$this->model) {
            $this->model = new Imei();
        }

        return $this->model;
    }

    /**
     * @return $this|\yii\db\ActiveQuery
     */
    public static function find()
    {
        return parent::find()->where(['wm_log.is_deleted' => false]);
    }

//    /**
//     * @return array|null|\yii\db\ActiveRecord
//     */
//    public function getAddress()
//    {
//        return AddressBalanceHolder::find(['id' => $this->imei_id])->one();
//    }
}

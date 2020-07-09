<?php

namespace frontend\models;

use DateTime;
use frontend\services\custom\Debugger;
use nepster\basis\helpers\DateTimeHelper;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Class CbLog
 * @package frontend\models
 * @property int $id
 * @property int $company_id
 * @property int $address_id
 * @property int $imei_id
 * @property integer $date
 * @property integer $imei
 * @property string $device
 * @property integer $signal
 * @property integer $unix_time_offset
 * @property integer $status
 * @property float $fireproof_counter_hrn
 * @property float $fireproof_counter_card
 * @property float $collection_counter
 * @property double $notes_billiards_pcs
 * @property double $rate
 * @property float $refill_amount
 * @property boolean $is_deleted
 */
class CbLog extends \yii\db\ActiveRecord
{

    const ZERO = '0';
    /** @var array $current_state */
    public $current_state = [
        '-11' => 'invalid_command',
        '-10' => 'bill_reject',
        '-9' => 'stacker_problem',
        '-8' => 'bill_fish',
        '-7' => 'sensor_problem',
        '-6' => 'bill_remove',
        '-5' => 'bill_jam',
        '-4' => 'checksum_error',
        '-3' => 'motor_failure',
        '-2' => 'com_error',
        '-1' => 'cpu_problem',
        '0' => 'link_pc',
        '1' => 'update_software',
        '2' => 'change_technical',
        '3' => 'change_economical',
        '4' => 'change_remoter',
        '5' => 'recreate_logfile',
        '6' => 'reserved',
        '7' => 'full_bill_acceptor',
        '8' => 'collection',
        '9' => 'technical_bill',
        '10' => 'reserved',
        '11' => 'reserved',
        '12' => 'reserved',
        '13' => 'start_board',
        '14' => 'unlink_pc',
        '15' => 'reserved',
        '16' => 'reserved',
        '17' => 'reserved',
        '18' => 'err_update_fram',
        '19' => 'last_poweroff',
        '20' => 'cashless_reserved',
        '21' => 'cashless_vend_denied',
        '22' => 'cashless_cmd_out_of_seq',
        '23' => 'cashless_refund_ok',
        '24' => 'cashless_refund_error',
        '25' => 'cashless_man_defined_error',
        '26' => 'cashless_communications_error',
        '27' => 'cashless_reader_error',
        '28' => 'cashless_payment_media_error',
        '29' => 'cashless_unk_error',
        '30' => 'eth_conn_ok',
        '31' => 'err_eth_cable',
        '32' => 'err_eth_ip_addr',
        '33' => 'err_eth_server_conn',
        '34' => 'err_eth_data_send',
        '35' => 'reserved',
        '36' => 'reserved',
        '37' => 'reserved',
        '38' => 'reserved',
        '39' => 'reserved',
        '40' => 'remote_pay',
        '41' => 'http_201_response',
        '42' => 'http_206_response',
        '43' => 'http_400_response',
        '44' => 'http_404_response',
        '45' => 'http_500_response',
        '46' => 'http_601_response',
        '47' => 'http_unknown_response',
        '48' => 'coordinator_reboot',
        '49' => 'service_entry',
        '50' => 'cmd_reserved0',
        '51' => 'cmd_reset_cpu',
        '52' => 'cmd_reset_vend',
        '53' => 'cmd_reset_coord',
        '54' => 'cmd_reset_modem',
        '55' => 'cmd_format_disk',
        '56' => 'cmd_time_set',
        '57' => 'cmd_validator_off',
        '58' => 'cmd_reserved1',
        '59' => 'cmd_reserved2',
        '60' => 'cmd_reserved3',
        '61' => 'err_fram_sysinfo',
        '62' => 'err_fram_dynamic_sysinfo',
        '63' => 'err_fram_machines',
        '64' => 'err_fram_statinfo',
        '65' => 'err_fram_logimage',
        '66' => 'err_machines_table_corrupted',
	'67' => 'accu_start_of_charging',
	'68' => 'accu_finish_of_charging'
        ];

    /** @var $model */
    private $model;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cb_log';
    }

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

    public function rules() {
        return [
            /* your other rules */
            [['created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['recount_amount'], 'double']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('frontend', 'ID'),
            'imei' => Yii::t('frontend', 'Imei'),
            'rate' => Yii::t('logs', 'Rate')
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
        return parent::find()->where(['cb_log.is_deleted' => false]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImei()
    {
        return $this->hasOne(Imei::className(), ['id' => 'imei_id']);
    }

    /**
     * @param $address_id
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getAddress($address_id)
    {
        return AddressBalanceHolder::find(['id' => $address_id])->one();
    }

    /**
     * @param $date
     * @param $address_id
     * @return false|string|null
     * @throws \yii\db\Exception
     */
    public function getSumDaysPreviousAnAddress($date, $address_id)
    {
        $diff = Yii::$app->db->createCommand(
            'SELECT `date` FROM `cb_log`
                    WHERE `date` < :date
                    and `address_id` = :address_id
                    ORDER BY `date` ASC
                    LIMIT 1')
            ->bindValue(':date', $date)
            ->bindValue(':address_id', $address_id)
            ->queryScalar();

//        Debugger::dd($diff);

        $a = DateTimeHelper::diffDaysPeriod($date, $diff, $showTimeUntilDay = true);

//        echo $a;die;
        if ($diff) {
            $date = date('Y-m-d', $date);
            $diff = date('Y-m-d', $diff);
//            echo $date;die;
            $datetime1 = new DateTime($date);
            $datetime2 = new DateTime($diff);
            $interval = $datetime1->diff($datetime2);
//            return $interval->format('%R%a');
            return $a;
        }


        return $diff;
    }

    public function getWmLog()
    {
        return $this->hasOne(WmLog::className(), ['id' => $this->imei_id])->one();
    }
}

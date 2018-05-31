<?php

namespace frontend\models;

use Yii;
use frontend\models\ImeiData;
use frontend\models\GdMashine;
use frontend\models\WmMashine;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "imei".
 *
 * @property int $id
 * @property int $imei
 * @property int $address_id
 * @property string $type_packet
 * @property int $imei_central_board
 * @property string $firmware_version
 * @property string $type_bill_acceptance
 * @property string $serial_number_kp
 * @property string $phone_module_number
 * @property string $crash_event_sms
 * @property int $critical_amount
 * @property int $time_out
 * @property int $created_at
 * @property int $updated_at
 * @property int $is_deleted
 * @property int $deleted_at
 * @property int $company_id
 * @property int $status
 *
 * @property AddressBalanceHolder $address
 * @property Machine[] $machines
 */
class Imei extends \yii\db\ActiveRecord
{
    const STATUS_OFF = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_UNDER_REPAIR = 2;
    const STATUS_JUNK = 3;

    public $current_status = [
        'Off',
        'On',
        'Under repair',
        'Junk'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'imei';
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
                    'deleted_at' => time()
                ],
            ],
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['imei', 'address_id', 'imei_central_board', 'critical_amount', 'time_out', 'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['imei', 'address_id', 'company_id', 'status'], 'required'],
            [['type_packet', 'firmware_version', 'type_bill_acceptance', 'serial_number_kp', 'phone_module_number', 'crash_event_sms'], 'string', 'max' => 255],
            ['status', 'in', 'range' => array_keys(self::statuses())],
            [['is_deleted'], 'string', 'max' => 1],
            [['address_id'], 'exist', 'skipOnError' => true, 'targetClass' => AddressBalanceHolder::className(), 'targetAttribute' => ['address_id' => 'id']],
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
            'init' => Yii::t('frontend', 'Init'),
            'address_id' => Yii::t('frontend', 'Address'),
            'type_packet' => Yii::t('frontend', 'Type Packet'),
            'imei_central_board' => Yii::t('frontend', 'Imei Central Board'),
            'firmware_version' => Yii::t('frontend', 'Firmware Version'),
            'type_bill_acceptance' => Yii::t('frontend', 'Type Bill Acceptance'),
            'serial_number_kp' => Yii::t('frontend', 'Serial Number Kp'),
            'phone_module_number' => Yii::t('frontend', 'Phone Module Number'),
            'crash_event_sms' => Yii::t('frontend', 'Crash Event Sms'),
            'critical_amount' => Yii::t('frontend', 'Critical Amount'),
            'time_out' => Yii::t('frontend', 'Time Out'),
            'created_at' => Yii::t('frontend', 'Created At'),
            'updated_at' => Yii::t('frontend', 'Update At'),
            'is_deleted' => Yii::t('frontend', 'Is Deleted'),
            'addressName' => Yii::t('frontend', 'Address'),
            'deleted_at' => Yii::t('frontend', 'Deleted At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddress()
    {
        return $this->hasOne(AddressBalanceHolder::className(), ['id' => 'address_id']);
    }

    /**
     *  get Initialisation status
     * @return string
     */
    public function getInit()
    {
       if (!empty($this->firmware_version)) {
           return 'Ok';
       }

       return Yii::t('frontend', 'Not initialized');
    }


    /**
     * get Address Name
     *
     * @return void
     */
    public function getAddressName()
    {
        $address = $this->address;

        return $address ? $address->name : '';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImeiData()
    {
        return $this->hasMany(ImeiData::className(), ['imei_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWmMashine()
    {
        return $this->hasMany(WmMashine::className(), ['imei_id' => 'id']);
    }

    /**
     * get Machine status
     *
     * @return void
     */
    public function getMachineStatus()
    {
        return $this->hasMany(WmMashine::className(), ['imei_id' => 'id']);
    }

    /**
     * get Gd Machine
     *
     * @return void
     */
    public function getGdMashine()
    {
        return $this->hasMany(GdMashine::className(), ['imei_id' => 'id']);
    }

    /**
     * Returns imei statuses list
     *
     * @param mixed $status
     * @return array|mixed
     */
    public static function statuses($status = null)
    {
        $statuses = [
            self::STATUS_OFF => Yii::t('frontend', 'Disabled'),
            self::STATUS_ACTIVE => Yii::t('frontend', 'Active'),
            self::STATUS_UNDER_REPAIR => Yii::t('frontend', 'Under repair'),
            self::STATUS_JUNK => Yii::t('frontend', 'Junk'),
        ];

        if ($status === null) {
            return $statuses;
        }

        return $statuses[$status];
    }

    /**
     * @return $this|\yii\db\ActiveQuery
     */
    public static function find()
    {
        return parent::find()->where(['status' => Imei::STATUS_ACTIVE]);
//        return new UserQuery(get_called_class());
//        return parent::find()->where(['is_deleted' => 'false'])
//            ->andWhere(['status' => Imei::STATUS_ACTIVE]);
//            ->andWhere(['<', '{{%user}}.created_at', time()]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public static function getStatusOff()
    {
        return Imei::find()->where(['status' => Imei::STATUS_OFF])->all();
    }
}

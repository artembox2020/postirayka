<?php

namespace frontend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "gd_mashine_data".
 *
 * @property int $id
 * @property int $mashine_id
 * @property string $type_mashine
 * @property int $gel_in_tank
 * @property int $bill_cash
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property int $is_deleted
 * @property int $deleted_at
 * @property int $current_status
 *
 * @property Imei $imei
 */
class GdMashineData extends \yii\db\ActiveRecord
{
    /** @var array $current_status */
    public $current_state = [
        '-2' => 'nulling',
        '-1' => 'refill',
        'disconnected',
        'idle',
        'power on',
        'busy',
        'washing',
        'rising',
        'extraction',
        'waiting door',
        'end cycle',
        'freeze mode',
        '1e water sensor',
        '3e motor sensor',
        '4e water supply',
        '5e problem plum',
        '8e motor',
        '9e uc poser supply',
        'ae communication',
        'de switch',
        'ce cooling',
        'de unclosed door',
        'fe ventilation',
        'he heater',
        'le water leak',
        'oe of overflow',
        'te temp sensor',
        'ue loading cloth',
        'max error'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'gd_mashine_data';
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
                'class' => TimestampBehavior::className(),
                'value' => time() + Jlog::TYPE_TIME_OFFSET
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mashine_id'], 'required'],
            [['mashine_id',
                'gel_in_tank',
                'bill_cash',
                'status',
                'current_status',
                'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['type_mashine'], 'string', 'max' => 255],
            [['mashine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Imei::className(), 'targetAttribute' => ['mashine_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('frontend', 'ID'),
            'mashine_id' => Yii::t('frontend', 'Imei ID'),
            'type_mashine' => Yii::t('frontend', 'Type Mashine'),
            'gel_in_tank' => Yii::t('frontend', 'Gel In Tank'),
            'bill_cash' => Yii::t('frontend', 'Bill Cash'),
            'status' => Yii::t('frontend', 'Status'),
            'current_status' => Yii::t('frontend','Current status'),
            'created_at' => Yii::t('frontend', 'Created At'),
            'updated_at' => Yii::t('frontend', 'Updated At'),
            'is_deleted' => Yii::t('frontend', 'Is Deleted'),
            'deleted_at' => Yii::t('frontend', 'Deleted At'),
        ];
    }
    
    /**
     * @return $this|\yii\db\ActiveQuery
     */
    public static function find()
    {
        return parent::find()->where(['is_deleted' => false]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImei()
    {
        return $this->hasOne(Imei::className(), ['id' => 'mashine_id']);
    }

    /**
     * Gets current state of the machine
     * 
     * @return string|null
     */
    public function getState()
    {
        if (array_key_exists($this->current_status, $this->current_state)) {

            return $this->current_state[$this->current_status];
        }

        return null;
    }
}

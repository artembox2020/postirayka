<?php

namespace frontend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "wm_mashine_data".
 *
 * @property int $id
 * @property int $mashine_id
 * @property string $type_mashine
 * @property int $number_device
 * @property int $level_signal
 * @property int $bill_cash
 * @property int $door_position
 * @property int $door_block_led
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property int $is_deleted
 * @property int $deleted_at
 * @property int $current_status
 * @property string $display
 * @property int $ping
 * @property int $total_cash
 *
 * @property WmMashine $wmMashine
 */
class WmMashineData extends \yii\db\ActiveRecord
{
    /** @var array $current_state */
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
        return 'wm_mashine_data';
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
            [['mashine_id', 'number_device',
                'level_signal', 'bill_cash',
                'door_position', 'door_block_led',
                'status',
                'current_status',
                'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['type_mashine', 'display'], 'string', 'max' => 255],
            [['mashine_id'], 'exist', 'skipOnError' => true, 'targetClass' => WmMashine::className(), 'targetAttribute' => ['mashine_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('frontend', 'ID'),
            'mashine_id' => Yii::t('frontend', 'Wm Mashine ID'),
            'type_mashine' => Yii::t('frontend', 'Type Mashine'),
            'number_device' => Yii::t('frontend', 'Number Device'),
            'level_signal' => Yii::t('frontend', 'Level Signal'),
            'bill_cash' => Yii::t('frontend', 'Bill Cash'),
            'door_position' => Yii::t('frontend', 'Door Position'),
            'door_block_led' => Yii::t('frontend', 'Door Block Led'),
            'status' => Yii::t('frontend', 'Status'),
            'created_at' => Yii::t('frontend', 'Created At'),
            'updated_at' => Yii::t('frontend', 'Updated At'),
            'is_deleted' => Yii::t('frontend', 'Is Deleted'),
            'deleted_at' => Yii::t('frontend', 'Deleted At'),
            'current_status' => Yii::t('frontend', 'Current Status'),
            'display' => Yii::t('frontend' ,'Display'),
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
    public function getWmMashine()
    {
        return $this->hasOne(WmMashine::className(), ['id' => 'mashine_id']);
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
    
    /**
     * Gets last encashment date  and sum, before timestamp accepted
     * 
     * @param int $mashineId
     * @param timestamp $timestampBefore
     * @return array|bool
     */
    public function getDateAndSumLastEncashmentByMashineId($mashineId, $timestampBefore)
    {
        $query = WmMashineData::find()->andWhere(['mashine_id' => $mashineId])
                                 ->andWhere(['bill_cash' => 0])
                                 ->andWhere(['<', 'created_at', $timestampBefore])
                                 ->orderBy(['created_at' => SORT_DESC])
                                 ->limit(1);

        $item = $query->one();

        if ($item) {
            $resultQuery = WmMashineData::find()->andWhere(['mashine_id' => $mashineId])
                                           ->andWhere(['<', 'created_at', $item->created_at])
                                           ->andWhere(['!=', 'bill_cash', 0])
                                           ->orderBy(['created_at' => SORT_DESC])
                                           ->limit(1);
            $resultItem = $resultQuery->one();

            if ($resultItem) {

                return [
                    'created_at' => $resultItem->created_at,
                    'bill_cash' => $resultItem->bill_cash
                ];
            }
        }

        return false;
    }

    /**
     * Gets encashment history by mashine id and timestamps
     * 
     * @param int $mashineId
     * @param timestamp $start
     * @param  timestamp $end
     * @return array
     */
    public function getEncashmentHistoryByMashineId($mashineId, $start, $end)
    {
        $history = [];
        $bhSummarySearch = new BalanceHolderSummarySearch();
        while($end > $start) {
            $encashmentInfo = $this->getDateAndSumLastEncashmentByMashineId($mashineId, $end);
            if (empty($encashmentInfo) || $encashmentInfo['created_at'] < $start) {

                break;
            }

            $end = $encashmentInfo['created_at'];
            $dayBeginningTimestamp = $bhSummarySearch->getDayBeginningTimestampByTimestamp($end);
            $history[$dayBeginningTimestamp][] = $encashmentInfo;
        }

        return $history;
    }
}

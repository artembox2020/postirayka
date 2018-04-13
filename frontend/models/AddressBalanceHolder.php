<?php

namespace frontend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "address_balance_holder".
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property int $floor
 * @property int $balance_holder_id
 * @property int $created_at
 * @property int $updated_at
 * @property int $is_deleted
 * @property int $deleted_at
 *
 * @property BalanceHolder $balanceHolder
 * @property Imei[] $imeis
 */
class AddressBalanceHolder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'address_balance_holder';
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
            TimestampBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['floor', 'balance_holder_id', 'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['balance_holder_id'], 'required'],
            [['name', 'address'], 'string', 'max' => 255],
            [['is_deleted'], 'string', 'max' => 1],
            [['balance_holder_id'], 'exist', 'skipOnError' => true, 'targetClass' => BalanceHolder::className(), 'targetAttribute' => ['balance_holder_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('frontend', 'ID'),
            'name' => Yii::t('frontend', 'Name'),
            'address' => Yii::t('frontend', 'Address'),
            'floor' => Yii::t('frontend', 'Floor'),
            'balance_holder_id' => Yii::t('frontend', 'Balance Holder ID'),
            'created_at' => Yii::t('frontend', 'Created At'),
            'updated_at' => Yii::t('frontend', 'Updated At'),
            'is_deleted' => Yii::t('frontend', 'Is Deleted'),
            'deleted_at' => Yii::t('frontend', 'Deleted At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBalanceHolder()
    {
        return $this->hasOne(BalanceHolder::className(), ['id' => 'balance_holder_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImeis()
    {
        return $this->hasMany(Imei::className(), ['address_id' => 'id']);
    }
}
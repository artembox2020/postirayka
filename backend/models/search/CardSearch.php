<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use frontend\models\CustomerCards;
use frontend\models\Transactions;
use frontend\models\Imei;
use frontend\models\AddressImeiData;
use frontend\models\AddressBalanceHolder;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for Cards search.
 */
class CardSearch extends CustomerCards
{
    const RECORDS_PER_PAGE = 20;
    const DATE_FORMAT = 'd-m-y H:i';

    public function rules()
    {
        return [
            [['card_no', 'discount', 'status', 'created_at'], 'integer'],
            [['balance'], 'number'],
        ];
    }

    public function scenarios()
    {
        return parent::scenarios(); // TODO: Change the autogenerated stub
    }

    /**
     * Main search method
     * 
     * @return yii\data\ActiveDataProvider
     */
    public function search($params)
    {
        $query = CustomerCards::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => self::RECORDS_PER_PAGE
            ],
        ]);

        if ( !($this->load($params) && $this->validate()) ) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'card_no' => $this->card_no,
            'balance' => $this->balance,
            'discount' => $this->discount,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ]);

        return $dataProvider;
    }

    /**
     * Search user method
     * 
     * @return yii\data\ActiveDataProvider
     */
    public function searchUser($params)
    {
        $query = CustomerCards::find()->select(['user_id'])->distinct()->andWhere(['not', ['user_id' => null]]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => self::RECORDS_PER_PAGE
            ],
        ]);

        if ( !($this->load($params) && $this->validate()) ) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'user_id' => $this->user_id,
        ]);

        return $dataProvider;
    }

    /**
     * Finds address by card number
     * 
     * @param int $cardNo
     * 
     * @return string|null
     */
    public function findAddressByCardNo($cardNo)
    {
        $transaction = Transactions::findLastTransactionByCardNo($cardNo, 'imei');

        if (empty($transaction)) {

            return null;
        }

        $imei = Imei::find()->andWhere(['imei' => $transaction->imei])->limit(1)->one();
        $addressImeiData = new AddressImeiData();

        if (empty($imei->id)) {

            return null;
        }

        $address_id = $addressImeiData->findAddressIdByImeiAndTimestamp($imei, time());

        if (empty($address_id)) {

            return null;
        }
        
        $address = AddressBalanceHolder::find()->where(['id' => $address_id])->limit(1)->one();
        
        if (empty($address)) {

            return null;
        }

        return $address->address;
    }

    /**
     * Finds last transaction date by card number
     * 
     * @param int $cardNo
     * 
     * @return string|null
     */
    public function findLastActivityByCardNo($cardNo)
    {
        $transaction = Transactions::findLastTransactionByCardNo($cardNo, 'created_at');

        if (empty($transaction)) {

            return null;
        }

        return date(self::DATE_FORMAT, $transaction->created_at);
    }

    /**
     * Finds card numbers by user id
     * 
     * @param int $userId
     * 
     * @return array
     */
    public function findCardsByUserId($userId)
    {
        $query = CustomerCards::find()->select(['card_no'])->andWhere(['user_id' => $userId]);
        $items = $query->all();

        return array_unique(ArrayHelper::getColumn($items, 'card_no'));
    }

    /**
     * Finds last circulation value by user id
     * 
     * @param int $userId
     * 
     * @return double|null
     */
    public function findLastCirculationByUserId($userId)
    {
        $cardsNo = $this->findCardsByUserId($userId);
        $transaction = Transactions::findLastTransactionByCardNo($cardsNo, 'amount');

        if (empty($transaction)) {

            return null;
        }

        return $transaction->amount;
    }
}

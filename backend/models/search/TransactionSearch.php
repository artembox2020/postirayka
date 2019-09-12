<?php

namespace backend\models\search;

use frontend\models\Transactions;
use frontend\models\CustomerCards;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Class TransactionSearch
 * @package backend\models\search
 */
class TransactionSearch extends Transactions
{
    const RECORDS_PER_PAGE = 20;

    public function rules()
    {
        return [
            [['imei', 'operation_time'], 'string'],
            [['operation', 'created_at'], 'integer'],
            [['amount'], 'number']
        ];
    }

    public function scenarios()
    {
        return parent::scenarios(); // TODO: Change the autogenerated stub
    }

    /**
     * main search method
     * 
     * @param int $card_no
     * @param array $params
     * @param bool $limitHistory
     * 
     * @return \yii\data\ActiveDataProvider
     */
    public function search($card_no, $params, $limitHistory = false)
    {
        $query = Transactions::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => self::RECORDS_PER_PAGE
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC]
            ]
        ]);

        $removeKeys = ['cardNo', 'userId', 'page', 'per-page', 'sort'];
        $params = array_diff_key($params, array_flip($removeKeys));

        if (!empty($params) && !($this->load($params) && $this->validate())) {

            return $dataProvider;
        }

        if (!empty($card_no)) {
            $query->andFilterWhere(['transactions.card_no' => $card_no]);
            $query = $this->limitHistory($card_no, $query, $limitHistory);
        } else {
            $query->andWhere('0=1');
        }

        $query->andFilterWhere([
            'imei' => $this->imei,
            'operation' => $this->operation,
            'operation_time' => $this->operation_time,
            'amount' => $this->amount,
            'created_at' => $this->created_at,
        ]);

        return $dataProvider;
    }

    /**
     * excludes data before card assignment
     * 
     * @param array $card_no
     * @param yii\db\ActiveDbQuery $query
     * @param bool $limitHistory
     * 
     * @return \yii\db\ActiveDbQuery
     */
    private function limitHistory($card_no, $query, $limitHistory)
    {
        if (!$limitHistory) {

            return $query;
        }

        $orConditions = ['or'];

        foreach ($card_no as $cardNo) {
            $card = CustomerCards::find()->andWhere(['card_no' => $cardNo])->limit(1)->one();
            $orConditions[] = ['and', ['>=', 'created_at', $card->created_at], ['card_no' => $card->card_no]];
        }
        $query->andWhere($orConditions);

        return $query;
    }
}
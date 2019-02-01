<?php

namespace frontend\models;
use yii\db\ActiveRecord;
use frontend\services\globals\Entity;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "address_imei_data".
 *
 * @property integer $address_id
 * @property integer $imei_id
 * @property int $created_at
 * @property int $updated_at
 * @property int $is_deleted
 * @property int $deleted_at
 */
class AddressImeiData extends ActiveRecord
{
    const INFINITY = 4000000000;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'address_imei_data';
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
            [['address_id', 'imei_id', 'created_at', 'updated_at', 'deleted_at'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'address_id' => Yii::t('frontend', 'Address ID'),
            'imei_id' => Yii::t('frontend', 'Imei ID'),
            'created_at' => Yii::t('frontend', 'Created At'),
            'updated_at' => Yii::t('frontend', 'Updated At'),
            'is_deleted' => Yii::t('frontend', 'Is Deleted'),
            'deleted_at' => Yii::t('frontend', 'Deleted At'),
        ];
    }

    /**
     * Writes log to table
     * 
     * @param int $imeiId
     * @param int $addressId
     * @return int
     */
    public function makeLog($imeiId, $addressId)
    {
        $addressImei = new AddressImeiData();
        $addressImei->address_id = $addressId;
        $addressImei->imei_id = $imeiId;
        $addressImei->is_deleted = false;
        $addressImei->save();

        return $addressImei->id;
    }

    /**
     * Writes item to the table and  returns its id
     * 
     * @param int $imeiId
     * @param int $addressId
     * @return int
     */
    public function createLog($imeiId, $addressId)
    {
        // if empty imei then bind anyway
        if (empty($imeiId)) {

            return $this->makeLog($imeiId, $addressId);
        }

        $query = AddressImeiData::find();
        $addressImeiItem = $query->andWhere(['imei_id' => $imeiId])
                                 ->orderBy(['created_at' => SORT_DESC])
                                 ->limit(1)
                                 ->one();

        // check whether imei has no already the same address binding
        if (!$addressImeiItem || $addressImeiItem->address_id != $addressId) {

            return $this->makeLog($imeiId, $addressId);
        }

        return 0;
    }

    /**
     * Gets next imei id by address and timestamp (find last binding of the day)
     * 
     * @param int $addressId
     * @param int $timestamp
     * @return array
     */
    public function getNextImeiIdByAddressAndTimestamp($addressId, $timestamp)
    {
        global $nextImeiIdByAddressAndTimestamp;

        if (isset($nextImeiIdByAddressAndTimestamp[$addressId.'-'.$timestamp])) {

            return $nextImeiIdByAddressAndTimestamp[$addressId.'-'.$timestamp];
        }

        $query = AddressImeiData::find();
        $item = $query->andWhere(['address_id' => $addressId])
                      ->andWhere(['>', 'created_at', $timestamp])
                      ->orderBy(['created_at' => SORT_ASC])
                      ->limit(1)
                      ->one();

        if (!$item) {

            $nextImeiIdByAddressAndTimestamp[$addressId.'-'.$timestamp] = [];

            return [];
        }

        $nextDayTimestamp = $this->getNextDayBeginningByTimestamp($item->created_at);

        $query = AddressImeiData::find();
        $item = $query->andWhere(['address_id' => $addressId])
                      ->andWhere(['<', 'created_at', $nextDayTimestamp])
                      ->orderBy(['created_at' => SORT_DESC])
                      ->limit(1)
                      ->one();

        if ($item->imei_id == 0) {

            $nextImeiIdByAddressAndTimestamp[$addressId.'-'.$timestamp] = [
                'imei_id' => $item->imei_id,
                'created_at' => $item->created_at,
                'id' => $item->id
            ];

            return $nextImeiIdByAddressAndTimestamp[$addressId.'-'.$timestamp];
        }

        $query = AddressImeiData::find();
        $itemImei = $query->andWhere(['imei_id' => $item->imei_id])
                      ->andWhere(['<', 'created_at', $nextDayTimestamp])
                      ->orderBy(['created_at' => SORT_DESC])
                      ->limit(1)
                      ->one();

        if ($itemImei->id != $item->id) {

            return $this->getNextImeiIdByAddressAndTimestamp($addressId, $item->created_at);
        }

        $nextImeiIdByAddressAndTimestamp[$addressId.'-'.$timestamp] = [
            'imei_id' => $item->imei_id,
            'created_at' => $item->created_at,
            'id' => $item->id
        ];

        return $nextImeiIdByAddressAndTimestamp[$addressId.'-'.$timestamp];
    }

    /**
     * Gets imei id by address and timestamp
     * 
     * @param int $addressId
     * @param int $timestamp
     * @return int
     */
    public function getImeiIdByAddressTimestamp($addressId, $timestamp)
    {
        $historyBeginning = $this->getHistoryBeginning($addressId);
        $address = AddressBalanceHolder::find()->where(['id' =>$addressId])->one();

        if ($historyBeginning > $timestamp) {

            $currentImei = $this->getCurrentImeiIdByAddress($addressId, $address->status);

            if (empty($currentImei)) {

                return 0;
            }

            return $currentImei->id;
        }

        $imeiId = 0;
        $imeiInfo = $this->getNextImeiIdByAddressAndTimestamp($addressId, $historyBeginning - 1);

        while (!empty($imeiInfo)) {

            if ($imeiInfo['created_at'] <= $timestamp) {
                $imeiId = $imeiInfo['imei_id'];
            } else {
                break;
            }

            $imeiInfo = $this->getNextImeiIdByAddressAndTimestamp($addressId, $imeiInfo['created_at']);
        }

        return $imeiId;
    }
    
    /**
     * Gets next address id by imei_id and timestamp (find last binding of the day)
     * 
     * @param int $imeiId
     * @param int $timestamp
     * @return array
     */
    public function getNextAddressIdByImeiAndTimestamp($imeiId, $timestamp)
    {
        $query = AddressImeiData::find();
        $item = $query->andWhere(['imei_id' => $imeiId])
                      ->andWhere(['>', 'created_at', $timestamp])
                      ->orderBy(['created_at' => SORT_ASC])
                      ->limit(1)
                      ->one();

        if (!$item) {

            return [];
        }

        $nextDayTimestamp = $this->getNextDayBeginningByTimestamp($item->created_at);

        $query = AddressImeiData::find();
        $item = $query->andWhere(['imei_id' => $imeiId])
                      ->andWhere(['<', 'created_at', $nextDayTimestamp])
                      ->orderBy(['created_at' => SORT_DESC])
                      ->limit(1)
                      ->one();

        if ($item->address_id == 0) {

            return [
                'address_id' => $item->address_id,
                'created_at' => $item->created_at,
                'id' => $item->id
            ];
        }

        $query = AddressImeiData::find();
        $itemImei = $query->andWhere(['address_id' => $item->address_id])
                      ->andWhere(['<', 'created_at', $nextDayTimestamp])
                      ->orderBy(['created_at' => SORT_DESC])
                      ->limit(1)
                      ->one();

        if ($itemImei->id != $item->id) {

            return $this->getNextAddressIdByImeiAndTimestamp($imeiId, $item->created_at);
        }

        return [
            'address_id' => $item->address_id,
            'created_at' => $item->created_at,
            'id' => $item->id
        ];
    }

    /**
     * Gets current imei by address 
     * 
     * @param int $addressId
     * @param int $addressStatus
     * @return int
     */
    public function getCurrentImeiIdByAddress($addressId, $addressStatus)
    {
        global $currentImeiByAddress;

        if (empty($currentImeiByAddress[$addressId.'-'.$addressStatus])) {

            $imei = Imei::find()->andWhere(['address_id' => $addressId, 'status' => $addressStatus])->limit(1)->one();
            $currentImeiByAddress[$addressId.'-'.$addressStatus] = $imei;
        }

        return $currentImeiByAddress[$addressId.'-'.$addressStatus];
    }

    /**
     * Gets history beginning by address id
     * 
     * @param int $addressId
     * @return int
     */
    public function getHistoryBeginning($address_id)
    {
        global $historyBeginningByAddressId;

        if (empty($historyBeginningByAddressId[$address_id])) {

            $item = AddressImeiData::find()->andWhere(['address_id' => $address_id])
                                           ->orderBy(['created_at' => SORT_ASC])
                                           ->limit(1)
                                           ->one();

            $historyBeginningByAddressId[$address_id] = $item ? $item->created_at : self::INFINITY;
        }

        return $historyBeginningByAddressId[$address_id];
    }

    /**
     * Gets next day beginning timestamp by timestamp
     * 
     * @param timestamp $timestamp
     * @return int
     */
    public function getNextDayBeginningByTimestamp($timestamp)
    {
        $Y = date('Y', $timestamp);
        $m = date('m', $timestamp);
        $d = date('d', $timestamp);

        return strtotime($Y.'-'.$m.'-'.$d.' 00:00:00') + 3600 *24;
    }

    /**
    * Makes mashine query item by timestamps and imei id
    *
    * @param timestamp $start
    * @param timestamp $end
    * @param int $imeiId
    * @return array 
    */
    public function makeWmMashineQueryItem($start, $end, $imeiId)
    {
        global $mashineQueryItem;

        if (!empty($mashineQueryItem[$start.'-'.$end.'-'.$imeiId])) {

            return $mashineQueryItem[$start.'-'.$end.'-'.$imeiId];
        }

        $bhSummarySearch = new BalanceHolderSummarySearch();

        if (empty($imeiId)) {

            $query = false;
        } else {

            $query = $bhSummarySearch->getAllMashinesQueryByTimestamps($start, $end, $imeiId);
        }

        $mashineQueryItem[$start.'-'.$end.'-'.$imeiId] = [
            'created_at' => $start,
            'query' => $query,
            'imei_id' => $imeiId
        ];

        return $mashineQueryItem[$start.'-'.$end.'-'.$imeiId];
    }

    /**
     * Gets all mashine queries info as array by address and timestamp intervals
     * 
     * @param int $addressId
     * @param int $addressStatus
     * @param timestamp $start
     * @param timestamp $end
     * @return array
     */
    public function getWmMashinesQueries($addressId, $addressStatus, $start, $end)
    {
        $wmMashinesQueries = [];
        $imeiId = $this->getImeiIdByAddressTimestamp($addressId, $start);

        while (!empty($imeiInfo = $this->getNextImeiIdByAddressAndTimestamp($addressId, $start))) {
            $wmMashinesQueries[] = $this->makeWmMashineQueryItem($start-1, $imeiInfo['created_at'], $imeiId);
            $imeiId = $imeiInfo['imei_id'];
            $start = $imeiInfo['created_at'];

            if ($start - 1 >= $end) {
                $lastImeiInfo = [];
                break;
            }

            $lastImeiInfo = $imeiInfo;
        }

        if (!empty($lastImeiInfo)) {
            $wmMashinesQueries[] = $this->makeWmMashineQueryItem($start-1, $end, $imeiId);
        }

        if (empty($wmMashinesQueries) && !empty($imei = $this->getCurrentImeiIdByAddress($addressId, $addressStatus))) {
            $bhSummarySearch = new BalanceHolderSummarySearch();
            $query = $bhSummarySearch->getAllMashinesQueryByTimestamps($start-1, $end, $imei->id);
            $wmMashinesQueries[] = [ 'created_at' => $start, 'query' => $query, 'imei_id' => $imei->id];
        }

        return $wmMashinesQueries;
    }

    /**
     * Gets WM mashines count by queries info
     * 
     * @param array $queriesInfo
     * @return int
     */
    public function getWmMashinesCountByMashineQueries($queriesInfo)
    {
        $mashineIds = [];
        foreach ($queriesInfo as $queryInfo) {

            if (empty($queryInfo['query'])) {
                continue;
            }

            $mashines = $queryInfo['query']->all();
            $mashineIds = array_merge($mashineIds, array_diff(ArrayHelper::getColumn($mashines, 'id'), $mashineIds));
        }

        return count($mashineIds);
    }

    /**
     * Gets WM mashines count by year and month
     * 
     * @param int $year
     * @param int $month
     * @param AddressBalanceHolder $address
     * @return int
     */
    public function getWmMashinesCountByYearMonth($year, $month, $address)
    {
         $bhSummarySearch = new BalanceHolderSummarySearch();
         $timestamps = $bhSummarySearch->getTimestampByYearMonth($year, $month);
         $queries = $this->getWmMashinesQueries($address->id, $address->status, $timestamps['start'], $timestamps['end']);

         return $this->getWmMashinesCountByMashineQueries($queries);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public static function find()
    {
        return parent::find()->where(['address_imei_data.is_deleted' => false]);
    }
    
    /**
     * @return array
     */
    public function getAddressHistoryByImei($imei)
    {
        if (empty($imei)) {
            
            return [];
        }

        $timestamp = 0;
        $historyInfo = [];
        $addressInfo = $this->getNextAddressIdByImeiAndTimestamp($imei->id, $timestamp);
        
        while (!empty($addressInfo)) {
            $address = AddressBalanceHolder::find()->where(['id' => $addressInfo['address_id']])->one();
            $historyInfo[] = [
                'address_id' => $addressInfo['address_id'],
                'created_at' => $addressInfo['created_at'],
                'address_name' => !empty($address) ? $address->address : false,
                'imei' => $imei->imei
            ];
            $timestamp = $addressInfo['created_at'];
            $addressInfo = $this->getNextAddressIdByImeiAndTimestamp($imei->id, $timestamp);
        }

        ArrayHelper::multisort($historyInfo, ['created_at'], [SORT_DESC]);

        return $historyInfo;
    }

    /**
     * @return array
     */
    public function getImeiHistoryByAddress($address)
    {
        if (empty($address)) {

            return [];
        }

        $timestamp = 0;
        $historyInfo = [];
        $imeiInfo = $this->getNextImeiIdByAddressAndTimestamp($address->id, $timestamp);

        while (!empty($imeiInfo)) {
            $imei = Imei::find()->where(['id' => $imeiInfo['imei_id']])->one();
            $historyInfo[] = [
                'imei_id' => $imeiInfo['imei_id'],
                'created_at' => $imeiInfo['created_at'],
                'imei' => !empty($imei) ? $imei->imei : false,
                'address_name' => $address->address
            ];
            $timestamp = $imeiInfo['created_at'];
            $imeiInfo = $this->getNextImeiIdByAddressAndTimestamp($address->id, $timestamp);
        }

        ArrayHelper::multisort($historyInfo, ['created_at'], [SORT_DESC]);

        return $historyInfo;
    }
    
    public function makeHistoryFromItems($items)
    {
        $historyInfo = [];
        $imeiIds = [];
        $zeroAddressIds = [];
        $addressIds = [];

        foreach ($items as $item) {

            $imei = Imei::find()->where(['id' => $item->imei_id])->one();
            $address = AddressBalanceHolder::find()->where(['id' => $item->address_id])->one();

            // check whether binding for address already set
            if (in_array($item->address_id, $addressIds) && !empty($item->address_id)) {

                continue;
            }

            // check whether binding for imei already set
            if (in_array($item->imei_id, $imeiIds)) {
                if (empty($item->imei_id)) {
                    if (in_array($item->address_id, $zeroAddressIds)) {

                        continue;
                    }
                } else {
                    continue;
                }
            }

            $historyInfo[] = [
                'imei_id' => $item['imei_id'],
                'created_at' => $item['created_at'],
                'imei' => !empty($imei) ? $imei->imei : false,
                'address_name' => !empty($address) ? $address->address : false
            ];

            $imeiIds[] = $item->imei_id;

            if (empty($item->imei_id)) {
                $zeroAddressIds[] = $item->address_id;
            }

            $addressIds[] = $item->address_id;
        }

        return $historyInfo;
    }

    /**
     * @return array
     */
    public function getHistoryByTimestamp($timestamp)
    {
        $bhSummarySearch = new BalanceHolderSummarySearch();
        $startTimestamp = $bhSummarySearch->getDayBeginningTimestampByTimestamp($timestamp);
        $endTimestamp = $startTimestamp + 3600*24;
        $query = AddressImeiData::find();
        $items = $query->andWhere(['>=', 'created_at', $startTimestamp])
                       ->andWhere(['<', 'created_at', $endTimestamp])
                       ->orderBy(['created_at' => SORT_DESC])
                       ->all();

        return $this->makeHistoryFromItems($items);
    }

    /**
     * @return timestamp
     */
    public function getAbsoluteHistoryBeginning()
    {
        $item = AddressImeiData::find()->orderBy(['created_at' => SORT_ASC])->limit(1)->one();

        if (empty($item)) {

            return self::INFINITY;
        }

        return $item->created_at;
    }

    /**
     * @return array
     */
    public function getHistory()
    {
        $bhSummarySearch = new BalanceHolderSummarySearch();
        $timestamp = $this->getAbsoluteHistoryBeginning();

        if ($timestamp == self::INFINITY) {

            return [];
        }

        $timestamp = $bhSummarySearch->getDayBeginningTimestampByTimestamp($timestamp);
        $stepInterval = 3600 * 24;
        $historyInfo = [];

        while ($timestamp < time() + Jlog::TYPE_TIME_OFFSET) {
            $historyInfo = array_merge($historyInfo, $this->getHistoryByTimestamp($timestamp));
            $timestamp += $stepInterval; 
        }

        ArrayHelper::multisort($historyInfo, ['created_at'], [SORT_DESC]);

        return $historyInfo;
    }
}

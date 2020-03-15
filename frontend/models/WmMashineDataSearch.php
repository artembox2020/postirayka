<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use frontend\models\WmMashineData;
use frontend\services\globals\QueryOptimizer;
use yii\helpers\ArrayHelper;
use frontend\services\globals\DateTimeHelper;
use frontend\services\globals\Entity;
use frontend\models\BalanceHolderSummarySearch;

/**
 * WmMashineDataSearch represents the model behind the search form of `frontend\models\WmMashineData`.
 */
class WmMashineDataSearch extends WmMashineData
{
    const TYPE_STATUS_DISCONNECTED = 0;
    const TYPE_MAX_STATUS_CODE = 26;
    const TYPE_MIN_STATUS_CODE = -2;

    const TYPE_WORK_INTERVAL_STEP = 300;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'wm_mashine_id', 'number_device', 'level_signal', 'bill_cash', 'door_position', 'door_block_led', 'status', 'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['type_mashine', 'is_deleted'], 'safe'],
            [['display'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = WmMashineData::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'wm_mashine_id' => $this->wm_mashine_id,
            'number_device' => $this->number_device,
            'level_signal' => $this->level_signal,
            'bill_cash' => $this->bill_cash,
            'door_position' => $this->door_position,
            'door_block_led' => $this->door_block_led,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'display' => $this->display,
        ]);

        $query->andFilterWhere(['like', 'type_mashine', $this->type_mashine])
            ->andFilterWhere(['like', 'is_deleted', $this->is_deleted]);

        return $dataProvider;
    }

    /**
     * Check monitoring for updates
     *
     * @param string $deviceIds
     * @param timestamp $timestamp
     * @return int
     */
    public function checkMonitoringWmUpdate($deviceIds, $timestamp)
    {
        $arrayDeviceIds = explode(",", $deviceIds);
        $query = WmMashineData::find()->andWhere(['mashine_id' => $arrayDeviceIds])
                                      ->andWhere(['>=', 'created_at', $timestamp]);

        return $query->count();
    }

    /**
     * Gets query, mashines by timestamp
     *
     * @param int $start
     * @param int $end
     *
     * @return ActiveDbQuery
     */
    public function getAllMashinesQueryByTimestamps(int $start, int $end)
    {
        $entity = new Entity();
        $companyId = $entity->getCompanyId();
        $statuses = [WmMashine::STATUS_ACTIVE];
        $query = WmMashine::find()->select(['wm_mashine.id']);
        $query = $query->where(['<', 'wm_mashine.created_at', $end]);
        $query = $query->andWhere(['wm_mashine.company_id' => $companyId, 'wm_mashine.status' => $statuses])
                        ->andWhere(
                            new \yii\db\conditions\OrCondition([
                                ['wm_mashine.is_deleted' => false],
                                new \yii\db\conditions\AndCondition([
                                    ['wm_mashine.is_deleted' => true],
                                    ['>', 'wm_mashine.deleted_at', $start]
                                ])
                            ])
                        );

        $query->leftJoin('imei', 'wm_mashine.imei_id = imei.id');
        $query = $query->andWhere(['<', 'imei.created_at', $end]);
        $query->andWhere(
                    new \yii\db\conditions\OrCondition([
                        ['imei.is_deleted' => false],
                        new \yii\db\conditions\AndCondition([
                            ['imei.is_deleted' => true],
                            ['>', 'imei.deleted_at', $start]
                        ])
                    ])
                );

        $query->andWhere(['imei.status' => Imei::STATUS_ACTIVE]);

        return $query;
    }

    /**
     * Gets all mashines count by timestamps
     *
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    public function getAllMashinesCountByTimestamps(int $start, int $end)
    {
        $query = $this->getAllMashinesQueryByTimestamps($start, $end);

        return $query->count();
    }

    /**
     * Gets query, mashines from wm_masine_data by timestamps
     *
     * @param int $start
     * @param int $end
     * @param array $select
     *
     * @return ActiveDbQuery
     */
    public function getBaseWmMashineDataQueryByTimestamps(int $start, int $end, array $select)
    {
        $query = WmMashineData::find();
        $query = $query->select($select)
                        ->distinct()
                        ->where(['>=', 'created_at', $start])
                        ->andWhere(['<', 'created_at', $end]);

        return $query;
    }

    /**
     * Gets green mashines count by timestamps
     *
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    public function getGreenMashinesCountByTimestamps($start, $end)
    {
        $allMashinesQuery = $this->getAllMashinesQueryByTimestamps($start, $end);
        $allMashines = QueryOptimizer::getItemsByQuery($allMashinesQuery);
        $allMashineIds = ArrayHelper::getColumn($allMashines, 'id');

        $query = $this->getBaseWmMashineDataQueryByTimestamps($start, $end, ['mashine_id']);
        $query = $query->andWhere(['!=', 'current_status', self::TYPE_STATUS_DISCONNECTED]);
        $query = $query->andWhere(['<=', 'current_status', self::TYPE_MAX_STATUS_CODE]);
        $query = $query->andWhere(['>=', 'current_status', self::TYPE_MIN_STATUS_CODE]);

        $dataMashines = QueryOptimizer::getItemsByQuery($query);
        $dataMashineIds = ArrayHelper::getColumn($dataMashines, 'mashine_id');

        $commonMashineIds = array_intersect($allMashineIds, $dataMashineIds);

        return count($commonMashineIds);
    }

    /**
     * Gets grey mashines count by timestamps
     *
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    public function getGreyMashinesCountByTimestamp($start, $end)
    {
        $allMashinesQuery = $this->getAllMashinesQueryByTimestamps($start, $end);
        $allMashines = QueryOptimizer::getItemsCountByQuery($allMashinesQuery);
        $greenMashines = $this->getGreenMashinesCountByTimestamps($start, $end);

        return ($allMashines - $greenMashines);
    }

    /**
     * Gets work mashines count by timestamps
     *
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    public function getWorkMashinesCountByTimestamps($start, $end)
    {
        $allMashinesQuery = $this->getAllMashinesQueryByTimestamps($start, $end);
        $allMashines = QueryOptimizer::getItemsByQuery($allMashinesQuery);
        $allMashineIds = ArrayHelper::getColumn($allMashines, 'id');

        $allowedStatuses = [2, 3, 4, 5, 6, 7, 8];
        $query = $this->getBaseWmMashineDataQueryByTimestamps($start, $end, ['mashine_id']);
        $query = $query->andWhere(['current_status' => $allowedStatuses]);

        $dataMashines = QueryOptimizer::getItemsByQuery($query);
        $dataMashineIds = ArrayHelper::getColumn($dataMashines, 'mashine_id');

        $commonMashineIds = array_intersect($allMashineIds, $dataMashineIds);

        return count($commonMashineIds);
    }

    /**
     * Gets error mashines count by timestamps
     *
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    public function getErrorMashinesCountByTimestamps($start, $end)
    {
        $allMashinesQuery = $this->getAllMashinesQueryByTimestamps($start, $end);
        $allMashines = QueryOptimizer::getItemsByQuery($allMashinesQuery);
        $allMashineIds = ArrayHelper::getColumn($allMashines, 'id');

        $query = $this->getBaseWmMashineDataQueryByTimestamps($start, $end, ['mashine_id']);
        $allowedStatuses = [9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26];
        $query = $query->andWhere(['current_status' => $allowedStatuses]);

        $dataMashines = QueryOptimizer::getItemsByQuery($query);
        $dataMashineIds = ArrayHelper::getColumn($dataMashines, 'mashine_id');

        $commonMashineIds = array_intersect($allMashineIds, $dataMashineIds);

        return count($commonMashineIds);
    }

    /**
     * Gets query current mashines
     *
     * @return ActiveDbQuery
     */
    public function getAllCurrentMashinesQuery()
    {
        $entity = new Entity();
        $companyId = $entity->getCompanyId();
        $statuses = [WmMashine::STATUS_ACTIVE];
        $query = WmMashine::find()->select(['wm_mashine.id', 'current_status', 'wm_mashine.ping'])
                                  ->andWhere(['wm_mashine.status' => $statuses, 'wm_mashine.company_id' => $companyId]);
        $query->leftJoin('imei', 'wm_mashine.imei_id = imei.id');
        $query->andWhere(['imei.is_deleted' => false]);
        $query->andWhere(['imei.status' => Imei::STATUS_ACTIVE]);

        return $query;
    }

    /**
     * Gets all current mashines count
     *
     * @return int
     */
    public function getAllCurrentMashinesCount()
    {
        $query = $this->getAllCurrentMashinesQuery();

        return QueryOptimizer::getItemsCountByQuery($query);
    }

    /**
     * Gets green current mashines count
     *
     * @return int
     */
    public function getGreenCurrentMashinesCount()
    {
        $statuses = [WmMashine::STATUS_ACTIVE];
        $query = $this->getAllCurrentMashinesQuery();
        $query = $query->andWhere(['!=', 'wm_mashine.current_status', self::TYPE_STATUS_DISCONNECTED]);
        $query = $query->andWhere(['<=', 'wm_mashine.current_status', self::TYPE_MAX_STATUS_CODE]);
        $query = $query->andWhere(['>=', 'wm_mashine.current_status', self::TYPE_MIN_STATUS_CODE]);
        $mashines = $query->all();
        $count = 0;

        foreach ($mashines as $mashine) {
            if ($mashine->getActualityClass() == 'ping-actual') {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Gets grey current mashines count
     *
     * @return int
     */
    public function getGreyCurrentMashinesCount()
    {
        $allMashinesCount = $this->getAllCurrentMashinesCount();
        $greenMashinesCount = $this->getGreenCurrentMashinesCount();

        return $allMashinesCount - $greenMashinesCount;
    }

    /**
     * Gets work current mashines count
     *
     * @return int
     */
    public function getWorkCurrentMashinesCount()
    {
        $statuses = [WmMashine::STATUS_ACTIVE];
        $allowedStatuses = [2, 3, 4, 5, 6, 7, 8];
        $query = $this->getAllCurrentMashinesQuery();
        $query = $query->andWhere(['wm_mashine.status' => $statuses, 'current_status' => $allowedStatuses]);
        $mashines = $query->all();
        $count = 0;

        foreach ($mashines as $mashine) {
            if ($mashine->getActualityClass() == 'ping-actual') {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Gets error current mashines count
     *
     * @return int
     */
    public function getErrorCurrentMashinesCount()
    {
        $statuses = [WmMashine::STATUS_ACTIVE];
        $allowedStatuses = [9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26];
        $query = $this->getAllCurrentMashinesQuery();
        $query = $query->andWhere(['wm_mashine.status' => $statuses, 'current_status' => $allowedStatuses]);
        $mashines = $query->all();
        $count = 0;

        foreach ($mashines as $mashine) {
            if ($mashine->getActualityClass() == 'ping-actual') {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Gets day beginning of history
     *
     * @return int
     */
    public function getHistoryDayBeginning()
    {
        $dateTimeHelper = new DateTimeHelper();
        $query = WmMashineData::find()->select(['created_at'])->orderBy(['created_at' => SORT_ASC])->limit(1);
        $item = $query->one();
        $timestamp = $item ? $item->created_at : $dateTimeHelper->getRealUnixTimeOffset(0);

        return $dateTimeHelper->getDayBeginningTimestamp($timestamp);
    }

    /**
     * Gets work percents by wash mashine and timestamps
     *
     * @param WmMashine $mashine
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    public function getWorkPercents($mashine, $start, $end)
    {
        $statuses = [WmMashine::STATUS_ACTIVE];
        $allowedStatuses = [2, 3, 4, 5, 6, 7, 8];

        if ($mashine->created_at > $start) {
            $start = $mashine->created_at;
        }

        if ($mashine->is_deleted && $mashine->deleted_at < $end) {
            $end = $mashine->deleted_at;
        }

        $currStamp = $start;
        $workSecs = 0;

        $query = $this->getBaseWmMashineDataQueryByTimestamps($start, $end, ['created_at']);
        $query->andWhere(['mashine_id' => $mashine->id, 'current_status' => $allowedStatuses, 'status' => $statuses]);
        $query->orderBy(['created_at' => SORT_ASC]);
        $workSecs = $this->getWorkSecsByPacketTimestamps(
            $start, $end, ArrayHelper::getColumn($query->all(), 'created_at')
        );

        $bhSummarySearch = new BalanceHolderSummarySearch();

        return $bhSummarySearch->parseFloat($workSecs/($end-$start)*100, 0);
    }

    /**
     * Gets current time work percents by mashine
     *
     * @param WmMashine $mashine
     *
     * @return int
     */
    public function getCurrentWorkPercents($mashine)
    {
        $allowedStatuses = [2, 3, 4, 5, 6, 7, 8];

        if (empty($mashine->current_status) || !in_array($mashine->current_status, $allowedStatuses)) {

            return 0;
        }

        $item = WmMashineData::find()
                    ->select(['created_at'])
                    ->andWhere(['mashine_id' => $mashine->id])
                    ->limit(1)
                    ->orderBy(['created_at' => SORT_DESC])
                    ->one();

        if (empty($item) || (time() - $item->created_at) > self::TYPE_WORK_INTERVAL_STEP) {

            return 0;
        }

        return 100;
    }

    /**
     * Gets working seconds by timestamps and work points
     *
     * @param int $start
     * @param int $end
     * @param array $timestamps
     * @param int $workSecs
     *
     * @return int
     */
    public function getWorkSecsByPacketTimestamps($start, $end, $timestamps, $workSecs = 0)
    {
        $currStamp = $start;

        if (empty($timestamps)) {

            return $workSecs;
        }

        $diff = $timestamps[0] - $currStamp;

        if ($diff <= self::TYPE_WORK_INTERVAL_STEP) {
            $intervalStep = $end - $currStamp < self::TYPE_WORK_INTERVAL_STEP ? 
                            ($end - $currStamp) : self::TYPE_WORK_INTERVAL_STEP;
            $currStamp += $intervalStep;
        } else {
            $intervalStep = $end - $timestamps[0] < self::TYPE_WORK_INTERVAL_STEP ?
                            ($end - $timestamps[0]) : self::TYPE_WORK_INTERVAL_STEP;
            $currStamp = $timestamps[0] + $intervalStep;
        }

        $workSecs += $intervalStep;

        while(!empty($timestamps) && $timestamps[0] <= $currStamp) {
            array_shift($timestamps);
        }

        return $this->getWorkSecsByPacketTimestamps($currStamp, $end, $timestamps, $workSecs);
    }
}

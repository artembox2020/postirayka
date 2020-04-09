<?php

namespace frontend\models;

use frontend\services\custom\Debugger;
use frontend\services\globals\Entity;
use frontend\services\globals\QueryOptimizer;
use Yii;
use yii\base\Model;
use \frontend\components\responsive\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;

/**
 * Class CbLogSearch
 * @package frontend\models
 */
class CbLogSearch extends CbLog
{
    const PAGE_SIZE = 10;
    const TYPE_ENCASHMENT_STATUS = 8;
    const TYPE_LAST_ENCASHMENT_DATE = '01.01.2019';
    const TYPE_ITEMS_LIMIT = 500;

    public $address;

    const INFINITY = 9999999999999999;
    const ZERO = 0;

    const TWO_HOURS_CORRECTION = 7200;

    public $from_date;
    public $to_date;
    public $mashineNumber;
    public $inputValue = ['date', 'created_at', 'unix_time_offset'];
    public $val2 = ['date', 'created_at', 'unix_time_offset'];

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['date','imei', 'address', 'is_deleted'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates canonical wm_log query
     *
     * @param frontend\services\globals\Entity
     * @param frontend\models\CbLogSearchFilter
     * @param array $params
     *
     * @return \yii\db\Query
     */
    public function getWmLogQuery($entity, $searchFilter, $params)
    {
        $wmLogQuery = (new \yii\db\Query())
            ->select([
                'unix_time_offset', 
                'address_id', 
                'imei', 
                'device',
                'number',
                'rate',
                'signal',
                'status',
                'price',
                'account_money',
                'washing_mode',
                'wash_temperature',
                'spin_type',
                'prewash',
                'rinsing',
                'intensive_wash',
                'created_at'
            ])
            ->from('wm_log')
            ->where(['company_id' => $entity->getCompanyId()]);

        $wmLogQuery = $this->applyCommonFilters($wmLogQuery, $params);

        $wmLogQuery = $searchFilter->applyFilterByConditionMethod(
            $wmLogQuery, 'number', $params, CbLogSearchFilter::FILTER_CATEGORY_NUMERIC
        );

        return $wmLogQuery;
    }

    /**
     * Creates canonical cb_log query
     *
     * @param frontend\services\globals\Entity
     * @param frontend\models\CbLogSearchFilter
     * @param array $params
     *
     * @return \yii\db\Query
     */
    public function getCbLogQuery($entity, $searchFilter, $params)
    {
        $cbLogQuery = (new \yii\db\Query())
            ->select([
                'unix_time_offset',
                'address_id',
                'imei',
                'device',
                'number',
                'rate',
                'signal',
                'status',
                'rate AS price',
                'account_money',
                'notes_billiards_pcs AS washing_mode',
                'fireproof_counter_hrn AS wash_temperature',
                'collection_counter AS spin_type',
                'last_collection_counter AS prewash', 
                'rinsing',
                'intensive_wash',
                'created_at'
            ])
            ->from('cb_log')
            ->where(['company_id' => $entity->getCompanyId()]);

        $cbLogQuery = $this->applyCommonFilters($cbLogQuery, $params);
        $cbLogQuery = $searchFilter->applyFilterByValueMethod($cbLogQuery, 'number', $params);

        return $cbLogQuery;
    }

    /**
     * Appends orderBy sentence, considering order field name
     *
     * @param \yii\db\Query $query
     * @param array $params
     * @param array $orderFieldName
     *
     * @return \yii\db\Query
     */
    public function applyOrder($query, $params, $orderFieldName)
    {
        $sort = $params['sort'];

        if (!empty($sort)) {
            $firstChar = substr($sort, 0, 1);
            $orderDirection = $firstChar == '-' ? SORT_DESC : SORT_ASC;
        } else {
            $orderDirection = SORT_DESC;
        }

        return $query->orderBy([$orderFieldName => $orderDirection]);
    }

    /**
     * Calculates the total number of items
     *
     * @param frontend\services\globals\Entity
     * @param frontend\models\CbLogSearchFilter
     * @param array $params
     *
     * @return int
     */
    public function getLogTotalCount($entity, $searchFilter, $params)
    {
        
        $cbLogCount = (int)$this->getCbLogQuery($entity, $searchFilter, $params)->count();
        $wmLogCount = (int)$this->getWmLogQuery($entity, $searchFilter, $params)->count();

        return $cbLogCount + $wmLogCount;
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
        $entity = new Entity();
        $searchFilter = new CbLogSearchFilter();
        $orderFieldName = $searchFilter->getDateFieldNameByParams($params);

        $defaultOrderAssoc = [$orderFieldName => SORT_DESC];
        $orderFields = [$orderFieldName];

        $wmLogQuery = $this->getWmLogQuery($entity, $searchFilter, $params);
        $this->applyOrder($wmLogQuery, $params, $orderFieldName);
        $wmLogQuery->limit(self::TYPE_ITEMS_LIMIT);

        $cbLogQuery = $this->getCbLogQuery($entity, $searchFilter, $params);
        $this->applyOrder($cbLogQuery, $params, $orderFieldName);
        $cbLogQuery->limit(self::TYPE_ITEMS_LIMIT);

        $query = new \yii\db\Query();
        $query->select('*')->from(['u' => $cbLogQuery->union($wmLogQuery, true)]);
        $this->applyOrder($query, $params, $orderFieldName);

        $dataProvider = new \frontend\components\responsive\data\ActiveDataProvider([
            'totalCount' => self::TYPE_ITEMS_LIMIT,
            'query' => $query,
            'pagination' => [
                'pageSize' => $params['page_size'] ? $params['page_size'] : self::PAGE_SIZE
            ],
            'sort' => [
                'defaultOrder' => [$orderFieldName => SORT_DESC],
                'attributes' => [$orderFieldName]
            ]
        ]);

        $dataProvider->setSort([
        'attributes' => [
            'unix_time_offset' => [
                'asc' => ['unix_time_offset' => SORT_ASC],
                'desc' => ['unix_time_offset' => SORT_DESC],
                'default' => SORT_DESC
            ],
            'created_at' => [
                'asc' => ['created_at' => SORT_ASC],
                'desc' => ['created_at' => SORT_DESC],
                'default' => SORT_DESC
            ],
        ],
        'defaultOrder' => [
            $orderFieldName => SORT_DESC
        ]
        ]);

        $this->load($params);

        return $dataProvider;
    }

    /**
     * Apply filters common for both `wm_log` and `cb_log` tables
     *
     * @param Query $query
     * @param array $params
     *
     * @return Query
     */
    public function applyCommonFilters($query, $params)
    {
        $searchFilter = new CbLogSearchFilter();
        $dateFieldName = $searchFilter->getDateFieldNameByParams($params);

        $query = $this->applyBetweenDateCondition($query, $dateFieldName);

        $query = $searchFilter->applyFilterByValueMethod($query, 'address', $params);
        $query = $searchFilter->applyFilterByConditionMethod($query, 'address', $params, CbLogSearchFilter::FILTER_CATEGORY_COMMON);

        $query = $searchFilter->applyFilterByValueMethod($query, $dateFieldName, $params);
        $query = $searchFilter->applyFilterByConditionMethod($query, $dateFieldName, $params, CbLogSearchFilter::FILTER_CATEGORY_DATE);

        $query = $searchFilter->applyFilterByValueMethod($query, 'imei', $params);
        $query = $searchFilter->applyFilterByConditionMethod($query, 'imei', $params, CbLogSearchFilter::FILTER_CATEGORY_COMMON);

        $query = $searchFilter->applyFilterByValueMethod($query, 'imei', ['inputValue' => $params]);

        $query = $searchFilter->applyFilterByValueMethod($query, 'address', ['inputValue' => $params]);

        $query = $query->andFilterWhere(['number' => $params['wm_mashine_number']]);
        $query = $query->andFilterWhere(['address_id' => $params['address_id']]);

        return $query;
    }

    /**
     * @param $params
     * @return ActiveDataProvider
     */
    public function getEncashment($params)
    {
        $entity = new Entity();
        $searchFilter = new CbLogSearchFilter();

        $query = (new \yii\db\Query())
            ->select(['date',
                'address_id',
                'collection_counter',
                'unix_time_offset',
                'fireproof_counter_hrn'])
            ->from('cb_log')->where(['company_id' => $entity->getCompanyId()]);

        $query->orderBy([
            'date' => SORT_DESC,
        ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => self::PAGE_SIZE
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'date' => $this->date,
            'imei' => $this->imei,
            'status' => $this->status
        ]);

        $query->andFilterWhere(['like', 'date', $this->date])
            ->andFilterWhere(['like', 'imei', $this->imei]);

        return $dataProvider;
    }

    /**
     * Sets basic parameters
     * 
     * @param CbLogSearch $searchModel
     * @param array $params
     * @param array $prms
     * @return array
     */
    public function setParams($searchModel, $params, $prms)
    {
        $jlogSearchModel = new JlogSearch();
        $searchFilter = new CbLogSearchFilter();

        $params = $jlogSearchModel->setParams($searchModel, $params, $prms);
        $params = $this->setLogParams($searchModel, $searchFilter, $params, $prms);

        return $params;
    }

    /**
     * Sets basic log parameters
     * 
     * @param CbLogSearch $searchModel
     * @param CbLogSearchFilter $searchFilter
     * @param array $params
     * @param array $prms
     * @return array
     */
    public function setLogParams($searchModel, $searchFilter, $params, $prms)
    {
        if ($params['type_packet'] == Jlog::TYPE_PACKET_LOG) {
            $dateFieldName = $searchFilter->getDateFieldNameByParams($params);

            if (!empty($params['dp-1-sort'])) {
                if ($params['date_setting'] || (!$params['date_setting'] && empty($params['sort']))) {
                     $params['sort'] = $params['dp-1-sort'];
                }
            }

            if (!empty($params['filterCondition']['date'])) {

                if (empty($params['filterCondition'][$dateFieldName])) {
                    $params['filterCondition'][$dateFieldName] = $params['filterCondition']['date'];
                }

                if (empty($params['val1'][$dateFieldName])) {
                    $params['val1'][$dateFieldName] = $params['val1']['date'];
                }

                if (empty($params['val2'][$dateFieldName])) {
                    $params['val2'][$dateFieldName] = $params['val2']['date'];
                }
            }

            if (!empty($params['address'])) {
                $jlogSearch = new JlogSearch();
                $params['address'] = $jlogSearch->findStaticAddressByString(
                     $params['address']
                );

                if (empty($params['val1'])) {
                    $params['val1']['address'] = $params['address'];
                    $params['val1']['unix_time_offset'] = null;
                    $params['val1']['created_at'] = null;
                    $params['val1']['number'] = null;
                }
            }
        }

        return $params;
    }

    /**
     * Gets 'device' basing on model data 
     * 
     * @param array $model
     * @return string
     */ 
    public function getDeviceView($model)
    {
        if (strtoupper($model['device']) == 'WM') {

            return Yii::t('logs', strtoupper($model['device'])).' '.$model['number'];
        }

        return Yii::t('logs', strtoupper($model['device']));
    }

    /**
     * Gets 'signal' or 'price' basing on model data 
     * 
     * @param array $model
     * @return integer
     */ 
    public function getLevelSignalView($model)
    {
        if (strtoupper($model['device']) == 'WM') {

            return $model['rate'];
        }

        return $model['signal'];
    }

    /**
     * Gets 'status' basing on model data 
     * 
     * @param array $model
     * @return integer
     */ 
    public function getStatus($model)
    {
        if (strtoupper($model['device']) == 'WM') {

            return $this->getWmStatus($model);
        }

        return $this->getCpStatus($model);
    }

    /**
     * Gets 'price' basing on model data 
     * 
     * @param array $model
     * @return integer|null
     */ 
    public function getPrice($model)
    {
        if (strtoupper($model['device']) == 'WM') {

            return $model['price'];
        }

        return null;
    }

    /**
     * Gets 'account_money' basing on model data 
     * 
     * @param array $model
     * @return integer|null
     */ 
    public function getAccountMoney($model)
    {
        if (strtoupper($model['device']) == 'WM') {

            return $model['account_money'];
        }

        return null;
    }

    /**
     * Gets 'notes_billiards_pcs' or 'washing_mode' basing on model data 
     * 
     * @param array $model
     * @return integer
     */    
    public function getNotesBilliardsPcs($model)
    {
        if (strtoupper($model['device']) == 'WM') {

            return $this->getWashingMode($model);
        }

        return $model['washing_mode'];
    }

    /**
     * Gets 'fireproof_counter_hrn' basing on model data 
     * 
     * @param array $model
     * @return double|bool
     */
    public function getFireproofCounterHrn($model)
    {
        if (strtoupper($model['device']) == 'WM') {

            return $this->getWashTemperature($model);
        }

        return round($model['wash_temperature'], 1);
    }

    /**
     * Gets 'collection_counter' basing on model data 
     * 
     * @param array $model
     * @return double|bool
     */
    public function getCollectionCounter($model)
    {
        if (strtoupper($model['device']) == 'WM') {

            return $this->getSpinType($model);
        }

        if ($model['status'] == self::TYPE_ENCASHMENT_STATUS) {
            $nominalsTotal = round($this->getNominalsTotal($model), 1);
            $coinTotal = round($this->getCoinNominalsTotal($model), 1);

            return $nominalsTotal + $coinTotal;
        }

        return round($model['spin_type'], 1);
    }

    /**
     * Gets 'collection_counter' view basing on model data 
     * 
     * @param array $model
     * @return string
     */
    public function getCollectionCounterView($model)
    {
        $unixTimestamp = $model['unix_time_offset'];
        $date = date('d.m.Y', $unixTimestamp);

        return "<span class='encashment-sum' data-timestamp='{$date}'>".$this->getCollectionCounter($model)."</span>";
    }

    /**
     * Gets 'last_collection_counter' basing on model data 
     * 
     * @param array $model
     * @return double|string|bool
     */
    public function getLastCollectionCounter($model)
    {

        if (strtoupper($model['device']) == 'WM') {

            return $this->getAdditionalWashOptions($model);
        }

        return !empty($model['prewash']) ? round($model['prewash'], 1) : null;
    }

    /**
     * Gets wm mashine log status basing on model data 
     * 
     * @param array $model
     * @return string|bool
     */
    public function getWmStatus($model)
    {
        $machine = new \frontend\models\WmLog();
        if (array_key_exists($model['status'], $machine->current_state)) {
            $logState = strtoupper($machine->current_state[$model['status']]);

            return "<span class='$logState'>".Yii::t('logs', $logState)."</span>";
        }

        return false;
    }

    /**
     * Gets cp status basing on model data 
     * 
     * @param array $model
     * @return string|bool
     */
    public function getCpStatus($model)
    {
        $cbLog = new \frontend\models\CbLog();

        if (array_key_exists($model['status'], $cbLog->current_state)) {

            return Yii::t('logs', $cbLog->current_state[$model['status']]);
        }

        return false;
    }

    /**
     * Gets 'washing_mode' basing on model data 
     * 
     * @param array $model
     * @return string|bool
     */
    public function getWashingMode($model)
    {
        $machine = new \frontend\models\WmMashine();
        if (array_key_exists($model['washing_mode'], $machine->washing_mode)) {

            return Yii::t('logs', $machine->washing_mode[$model['washing_mode']]);
        }

        return false;
    }

    /**
     * Gets 'wash_temperature' basing on model data 
     * 
     * @param array $model
     * @return string|bool
     */
    public function getWashTemperature($model)
    {
        $machine = new \frontend\models\WmMashine();

        if (array_key_exists($model['wash_temperature'], $machine->wash_temperature)) {

            return Yii::t('logs', $machine->wash_temperature[$model['wash_temperature']]);
        }

        return false;
    }

    /**
     * Gets 'spin_type' basing on model data 
     * 
     * @param array $model
     * @return string|bool
     */
    public function getSpinType($model)
    {
        $machine = new \frontend\models\WmMashine();

        if (array_key_exists($model['spin_type'], $machine->spin_type)) {

            return Yii::t('logs', $machine->spin_type[$model['spin_type']]);
        }

        return false;
    }

    /**
     * Gets additional options(prewash,rinsing,intensive_wash) basing on model data 
     * 
     * @param array $model
     * @return string|bool
     */
    public function getAdditionalWashOptions($model)
    {
         if ($model['prewash'] == 1) {
            $prewash = 'prewash';
         } else {
            $prewash = '';
        }

        if ($model['rinsing'] == 1) {
            $rinsing = 'rinsing';
        } else {
            $rinsing = '';
        }

        if($model['intensive_wash'] == 1) {
            $intensive_wash = 'intensive_wash';
        } else {
            $intensive_wash = '';
        }

        return Yii::t('logs', $prewash) . ' 
            ' . Yii::t('logs', $rinsing) . ' 
            ' . Yii::t('logs', $intensive_wash);
    }

    /**
     * Gets address string representation basing on model data 
     * 
     * @param array $model
     * @return string|bool
     */
    public function getAddressView($model)
    {
        if (
            empty($model['address_id']) ||
            ! ($address = AddressBalanceHolder::find()->where(['id' => $model['address_id']])->one())
        ) {

            return false;
        }

        $address = $address->address.', '.$address->floor;
        $model = (object)$model;
        $model->address = $address;
        $jlogInitSearch = new JlogInitSearch();

        return $jlogInitSearch->getAddressView($model, Jlog::TYPE_PACKET_LOG);
    }

    /**
     * Applies between date condition to query 
     * 
     * @param ActiveDbQuery $query
     * @param string $dateFieldName
     * 
     * @return ActiveDbQuery
     */
    public function applyBetweenDateCondition($query, $dateFieldName)
    {
        $jlogSearch = new JlogSearch();
        list($timeFrom, $timeTo) = $jlogSearch->makeTimeIntervals($this->from_date, $this->to_date);

        if ($timeFrom > self::ZERO) {
            $query = $query->andWhere(['>=', $dateFieldName, $timeFrom]);
        }

        if ($timeTo < self::INFINITY) {
            $query = $query->andWhere(['<=', $dateFieldName, $timeTo]);
        }

        return $query;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchEncashment($params)
    {
        $entity = new Entity();
        $searchFilter = new CbLogSearchFilter();

        $cbLogQuery = (new \yii\db\Query())
            ->select([
                'id',
                'unix_time_offset',
                'address_id',
                'imei_id',
                'imei',
                'device',
                'status',
                'notes_billiards_pcs AS washing_mode',
                'fireproof_counter_hrn AS wash_temperature',
                'collection_counter AS spin_type',
                'last_collection_counter AS prewash',
                'recount_amount',
                'banknote_face_values',
                'coin_face_values'
            ])
            ->from('cb_encashment')
            ->where(['company_id' => $entity->getCompanyId()])
            ->andWhere(['status' => self::TYPE_ENCASHMENT_STATUS]);

        $cbLogQuery = $this->applyCommonFilters($cbLogQuery, $params);

        $dataProvider = new ActiveDataProvider([
            'query' => $cbLogQuery,
            'pagination' => [
                'pageSize' => $params['page_size'] ? $params['page_size'] : self::PAGE_SIZE
            ],
            'sort' => [
                'defaultOrder' => ['unix_time_offset' => SORT_DESC],
                'attributes' => ['unix_time_offset']
            ]
        ]);

        $this->load($params);

        return $dataProvider;
    }

    /**
     * Gets last item basing on model data 
     * 
     * @param array $model
     * @return CbEncashment
     */
    public function getBaseItem($model) {
        $item = CbEncashment::find()->where(['status' => self::TYPE_ENCASHMENT_STATUS, 'imei_id' => $model['imei_id']])
                     ->andWhere(['<', 'unix_time_offset', $model['unix_time_offset']])
                     ->orderBy(['unix_time_offset' => SORT_DESC])
                     ->limit(1)
                     ->one();

        return $item;
    }

    /**
     * Gets the number of days since last collection 
     * 
     * @param array $model
     * @return integer
     */
    public function getLastCollectionDaysBefore($model)
    {
        $item = $this->getBaseItem($model);
 
        $unix_time_offset = strtotime(self::TYPE_LAST_ENCASHMENT_DATE);    

        if (!empty($item)) {

            $unix_time_offset =  (int)$item->unix_time_offset;
        }

        return round((int)($model['unix_time_offset'] - $unix_time_offset)/(3600*24), 1);
    }

    /**
     * Gets last `fireproof_counter_hrn` basing on model data 
     * 
     * @param array $model
     * @return integer
     */
    public function getLastFireproofCounterHrn($model)
    {
        $item = $this->getBaseItem($model);

        if (!$item) {

            return 0;
        }

        return $item->fireproof_counter_hrn;
    }

    /**
     * Gets difference `fireproof_counter_hrn` - last `fireproof_counter_hrn` - `collection_counter` basing on model data 
     * 
     * @param array $model
     * @return integer
     */
    public function getDifference($model)
    {

        $diff = ($model['wash_temperature'] - $this->getLastFireproofCounterHrn($model) - 2*$this->getCollectionCounter($model));
        $diff += (int)$model['recount_amount'];

        return $diff;
    }

    /**
     * Gets difference view basing on model data 
     * 
     * @param array $model
     * @return integer
     */
    public function getDifferenceView($model)
    {
        if (($difference = $this->getDifference($model)) < 0) {

            return "<span class='difference'>".$difference."</span>";
        } elseif ($difference > 0) {

            return "<span class='difference-green'>".$difference."</span>";
        }

        return "<span>".$difference."</span>";
    }

    /**
     * Gets recount amount basing on model data 
     * 
     * @param array $model
     * @return integer
     */
    public function getRecountAmount($model)
    {
        $recountAmount = $model['recount_amount'];

        if (empty($recountAmount)) {
            $recountAmount = 0;
        }

        return "<input type='number' name='recount_amount' data-id='{$model['id']}' value='{$recountAmount}' />";
    }

    /**
     * Parses `banknote_face_values` basing on model data 
     * 
     * @param array $model
     * @return array
     */
    public function parseBanknoteFaceValues($model)
    {
        if (empty($model['banknote_face_values'])) {

            return [];
        }

        $faceValuesString = $model['banknote_face_values'];
        $faceValuesString = $this->normalizeBanknoteFaceValuesString($faceValuesString);

        $parts = explode("+", $faceValuesString);

        $faceValues = [];

        foreach ($parts as $instance) {
            $part = explode("-", $instance);

            $faceValues[] = ['banknote' => $part[0], 'value' => isset($part[1]) ? $part[1] : 0];
        }

        $dataNominals = [];

        if (!empty($faceValues)) {

            foreach ($faceValues as $nominal) {
                $dataNominals[] = [
                    'nominal' => $nominal['banknote'],
                    'value' => $nominal['value'],
                    'sum' => (int)$nominal['banknote'] * (int)$nominal['value']
                ];
            }
        } else {
            $dataNominals = [];
        }

        return $dataNominals;
    }

    /**
     * Gets sum by nominals basing on model data 
     * 
     * @param array $model
     * @return integer
     */
    public function getNominalsTotal($model)
    {
        $dataNominals = $this->parseBanknoteFaceValues($model);
        $total = 0;

        foreach ($dataNominals as $dataNominal) {
            $total += (int)$dataNominal['sum'];
        }

        return $total;
    }

    /**
     * Gets sum view by nominals basing on model data 
     * 
     * @param array $model
     * @return string
     */
    public function getNominalsTotalView($model)
    {
        $dataNominalsTotal = [
            [
                'total' => $this->getNominalsTotal($model)
            ]
        ];

        $dataProvider = new ArrayDataProvider([
            'allModels' => $dataNominalsTotal,
        ]);

        return Yii::$app->view->render(
            '/encashment-journal/banknote_face_values/total',
            [
                'dataProvider' => $dataProvider
            ]
        );
    }

    /**
     * Gets the number of encashments basing on model data 
     * 
     * @param array $model
     * @return integer
     */
    public function getNumberOfEncashments($model)
    {
        $bhSummarySearch = new BalanceHolderSummarySearch();
        $startTimestamp = $bhSummarySearch->getDayBeginningTimestampByTimestamp($model['unix_time_offset']);
        $endTimestamp = $startTimestamp + 3600*24;
        $quantity = CbEncashment::find()->andWhere(['>=', 'unix_time_offset', $startTimestamp])
                                 ->andWhere(['<', 'unix_time_offset', $endTimestamp])
                                 ->andWhere(['status' => self::TYPE_ENCASHMENT_STATUS])
                                 ->andWhere(['imei_id' => $model['imei_id']])
                                 ->count();

        return $quantity;
    }

    /**
     * Gets address view extended basing on model data 
     * 
     * @param array $model
     * @return string
     */
    public function getAddressViewExtended($model)
    {

        return Yii::$app->view->render(
            '/encashment-journal/banknote_face_values/address_view',
            [
                'address' => $this->getAddressView($model),
                'date' => date('d.m.Y', $model['unix_time_offset']),
                'numberOfEncashments' => $this->getNumberOfEncashments($model)
            ]
        );
    }

    /**
     * Gets address grid view basing on model data 
     * 
     * @param array $model
     * @return string
     */
    public function getAddressGridView($model)
    {
        $searchModel = new CbLogSearch();

        $query = (new \yii\db\Query())->select('*')->from('cb_encashment')->andWhere(['id' => $model['id']]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return Yii::$app->view->render(
            '/encashment-journal/banknote_face_values/address',

            [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider
            ]
        );
        
    }

    /**
     * Gets banknote face values basing on model data 
     * 
     * @param array $model
     * @return string
     */
    public function getBanknoteFaceValuesView($model)
    {
        $searchModel = new CbLogSearch();

        $query = (new \yii\db\Query())->select('*')->from('cb_encashment')->andWhere(['id' => $model['id']]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return Yii::$app->view->render(
            '/encashment-journal/banknote_face_values/index',

            [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider
            ]
        );
    }

    /**
     * Gets the nominals view basing on model data 
     * 
     * @param array $model
     * @return string
     */
    public function getNominalsView($model)
    {
        $dataNominals = $this->parseBanknoteFaceValues($model);

        $dataProvider = new ArrayDataProvider([
            'allModels' => $dataNominals,
        ]);

        $view = Yii::$app->view->render(
            '/encashment-journal/banknote_face_values/nominals',
            [
                'dataProvider' => $dataProvider
            ]
        );

        $view = str_replace(["\n", "<br>", "\r\n", "<br/>"], ["","","",""], $view);

        return $view;
    }

    /**
     * Normalizes `banknote_face_value` basing on model data 
     * 
     * @param sring $banknoteFaceValues
     * @return string
     */
    public function normalizeBanknoteFaceValuesString($banknoteFaceValues)
    {
        if (!empty($banknoteFaceValues)) {
            $banknoteFaceValues = trim($banknoteFaceValues);
        }

        $banknoteFaceValues = str_replace([' ', '/'], ['+', '+'], $banknoteFaceValues);

        return $banknoteFaceValues;
    }

    /**
     * Parses `coin_face_values` basing on model data 
     * 
     * @param array $model
     * @return array
     */
    public function parseCoinFaceValues($model)
    {
        if (empty($model['coin_face_values'])) {

            return [];
        }

        $banknoteModel = ['banknote_face_values' => $model['coin_face_values']];

        return $this->parseBanknoteFaceValues($banknoteModel);
    }

    /**
     * Gets the coin nominals view basing on model data 
     * 
     * @param array $model
     * @return string
     */
    public function getCoinNominalsView($model)
    {
        $dataNominals = $this->parseCoinFaceValues($model);

        $dataProvider = new ArrayDataProvider([
            'allModels' => $dataNominals,
        ]);

        $view = Yii::$app->view->render(
            '/encashment-journal/banknote_face_values/coin_nominals',
            [
                'dataProvider' => $dataProvider
            ]
        );

        $view = str_replace(["\n", "<br>", "\r\n", "<br/>"], ["","","",""], $view);

        return $view;
    }

    /**
     * Gets sum by coin nominals basing on model data 
     * 
     * @param array $model
     * @return integer
     */
    public function getCoinNominalsTotal($model)
    {
        $dataNominals = $this->parseCoinFaceValues($model);
        $total = 0;

        foreach ($dataNominals as $dataNominal) {
            $total += (int)$dataNominal['sum'];
        }

        return $total;
    }

    /**
     * Gets sum view by coin nominals basing on model data 
     * 
     * @param array $model
     * @return string
     */
    public function getCoinNominalsTotalView($model)
    {
        $dataNominalsTotal = [
            [
                'total' => $this->getCoinNominalsTotal($model)
            ]
        ];

        $dataProvider = new ArrayDataProvider([
            'allModels' => $dataNominalsTotal,
        ]);

        return Yii::$app->view->render(
            '/encashment-journal/banknote_face_values/coin_total',
            [
                'dataProvider' => $dataProvider
            ]
        );
    }

    /**
     * Gets last encashment sum and date, as array, before timestampBefore
     * 
     * @param int $imeiId
     * @param int $timestampBefore
     * @return array
     */
    public function getLastEncashmentInfoByImeiId(int $imeiId, int $timestampBefore): array
    {
        $query = (new \yii\db\Query())
            ->select([
                'id',
                'unix_time_offset',
                'address_id',
                'imei_id',
                'imei',
                'status',
                'banknote_face_values',
                'coin_face_values'
            ])
            ->from('cb_encashment')
            ->andWhere(['status' => self::TYPE_ENCASHMENT_STATUS])
            ->andWhere(['imei_id' => $imeiId])
            ->andWhere(['<', 'unix_time_offset', $timestampBefore])
            ->orderBy(['unix_time_offset' => SORT_DESC])
            ->limit(1);

        $model = QueryOptimizer::getItemByQuery($query);
        $sum = $this->getNominalsTotal($model);
        $sum += $this->getCoinNominalsTotal($model);

        return ['created_at' => $model['unix_time_offset'], 'money_in_banknotes' => $sum];
    }

    /**
     * Gets date by timestamp, using UTC timezone
     * Заменили UTC на Europe/Kiev из .env
     *
     * @param array $model
     * @param string $dateFormat
     * @param array $params
     * 
     * @return string
     */
    public function getDateByTimestamp($model, $dateFormat, $params)
    {
        $unixTimeOffset = $this->getTimestampCorrection($model['unix_time_offset']);
        $timestamp = empty($params['date_setting']) ? $unixTimeOffset : $model['created_at'];
        $logTitle = empty($params['date_setting']) ? Yii::t('logs', 'Log Arrival Time') : Yii::t('logs', 'Log Event Time');
        $logTitleTimestamp = empty($params['date_setting']) ? $model['created_at'] : $unixTimeOffset;
        $date = date($dateFormat, $timestamp);
        $logTitleDate = date($dateFormat, $logTitleTimestamp);

        return Yii::$app->view->render(
            '/journal/logs/date-block',
            [
                'date' => $date,
                'logTitle' => $logTitle,
                'logTitleDate' => $logTitleDate
            ]
        );
    }

    /**
     * Gets timestamp correction for `unix_time_offset`
     *
     * @param int $timestamp
     *
     * @return int
     */
    public function getTimestampCorrection(int $timestamp): int
    {

        return $timestamp;
    }
}
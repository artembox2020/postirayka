<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use frontend\models\Jlog;
use frontend\models\WmMashine;
use frontend\models\Imei;
use frontend\services\custom\Debugger;
use frontend\services\globals\Entity;
use frontend\services\globals\EntityHelper;
use frontend\services\parser\CParser;

/**
 * JlogSearch represents the model behind the search form of `frontend\models\Jlog`.
 */
class JlogSearch extends Jlog
{
    const PAGE_SIZE = 10;

    const FILTER_NOT_SET = 0;
    const FILTER_CELL_EMPTY = 1;
    const FILTER_CELL_NOT_EMPTY = 2;
    const FILTER_TEXT_CONTAIN = 3;
    const FILTER_TEXT_NOT_CONTAIN = 4;
    const FILTER_TEXT_START_FROM = 5;
    const FILTER_TEXT_END_WITH = 6;
    const FILTER_TEXT_EQUAL = 7;

    const FILTER_DATE = 8;
    const FILTER_DATE_BEFORE = 9;
    const FILTER_DATE_AFTER = 10;
    const FILTER_DATE_FROM = 22;

    const FILTER_MORE = 11;
    const FILTER_MORE_EQUAL = 12;
    const FILTER_LESS = 13;
    const FILTER_LESS_EQUAL = 14;
    const FILTER_EQUAL = 15;
    const FILTER_NOT_EQUAL = 16;
    const FILTER_BETWEEN = 17;
    const FILTER_NOT_BETWEEN = 18;
    
    const FILTER_CATEGORY_COMMON = 19;
    const FILTER_CATEGORY_DATE = 20;
    const FILTER_CATEGORY_NUMERIC = 21;
    
    const INFINITY = 9999999999999999;
    const ZERO = 0;
    
    const MIN_LEVEL_SIGNAL = -128;

    const ADDRESS_DELIMITER = ' - ';
    public $from_date;
    public $to_date;
    public $mashineNumber;
    public $inputValue = ['date'];
    public $val2 = ['date'];

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
        $entity = new Entity();
        $entityHelper = new EntityHelper();
        $query = $entity->getUnitsQueryPertainCompany(new Jlog());

        $query = $this->applyBetweenDateCondition($query, $this);

        $query = $query->andFilterWhere(['like', 'packet', $this->mashineNumber]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $params['page_size'] ? $params['page_size'] : self::PAGE_SIZE
            ],
            'sort' => [
                'defaultOrder' => ['date' => SORT_DESC],
                'attributes' => [
                    'date', 'address'
                ]
            ]
        ]);

        $dataProvider->sort->attributes['date'] = [
            'asc' => ['unix_time_offset' => SORT_ASC],
            'desc' => ['unix_time_offset' => SORT_DESC],
        ];

        $this->load($params);

        // apply filters by id column

        $query = $this->applyFilterByValueMethod($query, 'id', $params);
        $query = $this->applyFilterByConditionMethod($query, 'id', $params, self::FILTER_CATEGORY_NUMERIC);

        // apply filters by type_packet column

        $query = $this->applyFilterByValueMethod($query, 'type_packet', $params);
        $query = $this->applyFilterByConditionMethod($query, 'type_packet', $params, self::FILTER_CATEGORY_COMMON);

        // apply filters by date column 

        $query = $this->applyFilterByValueMethod($query, 'date', $params);
        $query = $this->applyFilterByConditionMethod($query, 'date', $params, self::FILTER_CATEGORY_DATE);

        // apply filters by address column

        $query = $this->applyFilterByValueMethod($query, 'address', $params);
        $query = $this->applyFilterByConditionMethod($query, 'address', $params, self::FILTER_CATEGORY_COMMON);

        // apply filters by imei column

        $query = $this->applyFilterByValueMethod($query, 'imei', $params);
        $query = $this->applyFilterByConditionMethod($query, 'imei', $params, self::FILTER_CATEGORY_COMMON);

        $addressValue = trim(explode(self::ADDRESS_DELIMITER, $params['address'])[0]);
        $query->andFilterWhere(['like', 'address', $addressValue]);

        return $dataProvider;
    }

    /**
     * Creates data provider instance by mashine id
     *
     * @param array $params
     * @param WmMashine $mashine
     * @return ActiveDataProvider
     */
    public function searchByMashine($params, $mashine)
    {
        $imei = Imei::findOne($mashine->imei_id);
        $params['filterCondition']['imei'] = self::FILTER_TEXT_EQUAL;
        $params['val1']['imei'] = $imei->imei;
        if (empty(Yii::$app->request->queryParams['sort'])) {
            $extraParams = ['sort' => '-date'];
            Yii::$app->request->queryParams = array_merge($extraParams, Yii::$app->request->queryParams);
        }

        return $this->search($params);
    }

    /**
     * Gets array of common filters
     * 
     * @return array
     */
    public static function getCommonFilters()
    {

        return [
            self::FILTER_NOT_SET => Yii::t('frontend', 'FILTER NOT SET'),
            self::FILTER_CELL_EMPTY => Yii::t('frontend', 'FILTER CELL EMPTY'),
            self::FILTER_CELL_NOT_EMPTY => Yii::t('frontend', 'FILTER CELL NOT EMPTY'),
            self::FILTER_TEXT_CONTAIN => Yii::t('frontend', 'FILTER TEXT CONTAIN'),
            self::FILTER_TEXT_NOT_CONTAIN => Yii::t('frontend', 'FILTER TEXT NOT CONTAIN'),
            self::FILTER_TEXT_START_FROM => Yii::t('frontend', 'FILTER TEXT START FROM'),
            self::FILTER_TEXT_END_WITH => Yii::t('frontend', 'FILTER TEXT END WITH'),
            self::FILTER_TEXT_EQUAL => Yii::t('frontend', 'FILTER TEXT EQUAL')
        ];
    }

    /**
     * Gets array of date filters
     * 
     * @return array
     */
    public static function getDateFilters()
    {
        
        return [
            self::FILTER_NOT_SET => Yii::t('frontend', 'FILTER NOT SET'),
            self::FILTER_DATE => Yii::t('frontend', 'FILTER DATE'),
            self::FILTER_DATE_BEFORE => Yii::t('frontend', 'FILTER DATE BEFORE'),
            self::FILTER_DATE_AFTER => Yii::t('frontend', 'FILTER DATE AFTER'),
            self::FILTER_DATE_FROM => Yii::t('frontend', 'FILTER DATE FROM')
        ];
    }

    /**
     * Gets array of numeric filters
     * 
     * @return array
     */
    public static function getNumericFilters()
    {
        
        return [
            self::FILTER_NOT_SET => Yii::t('frontend', 'FILTER NOT SET'),
            self::FILTER_MORE => Yii::t('frontend', 'FILTER MORE'),
            self::FILTER_MORE_EQUAL => Yii::t('frontend', 'FILTER MORE EQUAL'),
            self::FILTER_LESS => Yii::t('frontend', 'FILTER LESS'),
            self::FILTER_LESS_EQUAL => Yii::t('frontend', 'FILTER LESS EQUAL'),
            self::FILTER_EQUAL => Yii::t('frontend', 'FILTER EQUAL'),
            self::FILTER_NOT_EQUAL => Yii::t('frontend', 'FILTER NOT EQUAL'),
            self::FILTER_BETWEEN => Yii::t('frontend', 'FILTER BETWEEN'),
            self::FILTER_NOT_BETWEEN => Yii::t('frontend', 'FILTER NOT BETWEEN')
        ];
    }

    /**
     * Gets array of accessible filters for all columns
     * 
     * @return array
     */
    public static function getAccessibleFiltersByColumns()
    {

        return [
            'id' => self::getNumericFilters(),
            'type_packet' => self::getCommonFilters(),
            'date' => self::getDateFilters(),
            'unix_time_offset' => self::getDateFilters(),
            'created_at' => self::getDateFilters(),
            'cb_log.unix_time_offset' => self::getDateFilters(),
            'imei' => self::getCommonFilters(),
            'address' => self::getCommonFilters(),
            'number_device' => self::getCommonFilters(),
            'number' => self::getNumericFilters(),
        ];
    }

    /**
     * Gets accessible filters by column
     * 
     * @return array
     */
    public static function getAccessibleFiltersByColumnName($name)
    {
        
        return self::getAccessibleFiltersByColumns()[$name];
    }

    /**
     * Applies FILTER_NOT_SET filter
     * 
     * @param ActiveQuery $query
     * @return array
     */
    private function changeQueryByNotSetFilter($query)
    {
        
        return $query;
    }

    /**
     * Applies FILTER_CELL_EMPTY filter
     * 
     * @param ActiveQuery $query
     * @return array
     */
    private function changeQueryByCellEmptyFilter($query, $columnName)
    {
        $query = $query->andWhere(['=', "LENGTH($columnName)", 0]);
        
        return $query;
    }

    /**
     * Applies FILTER_CELL_NOT_EMPTY filter
     * 
     * @param ActiveQuery $query
     * @return array
     */
    private function changeQueryByCellNotEmptyFilter($query, $columnName)
    {
        $query = $query->andWhere(['>', "LENGTH($columnName)", 0]);
        
        return $query;
    }

    /**
     * Applies FILTER_TEXT_CONTAIN filter
     * 
     * @param ActiveQuery $query
     * @return array
     */
    private function changeQueryByTextContain($query, $columnName, $params)
    {
        if ($columnName == 'type_packet') {
            $typeIds = Jlog::getTypePacketsFromNameByContainCondition($params['val1'][$columnName]);
            
            return $query->andWhere(['type_packet' => $typeIds]);
        }
        
        $query = $query->andWhere([ 'like', $columnName, $params['val1'][$columnName] ]);
        
        return $query;   
    }

    /**
     * Applies FILTER_TEXT_NOT_CONTAIN filter
     * 
     * @param ActiveQuery $query
     * @return array
     */
    private function changeQueryByTextNotContain($query, $columnName, $params)
    {
        if ($columnName == 'type_packet') {
            $typeIds = Jlog::getTypePacketsFromNameByNotContainCondition($params['val1'][$columnName]);
            
            return $query->andWhere(['type_packet' => $typeIds]);
        }
        
        $query = $query->andWhere([ 'not like', $columnName, $params['val1'][$columnName] ]);

        return $query;
    }

    /**
     * Applies FILTER_TEXT_START_FROM filter
     * 
     * @param ActiveQuery $query
     * @return array
     */
    private function changeQueryByTextStartFrom($query, $columnName, $params)
    {
        if ($columnName == 'type_packet') {
            $typeIds = Jlog::getTypePacketsFromNameByStartCondition($params['val1'][$columnName]);
            
            return $query->andWhere(['type_packet' => $typeIds]);
        }
        
        $val1 = $params['val1'][$columnName];
        $query = $query->andWhere(["LOCATE('{$val1}', {$columnName})" => 1]);
        
        return $query;
    }

    /**
     * Applies FILTER_TEXT_END_WITH filter
     * 
     * @param ActiveQuery $query
     * @return array
     */
    private function changeQueryByTextEndWith($query, $columnName, $params)
    {
        if ($columnName == 'type_packet') {
            $typeIds = Jlog::getTypePacketsFromNameByEndCondition($params['val1'][$columnName]);
            
            return $query->andWhere(['type_packet' => $typeIds]);
        }
        
        $val1 = $params['val1'][$columnName];
        $val1Length = mb_strlen($val1);
        $query = $query->andWhere(
            ["LOCATE('{$val1}', SUBSTRING({$columnName}, CHAR_LENGTH({$columnName}) -{$val1Length} + 1 ))" => 1]
        );
        
        
        return $query;
    }

    /**
     * Applies FILTER_TEXT_EQUAL filter
     * 
     * @param ActiveQuery $query
     * @return array
     */
    private function changeQueryByTextEqual($query, $columnName, $params)
    {
        if ($columnName == 'type_packet') {
            $typeId = Jlog::getTypePacketFromName($params['val1'][$columnName]);
            
            return $query->andWhere(['type_packet' => $typeId]);
        }
        
        $query = $query->andWhere([$columnName => $params['val1'][$columnName]]);

        return $query;
    }

    /**
     * Gets timestamp intervals, specified by dateParam
     * 
     * @param string $dateParam
     * @param string $columnName
     * @param array $params
     * @return array
     */
    protected function getTimestampIntervals($dateParam, $columnName, $params)
    {
        switch($dateParam) {
            case 'today':
                
                return [
                    'min' => strtotime("today midnight"),
                    'max'=> strtotime("now")
                ];
            case 'tomorrow':
                
                return [
                    'min' => strtotime("tomorrow midnight"),
                    'max' => strtotime("tomorrow midnight + 1 days")
                ];
            case 'yesterday':
                
                return [
                    'min' => strtotime('yesterday midnight'),
                    'max' => strtotime('today midnight')
                ];
            case 'lastweek':
                if (date("D") == "Mon") {
                    $minExpr = "last monday";
                    $maxExpr = "monday";
                } else {
                    $minExpr = "last monday -7 days";
                    $maxExpr = "last monday";
                }
                
                return [
                    'min' => strtotime($minExpr),
                    'max' => strtotime($maxExpr)
                ];
            case "lastmonth":
                $month = date('m', strtotime("last month"));
                $year = date('Y', strtotime("last month"));
                $dateStart = $year."-".$month."-01 00:00:00";
                
                $currentMonth = date('m', strtotime("now"));
                $currentYear = date('Y', strtotime("now"));
                $dateEnd = $currentYear."-".$currentMonth."-01 00:00:00";
                
                return [
                    'min' => strtotime($dateStart),
                    'max' => strtotime($dateEnd)
                ];
            case "lastyear":
                $lastYear = date("Y", strtotime("last year"));
                $currentYear = date("Y", strtotime("now"));
                $dateStart = $lastYear."-01-01 00:00:00";
                $dateEnd = $currentYear."-01-01 00:00:00";
                
                return [
                   'min' => strtotime($dateStart),
                   'max' => strtotime($dateEnd) 
                ];
            case "certain":
                $dateStart = date("Y-m-d", strtotime($params['val2'][$columnName]));
                $dateEnd = date("Y-m-d", strtotime("+1 days", strtotime($dateStart)));
                
                return [
                   'min' => strtotime($dateStart),
                   'max' => strtotime($dateEnd) 
                ];
        }
    }

    /**
     * Applies common filters
     * 
     * @param ActiveQuery $query
     * @param string $columnName
     * @param array $params
     * @return array
     */
    public function changeQueryByCommonFilter($query, $columnName, $params)
    {
        switch($params['filterCondition'][$columnName]) {
            case self::FILTER_NOT_SET:
                
                break;
            case self::FILTER_CELL_EMPTY:
                $query = $this->changeQueryByCellEmptyFilter($query, $columnName);

                break;
            case self::FILTER_CELL_NOT_EMPTY:
                $query = $this->changeQueryByCellNotEmptyFilter($query, $columnName);
                
                break;
            case self::FILTER_TEXT_CONTAIN:
                $query = $this->changeQueryByTextContain($query, $columnName, $params);
                
                break;
            case self::FILTER_TEXT_NOT_CONTAIN:
                $query = $this->changeQueryByTextNotContain($query, $columnName, $params);
                
                break;
            case self::FILTER_TEXT_START_FROM:
                $query = $this->changeQueryByTextStartFrom($query, $columnName, $params);
                
                break;
            case self::FILTER_TEXT_END_WITH:
                $query = $this->changeQueryByTextEndWith($query, $columnName, $params);
                
                break;
            case self::FILTER_TEXT_EQUAL:
                $query = $this->changeQueryByTextEqual($query, $columnName, $params);
                
                break;
        }

        return $query;
    }

    /**
     * Applies date filters
     * 
     * @param ActiveQuery $query
     * @param string $columnName
     * @param array $params
     * @return array
     */
    public function changeQueryByDateFilter($query, $columnName, $params)
    {
        $timeIntervals = $this->getTimestampIntervals(
            $params['val1'][$columnName], $columnName, $params 
        );
        $min = $timeIntervals['min'];
        $max = $timeIntervals['max'];
        switch($params['filterCondition'][$columnName]) {
            case self::FILTER_DATE :
                $query = $query->andWhere(
                    [">=", "UNIX_TIMESTAMP(STR_TO_DATE({$columnName}, '".Imei::MYSQL_DATE_TIME_FORMAT."'))", $min]
                );
                $query = $query->andWhere(
                    ["<", "UNIX_TIMESTAMP(STR_TO_DATE({$columnName}, '".Imei::MYSQL_DATE_TIME_FORMAT."'))", $max]
                );
                
                break;
            case self::FILTER_DATE_BEFORE:
                $query = $query->andWhere(
                    ["<", "UNIX_TIMESTAMP(STR_TO_DATE({$columnName}, '".Imei::MYSQL_DATE_TIME_FORMAT."'))", $min]
                );
                
                break;
            case self::FILTER_DATE_AFTER:
                $query = $query->andWhere(
                    [">=", "UNIX_TIMESTAMP(STR_TO_DATE({$columnName}, '".Imei::MYSQL_DATE_TIME_FORMAT."'))", $max]
                );

                break;
            case self::FILTER_DATE_FROM:
                $bhSummarySearch = new BalanceHolderSummarySearch();
                $start = $bhSummarySearch->getDayBeginningTimestampByTimestamp($min);

                $query = $query->andWhere(
                    [">=", "UNIX_TIMESTAMP(STR_TO_DATE({$columnName}, '".Imei::MYSQL_DATE_TIME_FORMAT."'))", $start]
                );

                break;
        }

        return $query;
    }

    /**
     * Applies numeric filters
     * 
     * @param ActiveQuery $query
     * @param string $columnName
     * @param array $params
     * @return array
     */
    public function changeQueryByNumericFilter($query, $columnName, $params)
    {
        $min = (
            $params['val1'][$columnName] < $params['val2'][$columnName] ?
            $params['val1'][$columnName] : $params['val2'][$columnName]
        );
        $max = (
            $params['val1'][$columnName] >= $params['val2'][$columnName] ?
            $params['val1'][$columnName] : $params['val2'][$columnName]
        );
        switch($params['filterCondition'][$columnName])
        {
            case self::FILTER_MORE:
                $query = $query->andWhere([">", $columnName, $params['val1'][$columnName]]);
                
                break;
            case self::FILTER_MORE_EQUAL:
                $query = $query->andWhere([">=", $columnName, $params['val1'][$columnName]]);
                
                break;
            case self::FILTER_LESS:
                $query = $query->andWhere(["<", $columnName, $params['val1'][$columnName]]);
                
                break;
            case self::FILTER_LESS_EQUAL:
                $query = $query->andWhere(["<=", $columnName, $params['val1'][$columnName]]);
                
                break;
            case self::FILTER_EQUAL:
                $query = $query->andWhere(["=", $columnName, $params['val1'][$columnName]]);

                break;
            case self::FILTER_NOT_EQUAL:
                $query = $query->andWhere(["!=", $columnName, $params['val1'][$columnName]]);

                break;    
            case self::FILTER_BETWEEN:
                $condition = new \yii\db\conditions\BetweenCondition(
                    $columnName, 'BETWEEN', $min, $max
                );
                $query = $query->andWhere($condition);
                
                break;
            case self::FILTER_NOT_BETWEEN:
                $condition = new \yii\db\conditions\BetweenCondition(
                    $columnName, 'NOT BETWEEN', $min, $max
                );
                $query = $query->andWhere($condition);
                
                break;
        }

        return $query;
    }

    /**
     * Applies conditional filter related to filter category
     * 
     * @param ActiveQuery $query
     * @param string $columnName
     * @param array $params
     * @param integer $filterCategory
     * @return array
     */
    public function applyFilterByConditionMethod($query, $columnName, $params, $filterCategory)
    {
        if (empty($params['filterCondition'][$columnName])) {

            return $query;
        }
        
        switch($filterCategory) {
            case self::FILTER_CATEGORY_COMMON :
                $query = $this->changeQueryByCommonFilter($query, $columnName, $params);
                
                break;
            case self::FILTER_CATEGORY_DATE:
                $this->changeQueryByDateFilter($query, $columnName, $params);
                
                break;
            case self::FILTER_CATEGORY_NUMERIC:
                $this->changeQueryByNumericFilter($query, $columnName, $params);
                
                break;
        }

        return $query;
    }

    /**
     * Applies value filter
     * 
     * @param ActiveQuery $query
     * @param string $columnName
     * @param array $params
     * @return array
     */
    public function applyFilterByValueMethod($query, $columnName, $params)
    {
        if (empty($params['inputValue'][$columnName])) {

            return $query;
        }

        if ($columnName == 'type_packet') {
            $typeIds = Jlog::getTypePacketsFromNameByContainCondition($params['inputValue'][$columnName]);
            
            return $query->andWhere(['type_packet' => $typeIds]);
        }

        return $query->andWhere(['like', $columnName, $params['inputValue'][$columnName]]);
    }

    /**
     * Gets all distinct imeis from j_log, mapped to array of objects
     * 
     * @return array
     */
    public function getImeisMapped()
    {
        $entity = new Entity();
        $query = $entity->getUnitsQueryPertainCompany(new Imei());
        $imeis = $query->select('imei')->distinct()->all();
        $imeisMapped = [];
        $counter = 1;
        $imeiIds = [];
        foreach($imeis as $imei) {

            if (in_array($imei->id, $imeiIds)) {
                continue;
            }

            $imeiIds[] = $imei->id;
            $imeisMapped[] = (object)['id' => $counter++, 'value' => $imei->imei]; 
        }

        return $imeisMapped;
    }

    /**
     * Gets all distinct addresses from j_log, mapped to array of objects
     * 
     * @return array
     */
    public function getAddressesMapped()
    {
        $entity = new Entity();
        $query = $entity->getAllUnitsQueryPertainCompany(new AddressBalanceHolder());
        $addresses = $query->select('address, is_deleted')->distinct()->all();
        $addressesMapped = [];
        $counter = 1;
        foreach($addresses as $address) {

            if ($address->is_deleted) {
                $value = $address->address.self::ADDRESS_DELIMITER.Yii::t('frontend', 'Deleted address');
            } else {
                $value = $address->address;
            }

            $addressesMapped[] = (object)['id' => $counter++, 'value' => $value]; 
        }

        return $addressesMapped;
    }

    /**
     * Gets time intervals, basing on $fromDate and $toDate
     * 
     * @param date $fromDate
     * @param date $toDate
     * @return array
     */
    public function makeTimeIntervals($fromDate, $toDate)
    {
        $timeFrom = 0;
        $timeTo = self::INFINITY;
        $startDay = ' 00:00:00';
        $endDay = ' 23:59:59';

        if (!empty($fromDate)) {

            if (!strrpos($fromDate, $startDay)) {
                $fromDate .= $startDay;
            }

            $timeFrom = strtotime($fromDate);
        }

        if (!empty($toDate)) {

            if (!strrpos($toDate, $endDay)) {
                $toDate .= $endDay;
            }

            $timeTo = strtotime($toDate);
        }

        return [$timeFrom, $timeTo];
    }

    /**
     * Applies between date condition to query 
     * 
     * @param ActiveDbQuery $query
     * @param yii\db\ActiveRecord $searchModel
     * @return ActiveDbQuery
     */
    public function applyBetweenDateCondition($query, $searchModel)
    {
        list($timeFrom, $timeTo) = $this->makeTimeIntervals($searchModel->from_date, $searchModel->to_date);

        if ($timeFrom > self::ZERO) {
            $query = $query->andWhere(['>=', 'unix_time_offset', $timeFrom]);
        }

        if ($timeTo < self::INFINITY) {
            $query = $query->andWhere(['<=', 'unix_time_offset', $timeTo]);
        }

        return $query;
    }

    /**
     * Sets model attributes and params by request and accepted params 
     * 
     * @param JlogSearch $searchModel
     * @param array $params
     * @param array $prms
     * @return array
     */
    public function setParams($searchModel, $params, $prms)
    {
        if (isset($prms['JlogSearch']['from_date'])) {
            $searchModel->from_date = $prms['JlogSearch']['from_date'];
        }

        if (isset($prms['JlogSearch']['to_date'])) {
            $searchModel->to_date = $prms['JlogSearch']['to_date'];
        }

        if (!empty($prms) && isset($prms['imei'])) {
            $params['imei'] = $prms['imei'];
        }

        if (empty($params['type_packet'])) {
            $params['type_packet'] = self::TYPE_PACKET_DATA;
        }

        if (!empty($params['address'])) {
            $params['address'] = $this->findStaticAddress($params['address'], $params['type_packet']);
        }

        return $params;
    }

    /**
     * Sets mashine number to model instance
     * 
     * @param JlogSearch $searchModel
     * @param array $params
     * @return array
     */
    public function setMashineNumber(JlogSearch $searchModel, array $params, bool $isMashine = false): array
    {
        if (!$isMashine) {

            return $params;
        }

        $mashineId = Yii::$app->request->get()['id'] ?? self::ZERO;

        if (!empty($mashineId) && !empty($mashine = WmMashine::findOne($mashineId))) {
            $searchModel->mashineNumber = '_'.$mashine->type_mashine.'*'.$mashine->number_device;
            $params['wm_mashine_number'] = $mashine->number_device;
        }

        if (!empty($mashine->address_id)) {
            $params['address_id'] = $mashine->address_id;
        }

        return $params;
    }

    /**
     * Sets address as filter param
     * 
     * @param JlogSearch $searchModel
     * @param array $params
     * @return array
     */
    public function setAddressId(JlogSearch $searchModel, array $params): array
    {
        $addressId = Yii::$app->request->get()['id'] ?? self::ZERO;

        if (!empty($addressId)) {
            $entityHelper = new EntityHelper();
            $address = AddressBalanceHolder::findOne($addressId);
            $params['filterCondition']['address'] = self::FILTER_TEXT_EQUAL;
            $params['val1']['address'] = $entityHelper->tryUnitRelationData($address, ['address' => ['static_address', 'static_floor'], ', ']);
        }

        return $params;
    }

    /**
     * Gets initialization history beginning by address string
     * 
     * @param string $addressString
     * 
     * @return int
     */
    public function getInitializationHistoryBeginningByAddressString($addressString)
    {
        $query = Jlog::find()->select(['unix_time_offset'])->andWhere(['address' => $addressString, 'type_packet' => self::TYPE_PACKET_INITIALIZATION]);
        $query = $query->orderBy(['unix_time_offset' => SORT_ASC])->limit(1);

        $item = $query->one();

        if ($item) {

            return $item->unix_time_offset;
        }

        return self::INFINITY;
    }

    /**
     * Gets imei id by address string and initial timestamp
     * 
     * @param string $addressString
     * @param int $start
     * 
     * @return int
     */
    public function getImeiIdByAddressStringAndInitialTimestamp($addressString, $start)
    {
        $query = Jlog::find()->select(['imei_id'])->andWhere(['address' => $addressString, 'type_packet' => self::TYPE_PACKET_INITIALIZATION]);
        $query->andWhere(['<=', 'unix_time_offset', $start]);
        $query = $query->orderBy(['unix_time_offset' => SORT_DESC])->limit(1);

        $item = $query->one();

        if ($item) {

            return $item->imei_id;
        }

        return 0;
    }

    /**
     * Gets last level signal by address string and initial timestamp
     *
     * @param string $addressString
     * @param int $start
     * 
     * @return int|null
     */
    public function getLastLevelSignalByAddressAndTimestamp($addressString, $start)
    {
        $query = Jlog::find()->select(['packet'])->andWhere(['address' => $addressString, 'type_packet' => self::TYPE_PACKET_INITIALIZATION]);
        $query->andWhere(['<', 'unix_time_offset', $start]);
        $query = $query->orderBy(['unix_time_offset' => SORT_DESC])->limit(1);
        $item = $query->one();

        if ($item && $item->packet) {
            $parser = new CParser();
            $parseData = $parser->iParse($item->packet);

            return $parseData['level_signal'];
        }

        return null;
    }

    /**
     * Gets last initialization item by address string and timestamp
     * 
     * @param string $addressString
     * @param int $start
     */
    public function getLastInitializationItemByAddressAndTimestamp($addressString, $start)
    {
        $query = Jlog::find()->andWhere(['address' => $addressString, 'type_packet' => self::TYPE_PACKET_INITIALIZATION]);
        $query->andWhere(['<', 'unix_time_offset', $start]);
        $query = $query->orderBy(['unix_time_offset' => SORT_DESC])->limit(1);

        return $query->one();
    }

    /**
     * Gets last data item by address string and timestamp
     * 
     * @param string $addressString
     * @param int $start
     * 
     * @return \frontend\models\Jlog 
     */
    public function getLastDataItemByAddressAndTimestamp($addressString, $start)
    {
        $query = Jlog::find()->andWhere(['address' => $addressString, 'type_packet' => self::TYPE_PACKET_DATA]);
        $query->andWhere(['<', 'unix_time_offset', $start]);
        $query = $query->orderBy(['unix_time_offset' => SORT_DESC])->limit(1);

        return $query->one();
    }

    /**
     * Gets modem not in touch items by addresses and timestamps
     * 
     * @param string $addressesInfo
     * @param int $start
     * @param int $end
     * @param int $monitoringStep
     * 
     * @return array 
     */
    public function getModemNotInTouchItemsByAddresses($addressesInfo, $start, $end, $monitoringStep)
    {
        $symbol = "_";
        $searchString = "*".ImeiData::CP_STATUS_TERMINAL_NOT_IN_TOUCH;
        $searchStringLength = strlen($searchString);
        $addressStrings = [];
        $addressesEndPoints = [];
        $items = [];

        foreach ($addressesInfo as $infoItem) {
            $addressString = $infoItem['static_address'].', '.$infoItem['static_floor'];
            $addressesEndPoints[$addressString] = $this->getFirstLastPacketItemsByAddress($addressString);

            if ($addressesEndPoints[$addressString]['first'] > $start + $monitoringStep) {
                $items[$addressString][] = [
                    'start' => $start,
                    'end' => $addressesEndPoints[$addressString]['first'] - 1
                ];
            }

            $addressStrings[] = $addressString;
        }

        $query = Jlog::find()->select(['id', 'address', 'unix_time_offset', 'date_end'])
                             ->andWhere(['address' => $addressStrings, 'type_packet' => self::TYPE_PACKET_DATA]);
        $query->andWhere(['<', 'unix_time_offset', $end]);
        $query->andWhere([">", "UNIX_TIMESTAMP(STR_TO_DATE(date_end, '".Imei::MYSQL_DATE_TIME_FORMAT."'))", $start]);
        $query->andWhere([ "LOCATE('{$symbol}', packet)" => 0]);
        $query->andWhere([
                "LOCATE('{$searchString}', SUBSTRING(packet, CHAR_LENGTH(packet) -{$searchStringLength} + 1)
                )" => 1]);
        $query->orderBy(['address' => SORT_ASC, 'unix_time_offset' => SORT_ASC]);

        foreach ($query->all() as $item) {
            $startStamp = $item->unix_time_offset;
            $endStamp = !empty($item->date_end) ? strtotime($item->date_end) : $startStamp + 300;
            $items[$item->address][] = ['start' => $startStamp, 'end' => $endStamp];
        }

        foreach (array_keys($addressesEndPoints) as $addressString) {
            if (($last=$addressesEndPoints[$addressString]['last']) < $end - $monitoringStep) {
                $count = array_key_exists($addressString, $items) ? count($items[$addressString]) : 0;
                if ($count && $items[$addressString][$count-1]['end'] == $last) {
                    $items[$addressString][$count-1]['end'] = $end;
                } else {
                    $items[$addressString][] = ['start' => $last, 'end' => $end];
                }
            }
        }

        return $items;
    }

    /**
     * Gets first and last packet items by address
     * 
     * @param string $addressString
     *
     * @return array
     */
    public function getFirstLastPacketItemsByAddress($addressString)
    {
        $query = Jlog::find()->select(['date'])
                             ->andWhere(['address' => $addressString, 'type_packet' => self::TYPE_PACKET_DATA]);

        $query->orderBy(["UNIX_TIMESTAMP(STR_TO_DATE(date, '".Imei::MYSQL_DATE_TIME_FORMAT."'))" => SORT_ASC])
              ->limit(1);
        $item = $query->one();

        if (!$item || !$item->date) {

            return ['first' => self::INFINITY, 'last' => self::INFINITY];
        }

        $first = strtotime($item->date);

        $query = Jlog::find()->select(['date_end'])
                             ->andWhere(['address' => $addressString, 'type_packet' => self::TYPE_PACKET_DATA]);

        $query->orderBy(["UNIX_TIMESTAMP(STR_TO_DATE(date_end, '".Imei::MYSQL_DATE_TIME_FORMAT."'))" => SORT_DESC])
              ->limit(1);
        $item = $query->one();

        if (empty($item->date_end)) {
            $last = self::INFINITY;
        } else {

            $last = strtotime($item->date_end);
        }

        return ['first' => $first, 'last' => $last];
    }

    /**
     * Finds static address by address string or returns the same address string
     *
     * @param string $addressString
     * @param int $typePacket
     *
     * @return string
     */
    public function findStaticAddress($addressString, $typePacket)
    {
        if (!in_array($typePacket, [self::TYPE_PACKET_INITIALIZATION, self::TYPE_PACKET_DATA, self::TYPE_PACKET_DATA_CP])
        ) {

            return $addressString;
        }

        $item = AddressBalanceHolder::find()->where(['address' => $addressString])->limit(1)->one();

        if (empty($item)) {

            return $addressString;
        }

        return $item->static_address;
    }

    /**
     * Finds address by static address string or returns the same string 
     *
     * @param string $addressString
     * @param int $typePacket
     * @param bool $cutFromRight
     *
     * @return string
     */
    public function findAddressByStatic($addressString, $typePacket, $cutFromRight = true)
    {
        if (empty($addressString)
            ||
            !in_array($typePacket, [self::TYPE_PACKET_INITIALIZATION, self::TYPE_PACKET_DATA, self::TYPE_PACKET_DATA_CP])
        ) {

            return $addressString;
        }

        // gets static address string
        if ($cutFromRight && ($position = strrpos($addressString, ",")) !== false) {
            $addressString = substr($addressString, 0, $position);
        }

        $item = AddressBalanceHolder::find()->where(['static_address' => $addressString])->limit(1)->one();

        if (empty($item)) {

            return $addressString;
        }

        return $item->address;
    }

    /**
     * Finds static address string by `address` field of `address_balance_holder` table
     *
     * @param string $addressString
     * @return string
     */
    public function findStaticAddressByString(string $addressString): string
    {
        $item = AddressBalanceHolder::find()->where(['address' => $addressString])->limit(1)->one();

        if (empty($item)) {

            return $addressString;
        }

        return $item->static_address.', '.$item->static_floor;
    }
}
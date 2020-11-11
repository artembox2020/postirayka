<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use frontend\models\BalanceHolder;
use frontend\models\ImeiData;
use frontend\models\Jlog;
use frontend\services\globals\Entity;
use frontend\services\globals\EntityHelper;
use frontend\services\globals\QueryOptimizer;
use yii\helpers\ArrayHelper;

/**
 * BalanceHolderSummarySearch represents the model to make summary journal
 */
class BalanceHolderSummarySearch extends BalanceHolder
{

    const PERCENT_ONE_THIRD = 33;
    const PERCENT_TWO_THIRD = 67;
    const IDLE_TIME_HOURS = 0.25;
    const TYPE_GENERAL = 0;
    const TYPE_DETAILED = 1;
    const TYPE_DAMAGE_IDLE_HOURS = 8;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'company_id', 'created_at', 'deleted_at'], 'integer'],
            [['name', 'city', 'address', 'phone', 'contact_person', 'is_deleted'], 'safe'],
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
    public function baseSearch($params)
    {
        $entity = new Entity();
        $query = BalanceHolder::find()->where(['balance_holder.company_id' => $entity->getCompanyId()]);
        $numberDays = $this->getDaysByMonths($params['year'])[$params['month']];
        $timestampStart = strtotime($params['year'].'-'.$params['month'].'-01 00:00:00');
        $timestampEnd = strtotime($params['year'].'-'.$params['month'].'-'.$numberDays.' 23:59:59');
        $query = $query->andWhere(['<=', 'created_at', $timestampEnd]);
        $query = $query->andWhere(new \yii\db\conditions\OrCondition([
                            new \yii\db\conditions\AndCondition([
                              ['=', 'balance_holder.is_deleted', false],
                            ]),
                            new \yii\db\conditions\AndCondition([
                              ['=', 'balance_holder.is_deleted', true],
                              ['>', 'balance_holder.deleted_at', $timestampStart]
                            ])
                        ]));
        //$query = $query->innerJoin('address_balance_holder', 'address_balance_holder.balance_holder_id = balance_holder.id');
        
        
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $dataProvider;
    }

    /**
     * Creates data provider instance with search query applied and restricts to one item
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function limitOneBaseSearch($params)
    {
        $dataProvider = $this->baseSearch($params);
        $dataProvider->query = $dataProvider->query->limit(1);
        $dataProvider->pagination = false;

        return $dataProvider;
    }

    /**
     * Gets the total number of all addresses
     *
     * @return int
     */
    public static function getTotalAddressesCount()
    {
        $entity = new Entity();

        return AddressBalanceHolder::find()->where(['company_id' => $entity->getCompanyId()])->count();
    }

    /**
     * Gets timestamp by year, month, day
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param boolean $isBeginning
     *
     * @return int
     */
    public function getTimestampByYearMonthDay($year, $month, $day, $isBeginning)
    {
        if ($isBeginning) {

            return  strtotime($year.'-'.$month.'-'.$day.' 00:00:00') + 0;
        } else {

            return  strtotime($year.'-'.$month.'-'.$day.' 23:59:59') + 0;
        }
    }

    /**
     * Gets timestamp referring to the beginning(end) of the day
     *
     * @param int $timestamp
     * @return timestamp
     */
    public function getDayBeginningTimestampByTimestamp($timestamp)
    {
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);

        return  strtotime($year.'-'.$month.'-'.$day.' 00:00:00');
    }

    /**
     * Gets the next month timestamp
     *
     * @return timestamp
     */
    public function getNextMonthBeginningTimestamp()
    {
        $timestamp = time() + Jlog::TYPE_TIME_OFFSET;
        $month = date('m', $timestamp);
        $year = date('Y', $timestamp);
        if ($month == '12') {
            $month = '01';
            ++$year;
        } else {
            ++$month;
        }

        return strtotime($year.'-'.$month.'-01 00:00:00');
    }

    /**
     * Gets timestamp by year and month
     *
     * @param int $year
     * @param int $month
     *
     * @return timestamp
     */
    public function getTimestampByYearMonth($year, $month)
    {
        $days = $this->getDaysByMonths($year)[$month];
        $timestampStart = $this->getTimestampByYearMonthDay($year, $month, '01', true);
        $timestampEnd = $this->getTimestampByYearMonthDay($year, $month, $days, false);

        return [
            'start' => $timestampStart,
            'end' => $timestampEnd
        ];
    }

    /**
     * Gets the year of the last month
     *
     * @return int
     */   
    public function getYearLastMonth() {
        $timestamp = time() + Jlog::TYPE_TIME_OFFSET;
        $year = date('Y', $timestamp);
        if (date('m', $timestamp) == '01') {
            --$year;
        }

        return $year;
    }

    /**
     * Gets the the last month
     *
     * @return int
     */  
    public function getLastMonth($timestamp) {
        $month = date('m', $timestamp);
        if ($month == '01') {
            $month = '12';
        } else {
            --$month;
            if ($month < 10) {
                $month = '0'.$month;
            }
        }

        return $month;
    }

    /**
     * Gets array, representing months
     *
     * @return array
     */  
    public function getMonths()
    {

        return [
            '01' => Yii::t('common', 'January'),
            '02' => Yii::t('common', 'February'),
            '03' => Yii::t('common', 'March'),
            '04' => Yii::t('common', 'April'),
            '05' => Yii::t('common', 'May'),
            '06' => Yii::t('common', 'June'),
            '07' => Yii::t('common', 'July'),
            '08' => Yii::t('common', 'August'),
            '09' => Yii::t('common', 'September'),
            '10' => Yii::t('common', 'October'),
            '11' => Yii::t('common', 'November'),
            '12' => Yii::t('common', 'December'),
        ];
    }

    /**
     * Gets array of the years
     *
     * @return array
     */  
    public function getYears()
    {
        $currentYear = (int)date('Y');
        $years = [];
        for ($i = $currentYear - 5; $i <= $currentYear + 1; ++$i) {
            $years[$i] = $i;
        }

        return $years;
    }

    /**
     * Check whether the year is leap
     *
     * @return boolean
     */  
    private function is_leap_year($year) {
	    return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0)));
    }

    /**
     * Gets array representing months and number of days
     *
     * @param $year
     * @return array
     */  
    public function getDaysByMonths($year)
    {
        
        return [
            '01' => 31,
            '02' => $this->is_leap_year($year) ? 29 : 28,
            '03' => 31,
            '04' => 30,
            '05' => 31,
            '06' => 30,
            '07' => 31,
            '08' => 31,
            '09' => 30,
            '10' => 31,
            '11' => 30,
            '12' => 31
        ];
    }

    /**
     * Sets params if necessary
     *
     * @return array
     */  
    public function setParams($params)
    {
        $timestamp = time() + Jlog::TYPE_TIME_OFFSET;
        $params['month'] = $params['month'] ? $params['month'] : date('m', $timestamp);
        $params['year'] = $params['year'] ? $params['year'] : date('Y', $timestamp);
        $params['type'] = $params['type'] ? $params['type'] : self::TYPE_GENERAL;

        return $params;
    }

    /**
     * Gets the query representing all active mashines
     *
     * @param timestamp $start
     * @param timestamp $end
     * @param int $imeiId
     * @return ActiveDbQuery
     */  
    public function getAllActiveMashinesQueryByTimestamps($start, $end, $imeiId)
    {
        $query = WmMashineData::find()->select('mashine_id')->distinct()->andWhere(['>=', 'created_at', $start]);
        $query = $query->andWhere(['<=', 'created_at', $end]);
        $mashineIds = ArrayHelper::getColumn(QueryOptimizer::getItemsByQuery($query), 'mashine_id');
        $query = WmMashine::find()->select('id')->distinct()->where(['imei_id' => $imeiId, 'id' => $mashineIds]);

        return $query;
    }
    
    /**
     * Gets the query representing all mashines
     *
     * @param timestamp $start
     * @param timestamp $end
     * @param int $imeiId
     * @return ActiveDbQuery
     */ 
    public function getAllMashinesQueryByTimestamps($start, $end, $imeiId)
    {
        $query = WmMashine::find();
        $query = $query->where(['imei_id' => $imeiId]);
        $query = $query->andWhere(['<=', 'created_at', $end]);
        $query = $query->andWhere(['status' => WmMashine::STATUS_ACTIVE]);
        $query = $query->andWhere(new \yii\db\conditions\OrCondition([
                            new \yii\db\conditions\AndCondition([
                              ['=', 'wm_mashine.is_deleted', false],
                            ]),
                            new \yii\db\conditions\AndCondition([
                              ['=', 'wm_mashine.is_deleted', true],
                              ['>', 'wm_mashine.deleted_at', $start]
                            ])
                        ]))
                       ->orderBy(['id' => SORT_ASC]);

        return $query;
    }

    /**
     * Gets date and sum encashment by timestamps and imeiid
     *
     * @param timestamp $start
     * @param timestamp $end
     * @param int $imeiId
     * @return array
     */
    public function getDateAndSumEncashmentByTimestamps($start, $end, $imeiId)
    {
        global $encashmentHistory;
        $imeiData = new ImeiData();
        $dayBeginning = $this->getDayBeginningTimestampByTimestamp($start);
        if (empty($encashmentHistory[$imeiId])) {
            $nowTimestamp = time() + Jlog::TYPE_TIME_OFFSET;
            $encashmentHistory[$imeiId] = $imeiData->getEncashmentHistoryByImeiId($imeiId, $start, $nowTimestamp);
        }

        if (empty($encashmentHistory[$imeiId][$dayBeginning])) {

            return [
                'encasment_date' => null,
                'encasment_sum' => null
            ];
        }

        $encashmentSum = 0;
        $encashmentDate = null;
        foreach ($encashmentHistory[$imeiId][$dayBeginning] as $item) {
            $encashmentSum += $item['money_in_banknotes'];
            if (empty($encashmentDate)) {
                $encashmentDate = $item['created_at'];
            }
        }

        return [
            'encashment_date' => $encashmentDate,
            'encashment_sum' => $encashmentSum
        ];
    }

    /**
     * Gets the query representing all imeis
     *
     * @param timestamp $start
     * @param timestamp $end
     * @return ActiveDbQuery
     */ 
    public function getAllImeisQueryByTimestamps($start, $end)
    {
        $entity = new Entity();
        $query = $entity->getUnitsQueryPertainCompany(new Imei());
        $query = $query->where(['company_id' => $entity->getCompanyId()]);
        $query = $query->andWhere(['<=', 'created_at', $end]);
        $query = $query->andWhere(new \yii\db\conditions\OrCondition([
                            new \yii\db\conditions\AndCondition([
                              ['=', 'imei.is_deleted', false],
                            ]),
                            new \yii\db\conditions\AndCondition([
                              ['=', 'imei.is_deleted', true],
                              ['>', 'imei.deleted_at', $start]
                            ])
                        ]));

        return $query;
    }

    /**
     * Gets the query representing all deleted mashines
     *
     * @param timestamp $start
     * @param timestamp $end
     * @param int $imeiId
     * @return ActiveDbQuery
     */ 
    public function getAllDeletedMashinesQueryByTimestamps($start, $end, $imeiId)
    {
        $entity = new Entity();
        $query = $entity->getUnitsQueryPertainCompany(new WmMashine());
        $query = $query->where(['imei_id' => $imeiId, 'company_id' => $entity->getCompanyId(), 'is_deleted' => true]);
        $query = $query->andWhere(['>=', 'deleted_at', $start]);
        $query = $query->andWhere(['<=', 'deleted_at', $end]);

        return $query;
    }

    /**
     * Gets the query representing all added mashines
     *
     * @param timestamp $start
     * @param timestamp $end
     * @param int $imeiId
     * @return ActiveDbQuery
     */ 
    public function getAllAddedMashinesQueryByTimestamps($start, $end, $imeiId)
    {
        $entity = new Entity();
        $query = $entity->getUnitsQueryPertainCompany(new WmMashine());
        $query = $query->where(['imei_id' => $imeiId, 'company_id' => $entity->getCompanyId()]);
        $query = $query->andWhere(['>=', 'created_at', $start]);
        $query = $query->andWhere(['<=', 'created_at', $end]);

        return $query;
    }

    /**
     * Gets the query representing all mashines by year, month and address
     *
     * @param int $year
     * @param int $month
     * @param ActiveRecord $address
     * @return ActiveDbQuery
     */ 
    public function getAllMashinesQueryByYearMonth($year, $month, $address)
    {
        $entity = new Entity();

        $imeiQuery = Imei::find()->andWhere(
            ['address_id' => $address->id, 'status' => $address->status, 'company_id' => $entity->getCompanyId()]
        );

        $imeiQuery = $imeiQuery->limit(1);
        $imei = QueryOptimizer::getItemByQuery($imeiQuery);

        if ($imei) {
            $timestamps = $this->getTimestampByYearMonth($year, $month);
            $query = $this->getAllMashinesQueryByTimestamps($timestamps['start'], $timestamps['end'], $imei->id);

            return $query;
        } else {

            return WmMashine::find()->where('0');
        }
    }

    /**
     * Gets the  base query representing data from ImeiData 
     *
     * @param timestamp $start
     * @param timestamp $end
     * @param Imei $imei
     * @param string $selectString
     * @return ActiveDbQuery
     */ 
    public function getBaseQueryByImeiAndTimestamps($start, $end, $imei, $selectString)
    {
        $baseQuery = ImeiData::find()->select($selectString)
                                     ->andWhere(['imei_id' => $imei->id])
                                     ->andWhere(['>=', 'created_at', $start])
                                     ->andWhere(['<', 'created_at', $end]);

        return $baseQuery;
    }

    /**
     * Gets income by imei and timestamps
     *
     * @param timestamp $start
     * @param timestamp $end
     * @param Imei $imei
     * @param Address $address
     * @return decimal
     */ 
    public function getIncomeByImeiAndTimestamps($start, $end, $imei, $address)
    {
        $jSummary = new Jsummary();
        $todayTimestamp = $this->getDayBeginningTimestampByTimestamp(time() + Jlog::TYPE_TIME_OFFSET);
        $selectString = 'fireproof_residue, created_at, imei_id';
        $query = $this->getBaseQueryByImeiAndTimestamps($start, $end, $imei, $selectString);
        $queryS1 = $query->orderBy(['created_at' => SORT_ASC]);
        $itemStart = QueryOptimizer::getItemByQuery($queryS1->limit(1));
        if (!empty($itemStart)) {
            $entityHelper = new EntityHelper();

            $itemEnd = ImeiData::find()->andWhere(['imei_id' => $imei->id])
                                       ->andWhere(['>', 'created_at', $end])
                                       ->orderBy(['created_at' => SORT_ASC])
                                       ->limit(1)
                                       ->one();

            if ($itemEnd) {
                $endTimestamp = $itemEnd->created_at + 1;
            } else {
                $endTimestamp = $end;
            }

            // makes none-zero time intervals
            /*$nonZeroIntervals = $entityHelper->makeNonZeroIntervalsByTimestamps(
                $start,
                $endTimestamp,
                new ImeiData(),
                $imei,
                'imei_id',
                $selectString,
                'fireproof_residue'
            );
            $income = 0;
            $isFirst = true;*/

            //calculation by each non-zero time interval and summing
            /*foreach ($nonZeroIntervals as $interval) {
                $income += $entityHelper->getUnitIncomeByNonZeroTimestamps(
                    $interval['start'],
                    $interval['end'],
                    new ImeiData(),
                    $imei,
                    'imei_id',
                    $selectString,
                    'fireproof_residue',
                    $isFirst
                );
                $isFirst = false;
            }*/
            
            $income = 0 +  $entityHelper->getUnitIncomeByNonZeroTimestamps(
                    $start,
                    $endTimestamp,
                    new ImeiData(),
                    $imei,
                    'imei_id',
                    $selectString,
                    'fireproof_residue',
                    true
                );
            $income = $this->parseFloat($income, 2);
        } else {
            $income = null;
        }

        $todayTimestamp = $this->getDayBeginningTimestampByTimestamp(time() + Jlog::TYPE_TIME_OFFSET);

        if ($todayTimestamp > $start) {

            $jSummary->saveItem($imei->id, $address->id, $start, $end, ['income' => $income], false);
        }

        return $income;
    }

    /**
     * Gets income by the last year and month
     *
     * @param int year
     * @param int $month
     * @return decimal
     */
    public function getIncomeForLastYear($year, $month)
    {
        --$year;
        $numberOfDays = $this->getDaysByMonths($year)[$month];
        $timestampStart = strtotime($year.'-'.$month.'-01 00:00:00');
        $timestampEnd = strtotime($year.'-'.$month.'-'.$numberOfDays.' 23:59:59');
        $jSummary = new Jsummary();
        $totalIncome = $jSummary->getTotalIncomeByTimestamps($timestampStart, $timestampEnd);
        if (empty($totalIncome)) {
            $totalIncome = $this->getTotalIncome($year, $month);
        }

        return $totalIncome;
    }

    /**
     * Gets total income by the year and month
     *
     * @param int year
     * @param int $month
     * @return decimal
     */
    public function getTotalIncome($year, $month)
    {
        $numberOfDays = $this->getDaysByMonths($year)[$month];
        $timestampStart = strtotime($year.'-'.$month.'-01 00:00:00');
        $timestampEnd = strtotime($year.'-'.$month.'-'.$numberOfDays.' 23:59:59');
        $imeis = $this->getAllImeisQueryByTimestamps($timestampStart, $timestampEnd);
        $totalIncome = 0;
        foreach ($imeis->all() as $imei) {
            if(!empty($imei->address_id)) {
                $address = AddressBalanceHolder::findOne($imei->address_id);
                if ($address && $address->status == $imei->status) {
                    $incomes = $this->getIncomesByYearMonth($year, $month, $address);
                    $imeiIncome = 0;
                    foreach ($incomes as $income) {
                        $imeiIncome += (float)$income['income'];
                    }
                    $imeiIncome = $this->parseFloat($imeiIncome, 2);
                    $totalIncome = (float)$totalIncome + $imeiIncome;
                }
            }
        }

        return $totalIncome;
    }

    /**
     * Gets income for the last month
     *
     * @return decimal
     */ 
    public function getIncomeForLastMonth($year, $month)
    {
        $timestamp = strtotime($year.'-'.$month.'-01 00:00:00');
        $month = $this->getLastMonth($timestamp);

        if ($month == '12') {
            --$year;
        }

        $numberOfDays = $this->getDaysByMonths($year)[$month];
        $timestampStart = strtotime($year.'-'.$month.'-01 00:00:00');
        $timestampEnd = strtotime($year.'-'.$month.'-'.$numberOfDays.' 23:59:59');
        $jSummary = new Jsummary();
        $totalIncome = $jSummary->getTotalIncomeByTimestamps($timestampStart, $timestampEnd);
        if (empty($totalIncome)) {
            $totalIncome = $this->getTotalIncome($year, $month);
        }

        return $totalIncome;
    }

    /**
     * Gets info about mashines by imei and timestamps
     *
     * @return array
     */ 
    public function getMashineStatisticsByImeiAndTimestamps($timestamp, $timestampEnd, $imei, $address)
    {
        $jSummary = new Jsummary();
        $entityHelper = new EntityHelper();
        $todayTimestamp = $this->getDayBeginningTimestampByTimestamp(time() + Jlog::TYPE_TIME_OFFSET);

        $mashinesCreatedQuery = $this->getAllAddedMashinesQueryByTimestamps($timestamp, $timestampEnd, $imei->id);
        $mashinesDeletedQuery = $this->getAllDeletedMashinesQueryByTimestamps($timestamp, $timestampEnd, $imei->id);
        $mashinesActiveQuery = $this->getAllActiveMashinesQueryByTimestamps($timestamp, $timestampEnd, $imei->id);
        $mashinesAllQuery = $this->getAllMashinesQueryByTimestamps($timestamp, $timestampEnd, $imei->id);
        $mashinesCreated = QueryOptimizer::getItemsCountByQuery($mashinesCreatedQuery);
        $mashinesDeleted = QueryOptimizer::getItemsCountByQuery($mashinesDeletedQuery);
        $mashinesActive = QueryOptimizer::getItemsCountByQuery($mashinesActiveQuery);
        $mashinesAll = QueryOptimizer::getItemsCountByQuery($mashinesAllQuery);
        $mashines = QueryOptimizer::getItemsByQuery($this->getAllMashinesQueryByTimestamps($timestamp, $timestampEnd, $imei->id));
        $encashmentInfo = $this->getDateAndSumEncashmentByTimestamps($timestamp, $timestampEnd, $imei->id);
        $totalIdleHours = 0.00;
        $totalHours = 0.00;
        $totalDamageIdleHours = 0.00;
        $totalWorkIdleHours = 0.00;
        $totalConnectIdleHours = 0.00;
        $totalCbConnectionIdleHours = 0.00;
        $totalCbIdleHours = 0.00;
        foreach ($mashines as $mashine) {
            $idleHoursInfo = $mashine->getIdleHoursByTimestamps($timestamp, $timestampEnd, self::IDLE_TIME_HOURS);
            extract($idleHoursInfo);
            $damageIdleHours = $idleHoursInfo['idleHours'] >= self::TYPE_DAMAGE_IDLE_HOURS ? $idleHoursInfo['idleHours'] : 0;

            if ($idleHours >= self::IDLE_TIME_HOURS) {
                $totalIdleHours += $idleHours;
            }

            $totalHours += $allHours;
            $totalWorkIdleHours += $workIdleHours;
            $totalConnectIdleHours += $connectIdleHours;
            $totalCbConnectionIdleHours += $cbConnectionIdleHours;
            $totalCbIdleHours += $cbIdleHours;
            $totalDamageIdleHours += $damageIdleHours;
        }

        $totalIdleHours = $this->parseFloat($totalIdleHours, 2);
        $totalWorkIdleHours = $this->parseFloat($totalWorkIdleHours, 2);
        $totalConnectIdleHours = $this->parseFloat($totalConnectIdleHours, 2);
        $totalCbConnectionIdleHours = $this->parseFloat($totalCbConnectionIdleHours, 2);
        $totalCbIdleHours = $this->parseFloat($totalCbIdleHours, 2);
        $totalHours = $this->parseFloat($totalHours, 2);
        $idleHoursReasons = $totalWorkIdleHours.'**'.$totalConnectIdleHours.'**'.$totalCbConnectionIdleHours.'**'.$totalCbIdleHours;

        $mashineStatistics = [
            'created' => $mashinesCreated,
            'deleted' => $mashinesDeleted,
            'active' => $mashinesActive,
            'all' => $mashinesAll,
            'idleHours' => !is_null($totalIdleHours) ? $totalIdleHours : null,
            'damageIdleHours' => $totalDamageIdleHours,
            'idleHoursReasons' => $idleHoursReasons,
            'allHours' => $totalHours,
            'encashment_date' => empty($encashmentInfo['encashment_date']) ? null : $encashmentInfo['encashment_date'],
            'encashment_sum' => empty($encashmentInfo['encashment_sum']) ? null : $encashmentInfo['encashment_sum'],
            'imei' => $imei->imei,
            'imei_id' => $imei->id,
            'address_id' => $address->id
        ];

        $todayTimestamp = $this->getDayBeginningTimestampByTimestamp(time() + Jlog::TYPE_TIME_OFFSET);

        if ($todayTimestamp > $timestamp) {
            $jSummary->saveItem($imei->id, $address->id, $timestamp, $timestampEnd, $mashineStatistics, false);
        }

        return $mashineStatistics;
    }

    /**
     * Gets incomes by year, month and address
     *
     * @param int $year
     * @param int $month
     * @param AddressBalanceHolder $address
     * @return array
     */
    public function getIncomesByYearMonth($year, $month, $address)
    {
        $entity = new Entity();
        $jSummary = new Jsummary();
        $addressImeiData = new AddressImeiData();
        $historyBeginning = $addressImeiData->getHistoryBeginning($address->id);

        if ($addressImeiData->getWmMashinesCountByYearMonth($year, $month, $address) == 0) {

            return [];
        }

        $imeiQuery = $entity->getUnitsQueryPertainCompany(new Imei());
        $incomes = [];
        $timestamps = $this->getTimestampByYearMonth($year, $month);

        $beginningTimestamp = $this->getDayBeginningTimestampByTimestamp($address->created_at);
        $todayTimestamp = $this->getDayBeginningTimestampByTimestamp(time() + Jlog::TYPE_TIME_OFFSET);
        $nextMonthTimestamp = $this->getNextMonthBeginningTimestamp();
        $numberOfDays = $this->getDaysByMonths($year)[$month];
        $intervalStep = 3600 * 24;

        $incomesFromHistory = $jSummary->getIncomes($timestamps['start'], $timestamps['end'] + 1, $todayTimestamp, $address->id);

        $daysArray = [];

        for ($k = 1; $k <= $numberOfDays; ++$k) {
            $daysArray[$k] = $k;
        }

        $emptyDays = array_diff($daysArray, array_keys($incomesFromHistory));

        foreach ($daysArray as $day)
        {
            if (isset($incomesFromHistory[$day]['income'])) {
                $incomes[$day] = $incomesFromHistory[$day];
                continue;
            }

            $timestamp = $timestamps['start'] + ($day - 1) *$intervalStep;

            if ($todayTimestamp < $timestamp) {
                break;
            }

            $imei = $addressImeiData->getImeiByAddressAndTimestamps(
                $timestamp, $timestamp + $intervalStep, $address, $historyBeginning
            );

            if (empty($imei)) {

                continue;
            }

            // make average income for the 1st number of the next month until the first actual results
            if (
                (($timestamp == $nextMonthTimestamp) || ($timestamp == $todayTimestamp && $day == 1))
            )
            {
                $timestampEnd = $timestamp + $intervalStep;
                $income = $this->getIncomeByImeiAndTimestamps($timestamp, $timestampEnd, $imei, $address);
                if (is_null($income)) {
                    $income = $this->getAverageIncomeByLastMonth($year, $month, $timestamp, $todayTimestamp, $day, $address);
                }

                if (isset($incomesFromHistory[$day]['created'])) {
                    $incomes[$day] = $incomesFromHistory[$day];
                    $incomes[$day]['income'] = $income;
                } else {
                    $mashineStatistics = $this->getMashineStatisticsByImeiAndTimestamps($timestamp, $timestampEnd, $imei, $address);
                    $incomes[$day] = array_merge(['income' => $income], $mashineStatistics);
                }

                break;
            }

            $timestampDiff = $timestamp - $beginningTimestamp;
            $numberOfMashines = $this->getAllMashinesQueryByTimestamps(
                $timestamp, $timestamp + $intervalStep - 1, $imei->id
            );
            $numberOfMashines = QueryOptimizer::getItemsCountByQuery($numberOfMashines);

            if ($timestampDiff < 0 || $numberOfMashines == 0) {
                continue;
            }

            $income = $this->getIncomeByImeiAndTimestamps($timestamp, $timestamp + $intervalStep, $imei, $address);

            if (isset($incomesFromHistory[$day]['created'])) {
                $incomes[$day] = $incomesFromHistory[$day];
                $incomes[$day]['income'] = $income;
            } else {
                $timestampEnd = $timestamp + $intervalStep;
                $mashineStatistics = $this->getMashineStatisticsByImeiAndTimestamps($timestamp, $timestampEnd, $imei, $address);
                $incomes[$day] = array_merge(['income' => $income], $mashineStatistics);
            }
        }

        //$incomes = $incomes + $incomesFromHistory;

        return $incomes;
    }

    /**
     * Gets the average income for the last month
     *
     * @param int $year
     * @param int $month
     * @param timestamp $timestamp
     * @param timestamp $todayTimestamp
     * @param int $day
     * @param AddressBalanceHolder $address
     * @return decimal
     */ 
    public function getAverageIncomeByLastMonth($year, $month, $timestamp, $todayTimestamp, $day, $address)
    {
        // switch to previous month if necessary
        if ($timestamp == $todayTimestamp && $day == 1) {
            $year = $this->getYearLastMonth();
            $timestamp = time() + Jlog::TYPE_TIME_OFFSET;
            $month = $this->getLastMonth($timestamp);
        } else {
            $timestamp = time() + Jlog::TYPE_TIME_OFFSET;
            $year = date('Y', $timestamp);
            $month = date('m', $timestamp);
        }

        $incomes = $this->getIncomesByYearMonth($year, $month, $address);

        return $this->getAverageIncome($incomes);
    }

    /**
     * Gets class label by incomes
     *
     * @param array $income
     * @param \frontend\models\AddressBalanceHolder $address
     * @param int $start
     * @param int $end
     * 
     * @return string
     */ 
    public function makeClassByIncome($income, $address, $start, $end)
    {
        if ($address) {
            $isAddressCreated = $address->isCreatedByTimestamps($start, $end);
            $isAddressDeleted = $address->isDeletedByTimestamps($start, $end);
        }

        if (!is_null($income)) {

            while (is_array($income['income'])) {
                $income = $income['income'];
            }

            if (!isset($income['income']) || (empty($income['income']) && $income['income'] != '0')) {
                //$class = ' not-set-income';
            }

            if (!empty($income['created'])) {
                $class .= ' green-color';
            }

            if (!empty($income['deleted'])) {
                $class .= ' red-color';
            }

            if (!empty($income['encashment_date'])) {
                $class.= ' blue-color';
            }

            if (empty($income['allHours'])) {

                $class = ' not-set-income';
                if (!empty($income['is_cancelled'])) {
                    $class .= ' cancelled';
                }

            } else {
                $idlePercentage = (float)$income['idleHours'] / (float)$income['allHours'] * 100;

                if ($idlePercentage <= 20) {
                    $class .= ' white-color';
                } elseif ($idlePercentage <= 40) {
                    $class .= ' light-grey';
                } elseif ($idlePercentage <= 60) {
                    $class .= ' middle-grey';
                } elseif ($idlePercentage <= 80) {
                    $class .= ' heavy-grey';
                } else {
                    $class .= ' dark-grey';
                }
            }
        } else {
            $class = ' not-set-income';
        }

        if ($isAddressCreated) {
            $class .= ' address-green-color';
        }

        if ($isAddressDeleted) {
            $class .= ' address-red-color';
        }

        return $class;
    }

    /**
     * Gets incomes aggregated data
     *
     * @param ActiveDataProvider $dataProvider
     * @param int $year
     * @param int $month
     * @param int $days
     * @param timestamp $timestamp
     * @return decimal
     */ 
    public function getIncomesAggregatedData($dataProvider, $year, $month, $days, $timestamp)
    {
        $summaryTotal = [];
        $countTotal = 0;
        $idlesTotal = [];
        $data = [];
        $k = 0;
        $timestampEnd = $this->getTimestampByYearMonthDay($year, $month, $days, false);
        $timestampStart = $this->getTimestampByYearMonthDay($year, $month, '01', true);

        foreach (QueryOptimizer::getItemsByQuery($dataProvider->query) as $balanceHolder) {
            $addressQuery = $balanceHolder->getAddressBalanceHoldersQueryByTimestamp($timestampStart, $timestampEnd);
            $addresses = QueryOptimizer::getItemsByQuery($addressQuery);

            foreach ($addresses as $address) {
                $data[$k] = [];
                $incomes = $this->getIncomesByYearMonth($year, $month, $address);
                $countTotal += QueryOptimizer::getItemsCountByQuery($this->getAllMashinesQueryByYearMonth($year, $month, $address));
                for ($i = 1, $j = 0; $i <= $days; ++$i) {
                    $summaryTotal[$i] += $this->parseFloat($incomes[$i]['income'], 2);
                    $idlesTotal[$i] += $this->parseFloat($incomes[$i]['idleHours'], 2);
                    $start = $this->getTimestampByYearMonthDay($year, $month, $i, true);
                    $end = $this->getTimestampByYearMonthDay($year, $month, $i, false);
                    $class = $this->makeClassByIncome($incomes[$i], $address, $start, $end);
                    $data[$k][$i] = [
                        'timestampStart' => $this->getTimestampByYearMonthDay($year, $month, $i, true),
                        'timestampEnd' => $this->getTimestampByYearMonthDay($year, $month, $i, false),
                        'class' => $class,
                        'address_id' => $address->id
                    ];
                }
                $data[$k]['incomes'] = $incomes;
                ++$k;
            }

        }

        $data['summaryTotal'] = $summaryTotal;
        $data['idlesTotal'] = $idlesTotal;
        $data['countTotal'] = $countTotal;

        return $data;
    }

    /**
     * Parses to decimal
     *
     * @param decimal $number
     * @param int $digits
     * @return decimal
     */ 
    public function parseFloat($number, $digits)
    {
        if ((int)$number != $number) {
            $number = Yii::$app->formatter->asDecimal($number, $digits);
        }

        return $number;
    }

    /**
     * Gets display variants: general or detailed summary
     *
     * @return decimal
     */ 
    public function getTypesOfDisplay()
    {

        return [
            self::TYPE_GENERAL => Yii::t('frontend', 'General'),
            self::TYPE_DETAILED => Yii::t('frontend', 'Detailed')
        ];
    }

    /**
     * Gets day average income by the month incomes
     *
     * @param array $incomes
     * @return decimal
     */ 
    public function getAverageIncome($incomes)
    {
        $totalIncome = 0;
        $totalDays = 0;
        foreach ($incomes as $day => $income) {
            if (!is_null($income['income']) && $income['income'] != '') {
                $totalIncome += $income['income'];
                ++$totalDays;
            }
        }

        if ($totalDays != 0) {
            $income = $totalIncome / $totalDays;
            $income = $this->parseFloat($income, 1);
        }

        if (is_array($income)) {
            $income = $income['income'];
        }

        return $income;
    }

    /**
     * Check whether day is a weekend
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return bool
     */ 
    public function isWeekend($year, $month, $day)
    {
        $timestamp = strtotime($year.'-'.$month.'-'.$day);

        return date('N', $timestamp) >= 6;
    }

    /**
     * Makes events string 
     *
     * @param array $incomeData
     * @param int $addressId
     * @param int $start
     * @param int $end
     * 
     * @return string
     */ 
    public function getEventsAsString($incomeData, $addressId, $start, $end)
    {
        $eventsString = '';
        $addressQuery = AddressBalanceHolder::find()->where(['id' => $addressId])->limit(1);
        $address = QueryOptimizer::getItemByQuery($addressQuery);

        if (!empty($incomeData['created'])) {
            $eventsString .= Yii::t('frontend', 'Addition').', ';
        }

        if (!empty($incomeData['deleted'])) {
            $eventsString .= Yii::t('frontend', 'Deletion').', ';
        }

        if (!empty($incomeData['encashment_date'])) {
            $eventsString .= Yii::t('frontend', 'Encashment').', ';
        }

        if ($address && $address->isCreatedByTimestamps($start, $end)) {
             $eventsString .= Yii::t('frontend', 'Address Was Created').', ';
        }

        if ($address && $address->isDeletedByTimestamps($start, $end)) {
             $eventsString .= Yii::t('frontend', 'Address Was Deleted').', ';
        }

        $eventsString = trim($eventsString);

        if (!empty($eventsString)) {
            $eventsString = mb_substr($eventsString, 0, mb_strlen($eventsString) - 1);
        }

        return $eventsString;
    }

    /**
     * Gets estimated idle hours value, regarding cb connection is common for all WM
     * 
     * @param array $params
     * 
     * @return double
     */
    public function getEstimatedIdleHours($params)
    {
        $idleHours = $params['idleHours'];

        if ($params['all'] > 1) {
            $cbConnectionIdleHours = explode("**", $params['idleHoursReasons'])[2];
            $idleHours -= ($params['all'] - 1)*($cbConnectionIdleHours/$params['all']);
            $idleHours = $this->parseFloat($idleHours, 2);
        }

        return $idleHours >= 0 ? $idleHours : 0;
    }
}

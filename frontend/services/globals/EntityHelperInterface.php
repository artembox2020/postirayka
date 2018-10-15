<?php

namespace frontend\services\globals;

use yii\di\Instance;

/**
 * Interface EntityHelperInterface
 * @package frontend\services\globals
 */
interface EntityHelperInterface
{
    /**
     * Attempts to get array of objects, filtered by status value
     * If not found, empty array is to be returned
     * @param Instance $instance
     * @param int $status
     * @return array
     */
    public function tryFilteredStatusDataEx($instance, $status);

    /**
     * Gets and maps filtered status data, specified by $params parameter
     * 
     * @param Instance $instance
     * @param int $status
     * @param array $params
     * @return array
     * @throws \yii\web\HttpException
     */
    public function tryFilteredStatusDataMapped($instance, $status, Array $params, Array $unitIds = []);

    /**
     * @param array $params
     * @return \yii\jui\AutoComplete
     * @throws \yii\web\HttpException
     */
    public function AutoCompleteWidgetFilteredData(Array $params);

    /**
     * Attempts to get relation of the instance
     * In case of not existing returns bool(false)
     * 
     * @param Instance $unit
     * @param string $relation
     * @return Instance|bool
     */
    public function tryUnitRelation($unit, $relation);

    /**
     * Attempts to retrieve relations data, specified by $params
     * In case of not existing returns bool(false)
     * 
     * @param Instance $unit
     * @param array $params
     * @return string|bool
     * @throws \yii\web\HttpException
     */
    public function tryUnitRelationData($unit, $params);
    
    /**
     * Submits the form, defined, on selectors events
     * 
     * @param string $formSelector
     * @param array $eventSelectors
     * @param int $timeDelay
     * @return javascript
     */
    public function submitFormOnInputEvents($formSelector, Array $eventSelectors, $timeDelay);

    /**
     * Removes redundant grids on the page, generated by Pjax
     * 
     * @param string $gridSelector
     * @return javascript
     */
    public function removeRedundantGrids($gridSelector);

    /**
     * Creates params based on $_GET data
     * 
     * @param array $requiredParams
     * @return array
     */
    public function makeParamsFromRequest(Array $requiredParams);

    /**
     * Renders popup window view
     * 
     * @param string $imgSrcs
     * @param string $text
     * @return string
     */
    public static function makePopupWindow($imgSrcs, $text);

    /**
     * Gets the base query from -data table (history) 
     * 
     * @param timestamp $start
     * @param timestamp $end
     * @param Instance $instance
     * @param Instance $bInstance
     * @param string $field
     * @param string $select
     * @return ActiveDbQuery
     */
    public function getBaseUnitQueryByTimestamps($start, $end, $instance, $bInstance, $field, $select);

    /**
     * Makes array of non-zero intervals from -data table (history) 
     * 
     * @param timestamp $start
     * @param timestamp $end
     * @param Instance $instance
     * @param Instance $bInstance
     * @param string $fieldInstance
     * @param string $select
     * @param string $field
     * @return array
     */
    public function makeNonZeroIntervalsByTimestamps($start, $end, $instance, $bInstance, $fieldInstance, $select, $field);

    /**
     * Gets unit income by the ready non-zero time interval from -data table (history)
     * 
     * @param timestamp $start
     * @param timestamp $end
     * @param Instance $inst
     * @param Instance $bInst
     * @param string $fieldInst
     * @param string $select
     * @param string $field
     * @param bool $isFirst
     * @return decimal
     */
    public function getUnitIncomeByNonZeroTimestamps($start, $end, $inst, $bInst, $fieldInst, $select, $field, $isFirst);

    /**
     * Calculates unit idle hours by timestamps
     * 
     * @param timestamp $start
     * @param timestamp $end
     * @param Instance $inst
     * @param Instance $bInst
     * @param string $fieldInst
     * @param string $select
     * @param int $timeIdleHours
     * @return decimal
     */
    public function getUnitIdleHoursByTimestamps($start, $end, $inst, $bInst, $fieldInst, $select, $timeIdleHours);
}

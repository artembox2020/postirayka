<?php

namespace frontend\components;

use Yii;
use yii\base\Component;
use yii\helpers\Html;
use frontend\services\globals\Entity;
use yii\helpers\ArrayHelper;
use frontend\models\ImeiDataSearch;
use frontend\models\Imei;
use frontend\models\WmMashine;

/**
 * Class MonitoringBuilder
 * @package frontend\components
 */
class MonitoringBuilder extends Component {
    private $monitoringController;
    public $layout;
    
    public const RED_FILLNESS_INDICATOR = 60;
    public const CONNECTION_IDLES_TIME = 1800;
    public const DATE_TIME_FORMAT = 'd.m.y H:i:s';

    /**
     * @inheritdoc
     */
    public function __construct($monitoringController)
    {
        $this->monitoringController = $monitoringController;
        $this->layout = Yii::$app->layout;
    }

    /**
     * Renders common data view
     * 
     * @param yii\data\ActiveDataProvider $dataProvider
     * @param frontend\models\ImeiDataSearch $searchModel
     * 
     * @return string
     */
    public function renderCommon($dataProvider, $searchModel)
    {
        $data = $this->getData($dataProvider, $searchModel);

        return Yii::$app->view->render("@frontend/views/monitoring/".$this->layout."/common", ['data' => $data]);
    }

    /**
     * Renders technical data view
     * 
     * @param yii\data\ActiveDataProvider $dataProvider
     * @param frontend\models\ImeiDataSearch $searchModel
     * 
     * @return string
     */
    public function renderTechnical($dataProvider, $searchModel)
    {
        $data = $this->getData($dataProvider, $searchModel);

        return Yii::$app->view->render("@frontend/views/monitoring/".$this->layout."/technical", ['data' => $data]);
    }

    /**
     * Renders financial data view
     * 
     * @param yii\data\ActiveDataProvider $dataProvider
     * @param frontend\models\ImeiDataSearch $searchModel
     * 
     * @return string
     */
    public function renderFinancial($dataProvider, $searchModel)
    {
        $data = $this->getData($dataProvider, $searchModel);

        return Yii::$app->view->render("@frontend/views/monitoring/".$this->layout."/financial", ['data' => $data]);
    }

    /**
     * Gets monitoring data
     * 
     * @param yii\data\ActiveDataProvider $dataProvider
     * @param frontend\models\ImeiDataSearch $searchModel
     * 
     * @return array
     */
    public function getData($dataProvider, $searchModel)
    {
        global $globalMonitoringData;
        
        if (!empty($globalMonitoringData)) {

            return $globalMonitoringData;
        }

        $controller = $this->monitoringController;
        $searchModel = new ImeiDataSearch();
        $data = [];

        $imeis = $dataProvider->query->all();
        foreach ($imeis as $imei) {
            $dProvider = $searchModel->searchImeiCardDataByImeiId($imei->id);
            $imeiData = $dProvider->query->one();
            $common = $this->getCommonData($imei);
            $financial = $this->getFinancialData($searchModel, $imei, $imeiData);
            $technical = $this->getTechnicalData($searchModel, $imei, $imeiData);
            $data[$imei->id] = ['common' => $common, 'financial' => $financial, 'technical' => $technical];
        }

        $globalMonitoringData = $data;

        return $data;
    }

    /**
     * Gets monitoring common data
     * 
     * @param frontend\models\Imei $imei
     * 
     * @return array
     */
    public function getCommonData($imei)
    {
        $address = $imei->fakeAddress;

        if (!$address) {

            return [];
        }

        $address->initSerialNumber();

        $balanceHolder = $imei->balanceHolder ?? $imei->getFakeBalanceHolder();
        $isDeleted  = $imei->balanceHolder ? false : true;

        $common = [
            'id' => $address->id,
            'name' => $address->name,
            'address' => $address->address,
            'floor' => $address->floor,
            'serialNumber' => $address->displaySerialNumber(),
            'imei' => $imei->imei,
            'bhId' => $balanceHolder->id,
            'bhName' => $balanceHolder->name,
            'is_deleted' => $isDeleted,
        ];

        return $common;
    }

    /**
     * Gets monitoring financial data
     * 
     * @param frontend\models\ImeiDataSearch $searchModel
     * @param frontend\models\Imei $imei
     * @param frontend\models\ImeiData $imeiData
     * 
     * @return array
     */
    public function getFinancialData($searchModel, $imei, $imeiData)
    {
        return [
            'in_banknotes' => $imeiData->in_banknotes,
            'fireproof_residue' => $imeiData->fireproof_residue,
            'money_in_banknotes' => $imeiData->money_in_banknotes,
            'last_encashment' => $searchModel->getScalarDateAndSumLastEncashmentByImeiId($imei->id),
            'pre_last_encashment' => $searchModel->getScalarDateAndSumPreLastEncashmentByImeiId($imei->id),
        ];
    }

    /**
     * Gets monitoring technical data
     * 
     * @param frontend\models\ImeiDataSearch $searchModel
     * @param frontend\models\Imei $imei
     * @param frontend\models\ImeiData $imeiData
     * 
     * @return array
     */
    public function getTechnicalData($searchModel, $imei, $imeiData)
    {
        $software = [
            'firmware_version_cpu' => $imei->firmware_version_cpu,
            'firmware_version' => $imei->firmware_version,
            'firmware_6lowpan' => $imei->firmware_6lowpan,
            'number_channel' => $imei->number_channel
        ];

        if (!empty($imei->capacity_bill_acceptance) && !empty($imeiData->in_banknotes)) {
            $fullness = (int)$imeiData->in_banknotes / (int)$imei->capacity_bill_acceptance;
            $fullness = $fullness * 100;
            $fullness = number_format($fullness, 2);
        } else {
            $fullness = 0;
        }
        $fullnessIndicator = 'green';

        if ($fullness >= self::RED_FILLNESS_INDICATOR) {
            $fullnessIndicator = 'red-tab';
        }

        $cpErrors = [1, 2,3, 4, 5, 6];
        $evtBillErrors = [1, 2, 3, 4, 4, 5, 6];
        $errorLabel = '';

        if (in_array($imeiData->packet, $cpErrors) || in_array($imeiData->evt_bill_validator, $evtBillErrors)) {
            $errorLabel = 'error';
        }

        $terminal = [
            'last_ping' => $imei->getLastPing(),
            'last_ping_class' => $imei->getLastPingClass(),
            'error' => $errorLabel,
            'last_ping_value' => date(self::DATE_TIME_FORMAT, $imei->getLastPingValue()),
            'level_signal' => $imeiData->getLevelSignal(),
            'phone_number' => $imei->phone_module_number,
            'money_in_banknotes' => Yii::$app->formatter->asDecimal($imeiData->getOnModemAccount(), 0),
            'fullness' => $fullness,
            'fullnessIndicator' => $fullnessIndicator,
            'in_banknotes' => $imeiData->in_banknotes,
            'imei' => $imei->imei
        ];

        $devices = [];

        $dataProviderWmMashine = $searchModel->searchWmMashinesByImeiId($imei->id);

        $mashines = $dataProviderWmMashine->query->all();

        foreach ($mashines as $model) {
            $indicator = 'green';
            $connectionIdleStates = [0, 16];
            $errorStates = [9, 10, 11, 12, 13, 14, 21, 25];

            if (in_array($model->current_status, $connectionIdleStates) || (time() - $model->ping > self::CONNECTION_IDLES_TIME)) {
                $indicator = 'darkgrey';
            } elseif (in_array($model->current_status, $errorStates)) {
                $indicator = 'red';
            }

            $lastPing = Yii::$app->formatter->asDate($model->ping, WmMashine::PHP_DATE_TIME_FORMAT);
            $timeParts = explode(" ", $lastPing);

            if (count($timeParts) >  1) {
                $lastPing = $timeParts[0]."<br>".$timeParts[1];
            }

            $deviceItem = [
                'type' => $model->type_mashine,
                'number_device' => $model->number_device,
                'level_signal' => $model->level_signal,
                'id' => $model->id,
                'bill_cash' => $model->bill_cash,
                'current_status' => Yii::t('frontend', $model->getState()),
                'indicator' => $indicator,
                'display' => $model->display,
                'last_ping' => $lastPing,
                'money_in_banknotes' =>  \Yii::$app->formatter->asDecimal($model->bill_cash, 0),
            ];

            $devices[$model->number_device] = $deviceItem;
        }

        $technical = ['software' => $software, 'terminal' => $terminal, 'devices' => $devices];

        return $technical;
    }
}
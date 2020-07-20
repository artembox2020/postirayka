<?php

namespace frontend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use frontend\services\parser\CParser;
use frontend\services\globals\QueryOptimizer;

/**
 * This is the model class for table "imei_data".
 *
 * @property int $id
 * @property int $imei_id
 * @property int $created_at
 * @property integer $date
 * @property string $imei
 * @property double $level_signal
 * @property float $on_modem_account
 * @property double $in_banknotes
 * @property float $money_in_banknotes
 * @property float $fireproof_residue
 * @property double $price_regim
 * @property int $updated_at
 * @property int $is_deleted
 * @property int $deleted_at
 * @property int $status
 * @property string $packet
 * @property string $evt_bill_validator
 * @property string $cb_version
 *
// * @property Imei $imei
 * @property WmMashine[] $wmMashines
 */
class ImeiData extends \yii\db\ActiveRecord
{
    public $bill_acceptance_fullness;
    public $bill_acceptance;
    public $software_versions;
    public $actions;
    public $date_last_encashment;
    public $date_sum_last_encashment;
    public $date_sum_pre_last_encashment;
    public $counter_last_encashment;
    public $counter_zeroing;
    public $technical_injection;

    const TYPE_ACTION_CPU_RELOAD = 1;
    const TYPE_ACTION_BILL_ACCEPTANCE_RELOAD = 2;
    const TYPE_ACTION_ZIGBEE_RELOAD = 3;
    const TYPE_ACTION_MODEM_RELOAD = 4;
    const TYPE_ACTION_LOGDISK_FORMAT = 5;
    const TYPE_ACTION_TIME_SET = 6;
    const TYPE_ACTION_BILL_ACCEPTANCE_BLOCK = 7;

    const TYPE_HALF_HOUR = 1800;

    const TYPE_BILL_OK = 0;
    const TYPE_BILL_ERRMOTOR = 1;
    const TYPE_BILL_ERRSENSOR = 2;
    const TYPE_BILL_ERRROM = 3;
    const TYPE_BILL_ERRBILLJAMMED = 4;
    const TYPE_BILL_ERRUNEXP = 5;
    const TYPE_BILL_ERRVALIDFULL = 6;

    const TYPE_DATE_FORMAT = 'd.m.Y H:i';

    const CP_STATUS_TERMINAL_NOT_IN_TOUCH = 8;

    public $status_central_board = [
        '1' => 'ErrFram',
        '2' => 'ErrFlash',
        '3' => 'ErrLogImage',
        '4' => 'ErrLogFormat',
        '5' => 'ErrSettings',
        '6' => 'Err6LowPan',
        '7' => 'OK',
        self::CP_STATUS_TERMINAL_NOT_IN_TOUCH => 'ErrTerminalNotInTouch',
        '9' => 'SUPPLY_OFF'
    ];

    public $status_bill_acceptor = [
        'OK',
        'ErrMotor',
        'ErrSensor',
        'ErrROM',
        'ErrBillJammed',
        'ErrUnexp',
        'ErrValidFull'
    ];

    // bill acceptance event constant list
    const evtBillValidator = [
        self::TYPE_BILL_OK => 'OK',
        self::TYPE_BILL_ERRMOTOR => 'ErrMotor',
        self::TYPE_BILL_ERRSENSOR => 'ErrSensor',
        self::TYPE_BILL_ERRROM => 'ErrROM',
        self::TYPE_BILL_ERRBILLJAMMED => 'ErrBillJammed',
        self::TYPE_BILL_ERRUNEXP => 'ErrUnexp',
        self::TYPE_BILL_ERRVALIDFULL => 'ErrValidFull',
        null => null
    ];

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
    public static function tableName()
    {
        return 'imei_data';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['imei_id'], 'required'],
            [['imei_id', 'created_at', 'updated_at', 'deleted_at', 'evt_bill_validator'], 'integer'],
            [['imei_id'], 'exist', 'skipOnError' => true, 'targetClass' => Imei::className(), 'targetAttribute' => ['imei_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('frontend', 'ID'),
            'imei_id' => Yii::t('frontend', 'Imei ID'),
//            'company_id' => Yii::t('frontnd', 'Company'),
            'status' => Yii::t('frontend', 'Status'),
            'created_at' => Yii::t('frontend', 'Created At'),
            'imei' => Yii::t('frontend', 'Imei'),
            'level_signal' => Yii::t('frontend', 'Level Signal'),
            'on_modem_account' => Yii::t('frontend', 'On Modem Account'),
            'in_banknotes' => Yii::t('frontend', 'In Banknotes'),
            'money_in_banknotes' => Yii::t('frontend', 'Money In Banknotes'),
            'fireproof_residue' => Yii::t('frontend', 'Fireproof Residue'),
            'price_regim' => Yii::t('frontend', 'Price Regim'),
            'updated_at' => Yii::t('frontend', 'Updated At'),
            'is_deleted' => Yii::t('frontend', 'Is Deleted'),
            'deleted_at' => Yii::t('frontend', 'Deleted At'),
            'bill_acceptance_fullness' => Yii::t('frontend', 'Bill Acceptance Fullness'),
            'bill_acceptance' => Yii::t('frontend', 'Bill Acceptance'),
            'software_versions' => Yii::t('frontend', 'Software Versions'),
            'actions' => Yii::t('frontend', 'Actions'),
            'date_last_encashment' => Yii::t('frontend', 'Date Last Encashment'),
            'date_sum_last_encashment' => Yii::t('frontend', 'Date And Sum Last Encashment'),
            'date_sum_pre_last_encashment' => Yii::t('frontend', 'Date And Sum Pre Last Encashment'),
            'counter_last_encashment' => Yii::t('frontend', 'Counter Last Encashment'),
            'counter_zeroing' => Yii::t('frontend', 'Counter Zeroing'),
            'technical_injection' => Yii::t('frontend', 'Technical Injection'),
            'evt_bill_validator' => Yii::t('imeiData', 'Event Bill Validator'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImeiRelation()
    {
        $query = Imei::find()->andWhere(['id' => $this->imei_id])->limit(1);

        return QueryOptimizer::getItemByQuery($query);
    }

    public static function find()
    {
        return parent::find()->where(['imei_data.is_deleted' => false]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWmMashines()
    {
        return $this->hasMany(WmMashine::className(), ['imei_id' => 'id']);
    }

    /**
     * Gets bill acceptance data view
     */
    public function getBillAcceptanceData()
    {
        $result ='';

        if (!empty($this->imeiRelation->capacity_bill_acceptance) && !empty($this->in_banknotes)) {
            $fullness = (int)$this->in_banknotes / (int)$this->imeiRelation->capacity_bill_acceptance;
            $fullness = $fullness * 100;
            $fullness = number_format($fullness, 2);
        } else {
            $fullness = null;
        }
        
        return Yii::$app->view->render(
            '/monitoring/data/billAcceptanceData',
            ['model' => $this, 'fullness' => $fullness]
        );
    }

    /**
     * Gets software versions view
     */
    public function getSoftwareVersions()
    {

        return Yii::$app->view->render(
            '/monitoring/data/softwareVersions',
            ['model' => $this]
        );
    }

    /**
     * Gets current modem card info view
     */
    public function getModemCard()
    {

        return Yii::$app->view->render('/monitoring/data/modemCard', ['model' => $this]);
    }

    /**
     * Gets actions info view
     */
    public function getActions()
    {

        return Yii::$app->view->render('/monitoring/data/actions', ['model' => $this, 'actions' => $this->actionsList()]);
    }

    /**
     * Gets actions list
     */
    public function actionsList()
    {

        return [
            self::TYPE_ACTION_CPU_RELOAD => Yii::t('frontend', 'Action Cpu Reload'),
            self::TYPE_ACTION_BILL_ACCEPTANCE_RELOAD => Yii::t('frontend', 'Action Bill Acceptance Reload'),
            self::TYPE_ACTION_ZIGBEE_RELOAD => Yii::t('frontend', 'Action ZigBee Reload'),
            self::TYPE_ACTION_MODEM_RELOAD => Yii::t('frontend', 'Action Modem Reload'),
            self::TYPE_ACTION_LOGDISK_FORMAT => Yii::t('frontend', 'Action Logdisk Format'),
            self::TYPE_ACTION_TIME_SET => Yii::t('frontend', 'Action Time Set'),
            self::TYPE_ACTION_BILL_ACCEPTANCE_BLOCK => Yii::t('frontend', 'Action Bill Acceptance Block')
        ];
    }

    /**
     * Gets last encashment date  and sum, before timestamp accepted
     *
     * @param int $imeiId
     * @param int $timestampBefore
     * @return array|bool
     */
    public function getDateAndSumLastEncashmentByImeiId(int $imeiId, int $timestampBefore)
    {
        $cbLogSearch = new CbLogSearch();
        $data = $cbLogSearch->getLastEncashmentInfoByImeiId($imeiId, $timestampBefore);

        if (!empty($data['created_at'])) {

            return $data;
        }

        return false;

        /*$query = ImeiData::find()->andWhere(['imei_id' => $imeiId])
                                 ->andWhere(['money_in_banknotes' => 0])
                                 ->andWhere(['<', 'created_at', $timestampBefore])
                                 ->orderBy(['created_at' => SORT_DESC])
                                 ->limit(1);

        $item = QueryOptimizer::getItemByQuery($query);

        if ($item) {
            $resultQuery = ImeiData::find()->andWhere(['imei_id' => $imeiId])
                                           ->andWhere(['<', 'created_at', $item->created_at])
                                           ->andWhere(['!=', 'money_in_banknotes', 0])
                                           ->orderBy(['created_at' => SORT_DESC])
                                           ->limit(1);
            $resultItem = QueryOptimizer::getItemByQuery($resultQuery);

            if ($resultItem) {

                return [
                    'created_at' => $resultItem->created_at,
                    'money_in_banknotes' => $resultItem->money_in_banknotes
                ];
            }
        }

        return false;
        */
    }

    /**
     * Gets encashment history by imei id and timestamps
     * 
     * @param int $imeiId
     * @param timestamp $start
     * @param  timestamp $end
     * @return array
     */
    public function getEncashmentHistoryByImeiId($imeiId, $start, $end)
    {
        $history = [];
        $bhSummarySearch = new BalanceHolderSummarySearch();
        while ($end > $start) {
            $encashmentInfo = $this->getDateAndSumLastEncashmentByImeiId($imeiId, $end);
            if (empty($encashmentInfo) || $encashmentInfo['created_at'] < $start) {

                break;
            }

            $end = $encashmentInfo['created_at'];
            $dayBeginningTimestamp = $bhSummarySearch->getDayBeginningTimestampByTimestamp($end);
            $history[$dayBeginningTimestamp][] = $encashmentInfo;
        }

        return $history;
    }

    /**
     * Gets last encashment date  and sum, like string
     * @param $imeiId
     * @return bool|string
     * @throws \yii\base\InvalidConfigException
     */
    public function getScalarDateAndSumPreLastEncashmentByImeiId($imeiId)
    {
        $timestampBefore = time() + Jlog::TYPE_TIME_OFFSET;
        $dateSumLastEncashment = $this->getDateAndSumLastEncashmentByImeiId($imeiId, $timestampBefore);
        if ($dateSumLastEncashment) {
            $dateSumPreLastEncashment = $this->getDateAndSumLastEncashmentByImeiId($imeiId, $dateSumLastEncashment['created_at']);
            $dateEncashment = $dateEncashment = date(self::TYPE_DATE_FORMAT, $dateSumPreLastEncashment['created_at']);

            return  $dateEncashment . '<br>' . $dateSumPreLastEncashment['money_in_banknotes'] . ' грн';
        }

        return false;
    }

    /**
     * Gets pre-last encashment date  and sum, like string
     * 
     * @param int $imeiId
     * @return string
     */
    public function  getScalarDateAndSumLastEncashmentByImeiId($imeiId)
    {
        $timestampBefore = time() + Jlog::TYPE_TIME_OFFSET;
        $dateSumEncashment = $this->getDateAndSumLastEncashmentByImeiId($imeiId, $timestampBefore);

        if ($dateSumEncashment) {
            $dateEncashment = date(self::TYPE_DATE_FORMAT, $dateSumEncashment['created_at']);

            return  $dateEncashment.'<br>'.$dateSumEncashment['money_in_banknotes'].' грн';
        }

        return false;
    }

    /**
     * @param $imeiId
     * @return bool|mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function  getScalarSumLastEncashmentByImeiId($imeiId)
    {
        $timestampBefore = time() + Jlog::TYPE_TIME_OFFSET;
        $dateSumEncashment = $this->getDateAndSumLastEncashmentByImeiId($imeiId, $timestampBefore);

        if ($dateSumEncashment) {
            $dateEncashment =  \Yii::$app->formatter->asDate($dateSumEncashment['created_at'], 'short');

            return  $dateSumEncashment['money_in_banknotes'];
        }

        return false;
    }

    public function  getScalarDateLastEncashmentByImeiId($imeiId)
    {
        $timestampBefore = time() + Jlog::TYPE_TIME_OFFSET;
        $dateSumEncashment = $this->getDateAndSumLastEncashmentByImeiId($imeiId, $timestampBefore);

        if ($dateSumEncashment) {
            $dateEncashment =  \Yii::$app->formatter->asDate($dateSumEncashment['created_at'], 'short');

            return  $dateEncashment;
        }

        return false;
    }

    /**
     * Gets the real CP Status value
     *
     * @return string|bool
     */
    public function getCPStatus()
    {
        $currentTimestamp = time() + Jlog::TYPE_TIME_OFFSET;
        if ($currentTimestamp - $this->created_at > self::TYPE_HALF_HOUR) {

            return Yii::t('imeiData', 'ErrTerminal');
        }

        $packet = $this->packet;

        $cParser = new CParser();

        $cpStatus = $cParser->getCPStatusFromPacketField($packet, $this);

        if (!$cpStatus) {

            if (isset($this->evt_bill_validator) && in_array($this->evt_bill_validator, array_keys($this->status_central_board))) {

                return Yii::t('imeiData', $this->status_central_board[$this->evt_bill_validator]);
            }
        } else {

            return $cpStatus;
        }

        return false;
    }

    /**
     * Gets the real Event Bill Validator value
     *
     * @return string|bool
     */
    public function getEvtBillValidator() {

        if (!isset($this->evt_bill_validator) || !in_array($this->evt_bill_validator, array_keys(self::evtBillValidator))) {

            return false;
        }

        $packet = $this->packet;

        $cParser = new CParser();

        $cpStatus = $cParser->getCPStatusFromPacketField($packet, $this);

        if ($cpStatus) {

            return Yii::t('imeiData', self::evtBillValidator[$this->evt_bill_validator]);
        }

        return false;
    }

    /**
     * Gets on modem account value
     *
     * @return string|bool
     */
    public function getOnModemAccount()
    {
        if (!is_null($this->imeiRelation->on_modem_account)) {

            return $this->imeiRelation->on_modem_account;
        }

        return $this->on_modem_account;
    }

    /**
     * Gets the level signal value
     *
     * @return string|bool
     */
    public function getLevelSignal()
    {
        if (!is_null($this->imeiRelation->level_signal)) {

            return $this->imeiRelation->level_signal;
        }

        return $this->level_signal;
    }

    /**
     * Makes class indicating whether active action
     *
     * @param string $action
     * @return string
     */
    public function makeActiveActionClass($action)
    {
        $imeiAction = new ImeiAction();
        $class = $imeiAction->makeClass($this->imei_id, $action);

        return $class;
    }
}

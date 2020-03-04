<?php

namespace frontend\controllers;

use frontend\models\CbLog;
use frontend\models\CbLogSearch;
use frontend\models\CbLogSearchFilter;
use frontend\models\TempLog;
use frontend\models\WmLog;
use frontend\services\custom\Debugger;
use frontend\services\globals\Entity;
use frontend\services\globals\EntityHelper;
use Yii;
use frontend\models\Jlog;
use frontend\models\AddressBalanceHolder;
use frontend\models\WmMashine;
use frontend\models\GdMashine;
use frontend\models\JlogSearch;
use frontend\models\JlogInitSearch;
use frontend\models\JlogDataSearch;
use frontend\models\JlogDataCpSearch;
use frontend\models\Imei;
use yii\data\ArrayDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

/**
 * JournalController implements the CRUD actions for journal logs
 */
class JournalController extends \frontend\controllers\Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Jlog models.
     * 
     * @param bool $isEncashment
     * @return mixed
     */
    public function actionIndex($isEncashment = false)
    {
        if ( !\Yii::$app->user->can('viewFinData', ['class'=>static::class]) && !\Yii::$app->user->can('viewTechData', ['class'=>static::class]) ) {
            \Yii::$app->getSession()->setFlash('AccessDenied', 'Access denied');
            return $this->render('@app/modules/account/views/denied/access-denied');
        }

        $searchModel = new JlogSearch();
        $entityHelper = new EntityHelper();
        $params = $this->makeParams(Yii::$app->request->queryParams);

        $addresses = $searchModel->getAddressesMapped();
        $params = $searchModel->setParams($searchModel, $params, $params);
        $typePackets = Jlog::getTypePackets();
        $pageSizes = jlog::getPageSizes();
        $searchModel->inputValue['date'] = $params['inputValue']['date'];
        $searchModel->val2['date'] = $params['val2']['date'];
        $eventSelectors = [
            'keyup' => '.journal-filter-form input[name=imei], .journal-filter-form input[name=address]',
            'change' => '.journal-filter-form select, .journal-filter-form input[name=address], .journal-filter-form input[name=imei]'
        ];

        if (!empty($isEncashment)) {
            $params['type_packet'] = Jlog::TYPE_PACKET_ENCASHMENT;
        }

        $dataProvider = $this->getDataProvider($searchModel, $params);

        $submitFormOnInputEvents = $entityHelper->submitFormOnInputEvents('.journal-filter-form', $eventSelectors);
        $removeRedundantGrids = $entityHelper->removeRedundantGrids('.journal-grid-view');
        $columnFilterScript = $this->getColumnFilterScript($params);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'typePackets' => $typePackets,
            'params' => $params,
            'submitFormOnInputEvents' => $submitFormOnInputEvents,
            'removeRedundantGrids' => $removeRedundantGrids,
            'columnFilterScript' => $columnFilterScript,
            'addresses' =>  $addresses,
            'journalController' => $this,
            'pageSizes' => $pageSizes
        ]);
    }

    /**
     * Lists all Jlog models by mashine id
     *
     * @param int $id
     * @param bool $redirectAction
     * @return mixed
     */
    public function actionIndexByMashine($id, $redirectAction = false)
    {
        $this->makeRedirection($redirectAction);

        $mashine = WmMashine::findOne($id);
        $searchModel = new JlogSearch();
        $entityHelper = new EntityHelper();
        $params = $this->makeParams(Yii::$app->request->queryParams);
        $params = $searchModel->setParams($searchModel, $params, $params);

        $searchModel->inputValue['date'] = $params['inputValue']['date'];
        $searchModel->val2['date'] = $params['val2']['date'];
        $typePackets = Jlog::getTypePackets();
        $pageSizes = Jlog::getPageSizes();
        $eventSelectors = [
            'change' =>
                '.journal-filter-form select,'.
                '.journal-filter-form input#mashine-from-date,'.
                '.journal-filter-form input#mashine-to-date'
        ];
        $submitFormOnInputEvents = $entityHelper->submitFormOnInputEvents('.journal-filter-form', $eventSelectors);
        $removeRedundantGrids = $entityHelper->removeRedundantGrids('.journal-grid-view');
        $columnFilterScript = $this->getColumnFilterScript($params);

        $dataProvider = $searchModel->searchByMashine($params, $mashine);

        return $this->renderPartial('index-by-mashine', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'typePackets' => $typePackets,
            'params' => $params,
            'submitFormOnInputEvents' => $submitFormOnInputEvents,
            'removeRedundantGrids' => $removeRedundantGrids,
            'columnFilterScript' => $columnFilterScript,
            'journalController' => $this,
            'pageSizes' => $pageSizes
        ]);
    }

    /**
     * Renders journal logs
     *
     * @param array $prms
     * @return string
     */
    public function actionLogs($prms)
    {
        $searchModel = new CbLogSearch();
        $searchFilter = new CbLogSearchFilter();
        $entity = new Entity();
        $params = $this->makeParams($prms);
        $params = $searchModel->setParams($searchModel, $params, $prms);
        $dataProvider = $searchModel->search($params);
        $dateFieldName = $searchFilter->getDateFieldNameByParams($params);

        $searchModel->inputValue[$dateFieldName] = $params['inputValue'][$dateFieldName];
        $searchModel->val2[$dateFieldName] = $params['val2'][$dateFieldName];
        $itemsCount = CbLogSearch::TYPE_ITEMS_LIMIT;
        $itemsCount = $itemsCount < ($dataCount = $dataProvider->query->count()) ? $itemsCount : $dataCount;
        $columnFilterScript = $this->getColumnFilterScript($params, $itemsCount);

        return $this->renderPartial('logs/index', [
            'searchModel' => $searchModel,
            'searchFilter' => $searchFilter,
            'dataProvider' => $dataProvider,
            'params' => $params,
            'itemsCount' => $itemsCount,
            'columnFilterScript' => $columnFilterScript
        ]);
    }

    /**
     * Renders index journal view by actual  data
     *
     * @param ActiveDataProvider $dataProvider
     * @param JlogSearch $searchModel
     * @param array $params
     * @return string
     */
    public function renderAll($dataProvider, $searchModel, $params)
    {

        return $this->renderPartial('/journal/all/index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'params' => $params
        ]);
    }

    /**
     * Renders journal initialization
     *
     * @param array $prms
     * @param yii\data\ActiveDataProvider $dataProvider
     * @return string
     */
    public function actionInit($params, $dataProvider)
    {
        $searchModel = new JLogInitSearch();
        $searchFilter = new CbLogSearchFilter();
        $dataProvider->query->andWhere(['type_packet' => Jlog::TYPE_PACKET_INITIALIZATION]);
        $params = $this->makeParams($params);

        $params = $searchModel->setParams($searchModel, $params, $params);

        $searchModel->inputValue['date'] = $params['inputValue']['date'];
        $searchModel->val2['date'] = $params['val2']['date'];

        return $this->renderPartial('init/index', [
            'searchModel' => $searchModel,
            'searchFilter' => $searchFilter,
            'dataProvider' => $dataProvider,
            'params' => $params
        ]);
    }

    /**
     * Renders journal data
     *
     * @param array $prms
     * @param yii\data\ActiveDataProvider $dataProvider
     * @param bool $isMashine
     * @return string
     */
    public function actionData($prms, $dataProvider, $isMashine = false)
    {
        $searchModel = new JLogDataSearch();
        $searchFilter = new CbLogSearchFilter();
        $dataProvider->query->andWhere(['type_packet' => Jlog::TYPE_PACKET_DATA]);

        $searchModel->setMashineNumber($searchModel, $prms, $isMashine);
        $arrayProvider = $searchModel->searchData($prms, $dataProvider->query);

        $searchModel->inputValue['date'] = $prms['inputValue']['date'];
        $searchModel->val2['date'] = $prms['val2']['date'];

        return $this->renderPartial('data/index', [
            'searchModel' => $searchModel,
            'searchFilter' => $searchFilter,
            'dataProvider' => $dataProvider,
            'arrayProvider' => $arrayProvider,
            'params' => $prms,
        ]);
    }

    /**
     * Renders journal central board data
     *
     * @param array $prms
     * @params yii\data\ActiveDataProvider $dataProvider
     * @return string
     */
    public function actionDataCp($params, $dataProvider)
    {
        $searchModel = new JLogDataCpSearch();
        $searchFilter = new CbLogSearchFilter();
        $dataProvider->query->andWhere(['type_packet' => Jlog::TYPE_PACKET_DATA]);

        $searchModel->inputValue['date'] = $params['inputValue']['date'];
        $searchModel->val2['date'] = $params['val2']['date'];

        return $this->renderPartial('data/cp', [
            'searchModel' => $searchModel,
            'searchFilter' => $searchFilter,
            'dataProvider' => $dataProvider,
            'params' => $params,
        ]);
    }

    /**
     * Runs encashment action
     *
     * @param array $params
     * @return string
     */
    public function actionEncashment($params)
    {

        return Yii::$app->runAction('encashment-journal/index', ['params' => array_merge($params, ['isEncashment' => 1])]);
    }

    /**
     * Gets the main journal js 
     * 
     * @param array $params
     * @param int|bool $itemsCount
     * @return string
     */
    private function getColumnFilterScript($params, $itemsCount = false)
    {
        $searchFilter = new CbLogSearchFilter();
        $dateFieldName = $searchFilter->getDateFieldNameByParams($params);

        return Yii::$app->view->render(
            "/journal/filters/columnFilter",
            [
                'today' => Yii::t('frontend', 'Today'),
                'tomorrow' => Yii::t('frontend', 'Tomorrow'),
                'yesterday' => Yii::t('frontend', 'Yesterday'),
                'lastweek' => Yii::t('frontend', 'Lastweek'),
                'lastmonth' => Yii::t('frontend', 'Lastmonth'),
                'lastyear' => Yii::t('frontend', 'Lastyear'),
                'certain' => Yii::t('frontend', 'Certain'),
                'params' => $params,
                'pageSize' => $params['page_size'] ? $params['page_size'] : JlogDataSearch::TYPE_PAGE_SIZE,
                'dateFieldName' => $dateFieldName,
                'itemsCount' => $itemsCount
            ]
        );
    }

    /**
     * Renders appropriate action regarding the packet type 
     * 
     * @param array $params
     * @param yii\data\ActiveDataProvider $dataProvider
     * @param bool $isMashine
     * @return string
     */
    public function renderAppropriatePacket($params, $dataProvider, $isMashine = false)
    {
        switch ($params['type_packet']) {
            case Jlog::TYPE_PACKET_LOG:

                return $this->actionLogs($params);
            case Jlog::TYPE_PACKET_INITIALIZATION:

                return $this->actionInit($params, $dataProvider);
            case Jlog::TYPE_PACKET_DATA:

                return $this->actionData($params, $dataProvider, $isMashine);
            case Jlog::TYPE_PACKET_DATA_CP:

                return $this->actionDataCp($params, $dataProvider);
            case Jlog::TYPE_PACKET_ENCASHMENT:

                return $this->actionEncashment($params);
        }
    }

    /**
     * Lists all Jlog models by address id
     *
     * @param int $id
     * @param string|bool $redirectAction
     * @return string
     */
    public function actionIndexByAddress(int $id, $redirectAction = false): string
    {
        $this->makeRedirection($redirectAction);

        $searchModel = new JlogSearch();
        $entityHelper = new EntityHelper();
        $params = Yii::$app->request->queryParams;

        $params = array_merge($params, $searchModel->setParams($searchModel, $params, $params));
        $params = $searchModel->setAddressId($searchModel, $params);
        $params = $this->makeParams($params);

        $searchModel->inputValue['date'] = $params['inputValue']['date'];
        $searchModel->val2['date'] = $params['val2']['date'];
        $searchModel->from_date = $params['JlogSearch']['from_date'];
        $searchModel->to_date = $params['JlogSearch']['to_date'];
        $typePackets = Jlog::getTypePackets();
        $pageSizes = Jlog::getPageSizes();

        $eventSelectors = [
            'change' =>
                '.journal-filter-form select,'.
                '.journal-filter-form input#mashine-from-date,'.
                '.journal-filter-form input#mashine-to-date'
        ];
        $submitFormOnInputEvents = $entityHelper->submitFormOnInputEvents('.journal-filter-form', $eventSelectors);
        $removeRedundantGrids = $entityHelper->removeRedundantGrids('.journal-grid-view');
        $columnFilterScript = $this->getColumnFilterScript($params);

        $dataProvider = $this->getDataProvider($searchModel, $params);

        return $this->renderPartial('index-by-address', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'typePackets' => $typePackets,
            'params' => $params,
            'submitFormOnInputEvents' => $submitFormOnInputEvents,
            'removeRedundantGrids' => $removeRedundantGrids,
            'columnFilterScript' => $columnFilterScript,
            'journalController' => $this,
            'pageSizes' => $pageSizes
        ]);
    }

    /**
     * Fills in params array with values regarding required params
     * 
     * @param array $params
     * @return array
     */
    public function makeParams(array $params): array
    {
        $entityHelper = new EntityHelper();
        $requiredParams = [
            'type_packet', 'imei', 'address', 'id', 'selectionName', 'selectionCaretPos',
            'wm_mashine_number',
            'address_id',
            'filterCondition' => [
                'date', 'type_packet', 'address', 'imei', 'id',
                'number', 'number_device', 'created_at', 'unix_time_offset'
            ],
            'val1' => [
                'date', 'type_packet', 'address', 'imei', 'id',
                'number', 'number_device', 'created_at', 'unix_time_offset'
            ],
            'val2' => [
                'date', 'type_packet', 'address', 'imei', 'id',
                'number', 'number_device', 'created_at', 'unix_time_offset'
            ],
            'inputValue' => [
                'date', 'type_packet', 'address', 'imei', 'id',
                'number', 'number_device', 'created_at', 'unix_time_offset'
            ],
            'sort', 'dp-1-sort', 'dp-2-sort',
            'CbLogSearch' => [
                'inputValue' => ['date', 'created_at', 'unix_time_offset'],
                'val2' => ['date', 'created_at', 'unix_time_offset']
            ],
            'JlogSearch' => [
                'inputValue' => ['date', 'created_at', 'unix_time_offset'],
                'val2' => ['date', 'created_at', 'unix_time_offset']
            ],
            'page_size',
            'date_setting'
        ];

        return $entityHelper->makeParamsFromArray($requiredParams, $params);
    }

    /**
     * Redirects to $redirectAction if necessary
     * 
     * @param string|bool $redirectAction
     */
    public function makeRedirection($redirectAction)
    {
        $session = Yii::$app->session;
        $markerIsActive = $session->isActive;
        if (!$markerIsActive) {
            $session->open();
        }

        if (!Yii::$app->request->isAjax && !$redirectAction) {
            $redirectUrl = array_merge([$session->get('redirectAction')], Yii::$app->request->queryParams);

            return $this->redirect($redirectUrl);
        } elseif ($redirectAction) {
            $session->set('redirectAction', $redirectAction);
        }

        if (!$markerIsActive) {
            $session->close();
        }
    }

    /**
     * Gets dataProvider for non-encashment and non-log packets
     *
     * @param \frontend\models\JlogSearch $searchModel
     * @param array $params
     * 
     * @return ActiveDataProvider|null
     */
    public function getDataProvider($searchModel, $params)
    {
        if (!in_array($params['type_packet'], [Jlog::TYPE_PACKET_LOG, Jlog::TYPE_PACKET_ENCASHMENT])) {

            return $searchModel->search($params);
        }

        return null;
    }
}

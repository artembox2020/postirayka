<?php

namespace api\modules\v1\controllers;

use frontend\models\AddressBalanceHolder;
use yii\rest\ActiveController;

/**
 * Addresses Controller API
 *
 */
class AddressesController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Addresses';

    /**
     * Behaviors
     *
     * @return mixed
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['contentNegotiator']['formats']['application/json'] = \yii\web\Response::FORMAT_JSON;
//        $behaviors['authenticator'] = [
//            'class' => \yii\filters\auth\HttpBasicAuth::className(),
//        ];


        return $behaviors;
    }

    /**
     * Actions
     *
     * @return mixed
     */
    public function actions()
    {

        $actions = parent::actions();


        unset($actions['delete'], $actions['create'], $actions['update']);

        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;

    }

    /**
     * @return \yii\web\Response
     */
    public function actionSearch()
    {
        $result = array();
        $output = AddressBalanceHolder::find()->all();

        foreach ($output as $object) {
            foreach ($object as $key => $value) {
                if ($key == 'name' && $value != null) {
                    $result[] = $object->name;
                }
            }
        }

        return $this->asJson($result);
    }

    /**
     * @param string $company_ids
     * @return \yii\web\Response
     */
    public function actionList($company_ids = null)
    {
        $result = array();
        $query = AddressBalanceHolder::find();

        if (!empty($company_ids) && is_array($companyIds = explode(",", $company_ids))) {
            $query = $query->andWhere(['company_id' => $companyIds]);
        }

        $output = $query->all();

        foreach ($output as $object) {
            foreach ($object as $key => $value) {
                if ($key == 'name' && $value != null) {
                    $result[] = $object->name. ':' . $object->address;
                }
            }
        }

        return $this->asJson($result);
    }
}

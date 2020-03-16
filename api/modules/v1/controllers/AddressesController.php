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
     * @param int $company_id
     * @return \yii\web\Response
     */
    public function actionList($company_id = null)
    {
        $result = array();
        $query = AddressBalanceHolder::find();

        if (!empty($company_id)) {
            $query = $query->andWhere(['company_id' => $company_id]);
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

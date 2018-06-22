<?php

namespace frontend\controllers;

use common\models\User;
use frontend\services\custom\Debugger;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use frontend\models\OtherContactPerson;
use frontend\models\OtherContactPersonSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * OtherContactPersonController implements the CRUD actions for OtherContactPerson model.
 */
class OtherContactPersonController extends Controller
{
    const NINE_DIGIT = 9;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'is_deleted' => true,
                    'deleted_at' => time()
                ],
            ],
            TimestampBehavior::className(),
        ];
    }

    /**
     * Lists all OtherContactPerson models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OtherContactPersonSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single OtherContactPerson model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new OtherContactPerson model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new OtherContactPerson();

        $user = User::findOne(Yii::$app->user->id);
        $users = $user->company->users;
        $company = $user->company;
        $balanceHolder = $company->balanceHolders;

        if ($model->load(Yii::$app->request->post())) {
            $bol = OtherContactPerson::findAll(['balance_holder_id' => $model->balance_holder_id]);
            $res = count($bol);
            if ($res > self::NINE_DIGIT) {
                Yii::$app->session->setFlash(
                    'error',
                    Yii::t('frontend', 'Limit reached! This Balance Holder reached the limit *10 of contact persons'));
                return $this->redirect(['/other-contact-person/create']);
            } else {
                $model->save();
            }

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'balanceHolder' => $balanceHolder
        ]);
    }

    /**
     * Updates an existing OtherContactPerson model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $user = User::findOne(Yii::$app->user->id);
        $users = $user->company->users;
        $company = $user->company;
        $balanceHolder = $company->balanceHolders;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'balanceHolder' => $balanceHolder
        ]);
    }

    /**
     * Deletes an existing OtherContactPerson model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->softDelete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the OtherContactPerson model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OtherContactPerson the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OtherContactPerson::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('frontend', 'The requested page does not exist.'));
    }
}

<?php

namespace app\modules\payment\controllers;

use frontend\models\CustomerCards;
use frontend\models\Orders;
use frontend\models\Transactions;
use Ramsey\Uuid\Uuid;
use yii\web\Controller;
use yii\base\DynamicModel;
use LiqPay;
use Yii;
/**
 * Default controller for the `payment` module
 */
class DefaultController extends Controller
{
    private const SIGN_FAIL = 'Signature fail';
    private const DATA_FAIL = 'Data not found';
    private const SUCCESS = 'Payment success';
    private const FAIL = 'Payment fail';

    private const FALSE = 0;
    /**
     * @param $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if ($action->id === 'callback') {
            $this->enableCsrfValidation = false;
        }
        $this->layout = '@frontend/modules/account/views/layouts/customer';
        return parent::beforeAction($action); // TODO: Change the autogenerated stub
    }

    /**
     * Form for card refund
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $model = new DynamicModel(['card_no','amount']);

        $cards = CustomerCards::find()
        ->andWhere([
            'is_deleted' => self::FALSE,
            'status' => CustomerCards::STATUS_ACTIVE
        ])
        ->select('card_no')->asArray()->column();

        $model
            ->addRule(['card_no', 'amount'],  'required')
            ->addRule(['card_no'], 'in', ['range' => $cards])
            ->addRule(['amount'], 'number', ['min' => 0,'max' => 200]);

        if($model->load(Yii::$app->request->post()) && $model->validate()){
            $order = new Orders();
            $uuid = Uuid::uuid4();
            $order->order_uuid = $uuid->toString();
            $order->card_no = $model->card_no;
            $order->amount = $model->amount;
            $order->status = Orders::STATUS_PENDING;
            $order->save();
            $button = Yii::$app->mapBuilder->createPaymentButton($model, $order, env('SERVER_URL'), env('RESULT_URL'));
            return $this->render('confirm', ['payment_button' => $button]);
        }

        return $this->render('index', ['model' => $model]);
    }

    /**
     * ?????????????????? ?????????????? ?? ???????????????????? ?? ??????????????
     *
     */
    public function actionCallback()
    {
        Yii::$app->mapBuilder->paymentCallback();
    }

    /**
     * ?????????????? ?????????? ?????????????????? ??????????????
     * @return string
     */
    public function actionSuccess()
    {

        return $this->render('success');
    }

    /**
     * Makes payment history
     * 
     * @return string
     */
    public function actionHistory()
    {

        return $this->render('history');
    }
}

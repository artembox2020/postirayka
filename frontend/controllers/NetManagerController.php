<?php

namespace frontend\controllers;

use common\models\UserProfile;
use frontend\models\AddressBalanceHolder;
use frontend\models\BalanceHolder;
use frontend\models\Imei;
use frontend\models\WmMashine;
use Yii;
use common\models\User;
use backend\models\UserForm;
use backend\models\Company;
use yii\helpers\ArrayHelper;
use backend\services\mail\MailSender;
use frontend\services\custom\Debugger;
use yii\web\NotFoundHttpException;

/**
 * Class NetManagerController
 * @package frontend\controllers
 */
class NetManagerController extends \yii\web\Controller
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
        }

        return $this->render('index', [
            'model' => $model,
            'users' => $users,
            'balanceHolders' => $balanceHolders,
        ]);
    }

    /**
     * @return string
     */
    public function actionEmployees()
    {
        // $searchModel = new UserSearch();
        // $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // $dataProvider->sort = [
        //     'defaultOrder' => ['created_at' => SORT_DESC],
        // ];

        $user = User::findOne(Yii::$app->user->id);
        $profile = UserProfile::findOne($user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
        }

        return $this->render('employees', [
            'users' => $users,
            'profile' => $profile
        ]);
    }

    /**
     * Create Employee & set role & position & birthday
     *
     * @return void
     */
    public function actionCreateEmployee()
    {
        if (Yii::$app->user->can('create_employee')) {
            $model = new UserForm();
            $model->setScenario('create');

            if ($model->load(Yii::$app->request->post())) {
                $model->other = $model->password;
                $model->save();

                $manager = User::findOne(Yii::$app->user->id);
                $user = User::findOne(['email' => $model->email]);

                $user->company_id = $manager->company_id;
                $user->save();

                // send invite mail
                $password = $model->other;
                $sendMail = new MailSender();
                $company = Company::findOne(['id' => $manager->company_id]);
                $user = User::findOne(['email' => $model->email]);
                $sendMail->sendInviteToCompany($user, $company, $password);

                Yii::$app->session->setFlash('success', Yii::t('backend', 'Send ' . $model->username . ' invite'));

                return $this->redirect(['users']);
            }

            $roles = ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'name');

            unset($roles[array_search('administrator', $roles)]);
            unset($roles[array_search('manager', $roles)]);
            unset($roles[array_search('user', $roles)]);

            foreach ($roles as $key => $role) {
                $roles[$key] = Yii::t('backend', $role);
            }

            $model->status = 1;
            return $this->render('create', [
                'model' => $model,
                'roles' => $roles
            ]);

        }

        return $this->render('/denied/access-denied', [
            $this->accessDenied()
        ]);
    }

    /**
     *  view one employee
     */
    public function actionViewEmployee()
    {
        if (Yii::$app->request->post()) {
            $model = User::findOne(['id' => Yii::$app->request->post('id')]);

            return $this->render('view-employee', [
                'model' => $model
            ]);
        }
    }

    /**
     *  edit employee
     */
    public function actionEditEmployee($id)
    {
        $user = new UserForm();
        $user->setModel($this->findModel($id));
        $profile = UserProfile::findOne($id);

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {
            $isValid = $user->validate(false);
            $isValid = $profile->validate(false) && $isValid;
            if ($isValid) {
                $user->save(false);
                $profile->save(false);

                return $this->redirect(['/net-manager/employees']);
            }
        }

        return $this->render('create', [
            'user' => $user,
            'profile' => $profile,
            'roles' => ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'name'),
        ]);
    }

    /**
     *  method check access with role
     */
    private function accessDenied()
    {
        return Yii::$app->session->setFlash(
            'error',
            Yii::t('frontend', 'Access denied')
        );
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionBalanceHolders()
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('balance-holders', [
            'model' => $model,
            'users' => $users,
            'balanceHolders' => $balanceHolders,
        ]);
    }

    /**
     * @param $id
     * @return string|\yii\web\Response
     */
    public function actionViewBalanceHolder($id)
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
            $model = $this->findBalanceHolder($balanceHolders, $id);
        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('view-balance-holder', [
            'model' => $model,
        ]);
    }

    /**
     * @param $array
     * @param $id
     * @return mixed
     */
    private function findBalanceHolder($array, $id)
    {
        foreach ($array as $value) {
            if ($value->id == $id) {
                return $value;
            }
        }
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionAddresses()
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('addresses/addresses', [
            'model' => $model,
            'users' => $users,
            'balanceHolders' => $balanceHolders,
        ]);
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionWashpay()
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $company = $user->company;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('washpay/washpay', [
            'company' => $company,
            'model' => $model,
            'users' => $users,
            'balanceHolders' => $balanceHolders,
        ]);
    }

    /**
     * @param $id
     * @return string|\yii\web\Response
     */
    public function actionWashpayView($id)
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
            $imei = Imei::findOne($id);
            $address = AddressBalanceHolder::findOne($imei->address_id);
            $balanceHolder = $address->balanceHolder;
        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('washpay/washpay-view', [
            'model' => $model,
            'users' => $users,
            'balanceHolders' => $balanceHolders,
            'imei' => $imei,
            'address' => $address,
            'balanceHolder' => $balanceHolder
        ]);
    }

    public function actionWashpayUpdate($id)
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $company = $user->company;
            $balanceHolders = $company->balanceHolders;
            foreach ($company->balanceHolders as $balanceHolder) {
                foreach ($balanceHolder->addressBalanceHolders as $addresses) {
                    $tempadd[] = $addresses;
                    foreach ($addresses->imeis as $value) {
                        if ($value->id == $id) {
                            $imei = $value;
                        }
                    }
                }
            }
                $address = AddressBalanceHolder::findOne($imei->address_id);
                $balanceHolder = $address->balanceHolder;

            if ($imei->load(Yii::$app->request->post())) {
                $imei->save();
                return $this->redirect('washpay');
            }

        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('washpay/washpay-update' , [
            'company' => $company,
            'imei' => $imei,
            'address' => $address,
            'addresses' => $tempadd,
            'balanceHolder' => $balanceHolder,
            'balanceHolders' => $balanceHolders
        ]);
    }

    public function actionWashpayCreate()
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $company = $user->company;
            $balanceHolders = $company->balanceHolders;
            foreach ($company->balanceHolders as $balanceHolder) {
                foreach ($balanceHolder->addressBalanceHolders as $addresses) {
                    $tempadd[] = $addresses;
                    }
                }
            $imei = new Imei();
            $address = new AddressBalanceHolder();

            if ($imei->load(Yii::$app->request->post())) {
                $imei->company_id = $company->id;
                $imei->save();
                return $this->redirect('washpay');
            }
        }

        return $this->render('washpay/washpay-create', [
            'company' => $company,
            'imei' => $imei,
            'address' => $address,
            'addresses' => $tempadd,
            'balanceHolders' => $balanceHolders
        ]);
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionWmMachine()
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('wm-machine/wm-machine', [
            'model' => $model,
            'users' => $users,
            'balanceHolders' => $balanceHolders,
        ]);
    }

    public function actionWmMachineView($number_device)
    {
        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $users = $user->company->users;
            $model = $user->company;
            $balanceHolders = $model->balanceHolders;
            $wm_machine = WmMashine::findOne($number_device);
//            $imei = WmMashine::findOne($id);
//            $address = AddressBalanceHolder::findOne($imei->id);
//            $balanceHolder = BalanceHolder::findOne($address->balance_holder_id);
        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('wm-machine/wm-machine-view', [
            'wm_machine' => $wm_machine,
//            'imei' => $imei,
//            'address' => $address,
//            'balanceHolder' => $balanceHolder
        ]);
    }

    public function actionFixedAssets()
    {
        $assets = Imei::getStatusOff();

        return $this->render('fixed-assets/index', [
            'assets' => $assets
        ]);
    }

    public function actionFixedAssetsUpdate($id)
    {
        $imei = Imei::find()->where(['id' => $id])->andWhere(['status' => Imei::STATUS_OFF])->one();

        $user = User::findOne(Yii::$app->user->id);

        if (!empty($user->company)) {
            $company = $user->company;
            $balanceHolders = $company->balanceHolders;
            foreach ($company->balanceHolders as $balanceHolder) {
                foreach ($balanceHolder->addressBalanceHolders as $addresses) {
                    $tempadd[] = $addresses;
                }
            }
            $address = AddressBalanceHolder::find(['id' => $imei->address_id])->one();
//            Debugger::dd($address);
            $balanceHolder = $address->balanceHolder;

            if ($imei->load(Yii::$app->request->post())) {
                $imei->save();
                return $this->redirect('washpay');
            }

        } else {

            return $this->redirect('account/sign-in/login');
        }

        return $this->render('fixed-assets/update', [
//            'assets' => $assets,
            'company' => $company,
            'imei' => $imei,
            'address' => $address,
            'addresses' => $tempadd,
            'balanceHolders' => $balanceHolders,
            'balanceHolder' => $balanceHolder
        ]);
    }

    public function actionWmMachineCreate()
    {

        return $this->render('wm-machine/wm-machine-create', [

    ]);
    }
}

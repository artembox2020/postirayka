<?php

namespace frontend\modules\account\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use common\models\LoginForm;
use common\models\User;
use frontend\modules\account\models\PasswordResetRequestForm;
use frontend\modules\account\models\ResetPasswordForm;
use frontend\modules\account\models\SignupForm;

/**
 * Class UserController.
 */
class SignInController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'signup', 'sign-in-via-google', 'sign-in-via-fb', 'confirm-email', 'request-password-reset', 'reset-password', 'is-user-active'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['login', 'signup', 'sign-in-via-google', 'sign-in-via-fb', 'confirm-email', 'request-password-reset', 'reset-password'],
                        'allow' => false,
                        'roles' => ['@'],
                        'denyCallback' => function () {

                            if (Yii::$app->user->can('customer')) {

                                return Yii::$app->controller->redirect(['customer/index', 'id' => Yii::$app->user->id]);
                            } else {

                                return Yii::$app->controller->redirect(['/site/index']);
                            }
                        },
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $model = Yii::$app->user->identity;
            $model->ip = Yii::$app->request->userIP;
            $model->save();

            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', ['model' => $model]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        if (Yii::$app->keyStorage->get('frontend.registration')) {
            $model = new SignupForm();
            if ($model->load(Yii::$app->request->post())) {
                if ($user = $model->signup()) {
                    if (Yii::$app->keyStorage->get('frontend.email-confirm')) {
                        // подтверждение email
                        if ($model->sendEmail()) {
                            Yii::$app->session->setFlash('success', Yii::t('frontend', 'Your account has been successfully created. Check your email for further instructions.'));
                        } else {
                            Yii::$app->session->setFlash('error', Yii::t('frontend', 'There was an error sending your message.'));
                        }

                        return $this->refresh();
                    } else {
                        // автологин
                        if (Yii::$app->getUser()->login($user)) {
                            return $this->goHome();
                        }
                    }
                }
            }

            return $this->render('signup', ['model' => $model]);
        } else {
            Yii::$app->session->setFlash('info', Yii::t('frontend', 'Registration is disabled.'));

            return $this->goHome();
        }
    }

    /**
     * @inheritdoc
     */
    public function actionConfirmEmail($id, $token)
    {
        $user = User::find()->where([
            'id' => $id,
            'access_token' => $token,
            'status'=> User::STATUS_INACTIVE,
        ])->one();

        if ($user) {
            $user->status = User::STATUS_ACTIVE;
            $user->removeAccessToken();
            $user->save();
            Yii::$app->session->setFlash('success', Yii::t('frontend', 'Your account has been successfully activated.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('frontend', 'Error activate your account.'));
        }

        return $this->goHome();
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('password-reset-success', Yii::t('frontend', 'Check your email for further instructions.'));
            } else {
                Yii::$app->session->setFlash('password-reset-error', Yii::t('frontend', 'Sorry, we are unable to reset password for the provided email address.'));
            }

            return $this->goHome();
        }

        return $this->render('requestPasswordResetToken', ['model' => $model]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
         //$model = new ResetPasswordForm($token);
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', Yii::t('frontend', 'New password saved.'));

            return $this->goHome();
        }

        return $this->render('resetPassword', ['model' => $model]);
    }

    /**
     * Signs In/Up via google
     *
     * @return string
     */
    public function actionSignInViaGoogle()
    {
        $userData = Yii::$app->googleOAuth->findUserData();
        $model = new LoginForm();

        if ($model->loginViaGoogle($userData)) {
            $model = Yii::$app->user->identity;
            $model->ip = Yii::$app->request->userIP;
            $model->save();

            return "<script>self.close();</script>";
        } else {
            $model->password = '';

            return $this->render('login', ['model' => $model]);
        }
    }

    /**
     * Signs In/Up via facebook
     *
     * @return string
     */
    public function actionSignInViaFb()
    {
        $userData = Yii::$app->fbOAuth->findUserData();

        $model = new LoginForm();

        if ($model->loginViaFb($userData)) {
            $model = Yii::$app->user->identity;
            $model->ip = Yii::$app->request->userIP;
            $model->save();

            return "<script>self.close();</script>";
        } else {
            $model->password = '';

            return $this->render('login', ['model' => $model]);
        }
    }

    /**
     * Check whether user is logged in
     *
     * @return string
     */
    public function actionIsUserLogged()
    {
        $result = empty(Yii::$app->user->id) ? 0 : 1;

        return json_encode(['result' => $result]);
    }
}
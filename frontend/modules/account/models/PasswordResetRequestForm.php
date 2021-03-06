<?php

namespace frontend\modules\account\models;

use Yii;
use yii\base\Model;
use common\models\User;
use yii\helpers\Html;

/**
 * Password reset request form.
 */
class PasswordResetRequestForm extends Model
{
    public $email;
    public $verifyCode;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'exist', 'targetClass' => User::className(), 'filter' => ['status' => User::STATUS_ACTIVE]],

            // verifyCode needs to be entered correctly
            ['verifyCode', 'captcha'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'email' => Yii::t('frontend', 'Email'),
            'verifyCode' => Yii::t('frontend', 'Verification code'),
        ];
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return bool whether the email was send
     */
    public function sendEmail()
    {
        /* @var $user User */
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'email' => $this->email,
        ]);

        if ($user) {
            $user->generateAccessToken();
        } else {
            return false;
        }

        if (!$user->save()) {
            return false;
        }

        $link = \yii\helpers\Url::to('/account/sign-in/reset-password', true);
        $link .= "?token=".$user->access_token;
        $link = Yii::t('frontend', 'Password reset link').': '.Html::a($link, $link, ['target' => '_blank']);
        
        return mail($this->email, Yii::t('frontend', 'Password reset for {name}', ['name' => Yii::$app->name]), $link, ["Content-Type" => "text/html"]);

        return Yii::$app->mailer->compose('passwordReset', ['user' => $user])
            ->setFrom([Yii::$app->params['robotEmail'] => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject(Yii::t('frontend', 'Password reset for {name}', ['name' => Yii::$app->name]))
            ->setHtmlBody($link)
            ->send();
    }
}

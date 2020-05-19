<?php

namespace frontend\controllers;

use common\models\User;
use frontend\models\ImeiDataSearch;
use frontend\models\ImeiAction;
use frontend\models\WmMashineDataSearch;
use frontend\models\WmMashine;
use frontend\models\Jlog;
use frontend\services\globals\Entity;
use frontend\services\globals\EntityHelper;
use Yii;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\helpers\ArrayHelper;

class Controller extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (Yii::$app->user->can('customer')) {
            $this->layout = '@frontend/modules/account/views/layouts/customer';
        }

        return parent::beforeAction($action);
    }

    /**
     * Gets path depending on layout
     * 
     * @param string $view
     * @param bool $ignoreLayout
     * 
     * @return string
     */
    public function getPath($view, $ignoreLayout = false)
    {
        $layout = Yii::$app->layout;
        $layoutParts = explode("-", $layout);

        if ($ignoreLayout || count($layoutParts) < 2) {

            return $view;
        }

        return $view.'-'.$layoutParts[1];
    }

    /**
     * Gets model instance according to filter data
     * 
     * @param array $data
     * @param instance $instance
     * @param bool $hasUserId
     * 
     * @return instance
     * @throws \yii\web\NotFoundHttpException
     */
    public function getModel($data, $instance, $hasUserId = true)
    {
        $entity = new Entity();

        if (Yii::$app->user->can(User::ROLE_CUSTOMER)) {

            if ($hasUserId) {
                $data['user_id'] = Yii::$app->user->id;
            }

        } else if (!Yii::$app->user->can('super_administrator')) {
            $data['company_id'] = $entity->getCompanyId();
        }

        $model = $instance::find()->andWhere($data)->limit(1)->one();

        if (!$model) {
            throw new \yii\web\NotFoundHttpException(Yii::t('common','Entity not found'));
        }

        return $model;
    }

    /**
     * Gets user roles available
     * 
     * @return array
     */
    public function getRoles()
    {
        $roles = ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'name');

        $now = Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId());

        unset($roles[array_search('super_administrator', $roles)]);
        unset($roles[array_search('user', $roles)]);

        foreach ($roles as $key => $role) {
            $roles[$key] = Yii::t('backend', $role);
        }

        return $roles;
    }

    /**
     * Sets user roles possible
     * 
     * @return array
     */
    public function setRoles()
    {
        if (!array_key_exists(User::ROLE_ADMINISTRATOR, Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId()))) {
            $array = Yii::$app->authManager->getRoles();
            unset($array[self::ADMINISTRATOR]);
        } else {
            $array = Yii::$app->authManager->getRoles();
        }

        $roles = ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'name');

        $now = Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId());

        //unset($roles[array_search('super_administrator', $roles)]);
        unset($roles[array_search('user', $roles)]);

        foreach ($roles as $key => $role) {
            $roles[$key] = Yii::t('backend', $role);
        }

        if (array_key_exists(User::ROLE_CUSTOMER, Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId()))) {
            $key = array_search(User::ROLE_CUSTOMER, $roles);
            $roles = array_intersect_key($roles, [$key => $roles[$key]]);
        }

        return $roles;
    }

    /**
     * Performs card operations by card post data (block/unblock + refill)
     
     * @param array $post
     * @param \frontend\models\CustomerCards $card
     * 
     * @return string|bool
     */
    public function updateMapData($post, $card)
    {
        $model = Yii::$app->mapBuilder->getUpdateMapDataModelFromPost($post, $card);

        Yii::$app->session->set(
            'update-map-data-status',
            Yii::$app->mapBuilder->getFlashMessageByStatus(
                $model ? $model->status : Yii::$app->mapBuilder::STATUS_ERROR
            )
        );

        // in case of need payment confirmation redirect to liqpay payment page
        if ($model->status == Yii::$app->mapBuilder::STATUS_PENDING_CONFIRMATION) {

            return $this->render(
                '@frontend/views/map/confirm_payment',
                [
                    'payment_button' => Yii::$app->mapBuilder->createOrderAndPaymentButton(
                        $model, env('SERVER_URL'), env('FRONTEND_URL').Yii::$app->request->url
                    )
                ]
            );
        }

        return false;
    }

    /*public function render($view, $params = [])
    {
        $view = $this->getPath($view);

        return parent::render($view, $params);
    }
    
    public function renderPartial($view, $params = [])
    {
        $view = $this->getPath($view);

        return parent::renderPartial($view, $params);
    }*/

    /** retranslates packets to another server **/
    public function retranslatePackage(string $url, string $p): void
    {
        $url = 'http://167.86.98.115:6080'.$url.'?p='.$p;

        ob_start();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getallheaders());
        curl_exec($ch);

        curl_close($ch);

        ob_get_clean();
    }

    /**
     * Puts down log into the file '/log/packets.dump'
     * @param string $packet
     */
    public function putLog(string $packet, string $url): void
    {
        $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/log/packets_1.x.dump', 'a+');
        fwrite($fp, $url."?p=".$packet."\n");
        fclose($fp);
    }
}

<?php

namespace frontend\components;

use Yii;
use yii\base\Component;
use yii\helpers\Html;
use frontend\services\globals\Entity;
use yii\helpers\ArrayHelper;

/**
 * Class HeaderBuilder
 * @package frontend\components
 */
class HeaderBuilder extends Component {

    /**
     * Builds header depending on layout
     * 
     * @param string $layout
     * 
     * @return string
     */
    public function makeHeader($layout)
    {
        $brand = Yii::$app->name;
        $brand_url = '/';
        $company = null;
        $entity = new Entity();
        $userMenuItems = [];

        if (Yii::$app->user->isGuest) {
            $menuItems[] = ['label' => Yii::t('frontend', 'Login'), 'url' => ['/account/sign-in/login']];
        } else {
            $user = Yii::$app->user;

            if (!empty($user->company)) {
                $company = $user->company;
                $brand = $company->name;
            }

            $role = ArrayHelper::map(
                Yii::$app->authManager->getRolesByUser($user->id), 
                'description', 'description'
            );
            foreach ($role as $key => $val) {
                $role_description = $key;
            }

            $role_name = $user->identity->username;
            $userRole = \Yii::$app->authManager->getRolesByUser($user->id);

            if (empty($role_description)) {
                $userRole = '';
            } else {
                $userRole = $role_description;
            }

            $monitoring_url = null;

            if ($user->can('viewTechData') ) {
                $monitoring_url = '/monitoring/technical';
            }

            if ($user->can('viewFinData') ) {
                $monitoring_url = '/monitoring/financial';
            }

            if ($user->can('viewTechData') && $user->can('viewFinData') ) {
                $monitoring_url = '/monitoring';
            }

            if (empty($role_name)) {
                $role_name = Yii::t('frontend', 'role not defined');
            }

            $menuItems = [
                [
                    'label' => Yii::t('nav-items', 'Net'),
                    'url' => ['/net-manager'],
                    'visible' => $company && $user->can('viewCompanyData'),
                ],
                [
                    'label' => Yii::t('frontend', 'Monitoring'),
                    'url' => [$monitoring_url],
                    'visible' => $company && $monitoring_url,
                ],
                [
                    'label' => Yii::t('nav-items', 'Encashment'),
                    'url' => ['/encashment-journal/index'],
                    'visible' => $company && $user->can('viewFinData'),
                ],
                [
                    'label' => Yii::t('frontend', 'Dlogs'),
                    'url' => ['/journal/index?sort=-date'],
                    'visible' => $company && $user->can('viewTechData'),
                ],
            ];

            $userMenuItems =  [
                [
                    'label' => Yii::t('frontend', 'Users'),
                    'url' => ['/net-manager/employees'],
                    'visible' => $user->can('manager'),
                ],
                [
                    'label' => Yii::t('frontend', 'Settings'),
                    'url' => ['/account/default/settings'],
                    'visible' => $user->can('manager'),
                ],
                [
                    'label' => Yii::t('frontend', 'Company'),
                    'url' => ['/account/default/tt'],
                    'visible' => $company && $user->can('administrator'),
                ],
                [
                    'label' => Yii::t('frontend', 'Logout'),
                    'url' => ['/account/sign-in/logout'],
                ]
            ];
        }

        return Yii::$app->view->render(
            "@frontend".'/views/layouts/'.$layout.'/header',
            [
                'brand_url' => env('FRONTEND_URL'),
                'menuItems' => $menuItems,
                'userMenuItems' => $userMenuItems
            ]
        );
    }

    /**
     * Gets table responsive class for journal events
     * 
     * @return string
     */
    public function getJournalResponsiveClass()
    {

        return Yii::$app->layout == 'main-new' ? 'table-responsive' : '';
    }
    
    public function getMapShapterTabActivity($action, $tabId)
    {

        return in_array($action, $tabId) ? 'active' : '';
    }

    /**
     * Builds customer header depending on layout
     * 
     * @param string $layout
     * 
     * @return string
     */
    public function makeCustomerHeader($layout)
    {
        $brand = Yii::$app->name;
        $brand_url = env('FRONTEND_URL').'/account/customer/index?id='.Yii::$app->user->id;
        $entity = new Entity();

        $role = ArrayHelper::map(Yii::$app->authManager->getRolesByUser(Yii::$app->user->id), 'description', 'description');
        foreach ($role as $key => $val) {
            $role_description = $key;
        }

        $role_name = Yii::$app->user->identity->username;
        $userRole = \Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);

        if (empty($role_description)) {
            $userRole = '';
        } else {
            $userRole = $role_description;
        }

        $menuItems = [];

        if (empty($role_name)) {
            $role_name = Yii::t('frontend', 'role not defined');
        }

        $menuItems = [
            [
                'label' => 'add-card'
            ],
            [
                'label' => Yii::t('payment', 'Top Up Card'),
                'url' => ['/payment/default/index']
            ]
        ];

        $userMenuItems = [
                    [
                        'label' => Yii::t('frontend', 'Logout'),
                        'url' => ['/account/sign-in/logout'],
                        'linkOptions' => ['data-method' => 'post'],
                    ],
        ];

        return Yii::$app->view->render(
            "@frontend".'/views/layouts/'.$layout.'/header',
            [
                'brand_url' => $brand_url,
                'menuItems' => $menuItems,
                'userMenuItems' => $userMenuItems
            ]
        );
    }
}

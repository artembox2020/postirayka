<?php

use backend\models\Log;
use backend\widgets\Menu;

/* @var $this \yii\web\View */
?>
<aside class="main-sidebar">
    <section class="sidebar">
        <?= Menu::widget([
            'options' => ['class' => 'sidebar-menu'],
            'items' => [
                [
                    'label' => Yii::t('backend', 'Main'),
                    'options' => ['class' => 'header'],
                ],
                [
                    'label' => Yii::t('backend', 'Menu'),
                    'url' => ['/menu/index'],
                    'icon' => '<i class="fa fa-sitemap"></i>',
                ],
//                [
//                    'label' => Yii::t('backend', 'Tags'),
//                    'url' => ['/tag/index'],
//                    'icon' => '<i class="fa fa-tags"></i>',
//                ],
                [
                    'label' => Yii::t('backend', 'Content'),
                    'url' => '#',
                    'icon' => '<i class="fa fa-edit"></i>',
                    'options' => ['class' => 'treeview'],
                    'items' => [
                        ['label' => Yii::t('backend', 'Static pages'), 'url' => ['/page/index'], 'icon' => '<i class="fa fa-angle-double-right"></i>'],
                    ],
                ],
                [
                    'label' => Yii::t('backend', 'System'),
                    'options' => ['class' => 'header'],
                ],
                [
                    'label' => Yii::t('backend', 'Users'),
                    'url' => ['/user/index'],
                    'icon' => '<i class="fa fa-users"></i>',
                    'visible' => Yii::$app->user->can('super_administrator'),
                ],
                [
                    'label' => Yii::t('backend', 'Cards'),
                    'url' => ['/card/index'],
                    'icon' => '<i class="fa fa-credit-card"></i>',
                    'visible' => Yii::$app->user->can('super_administrator'),
                ],
                [
                    'label' => Yii::t('backend', 'Companies'),
                    'url' => ['/company/index'],
                    'icon' => '<i class="fa fa-copyright"></i>',
                    'visible' => Yii::$app->user->can('super_administrator'),
                ],
                [
                    'label' => Yii::t('backend', 'Other'),
                    'options' => ['class' => 'header'],
                ],
                [
                    'label' => Yii::t('backend', 'Basket'),
                    'url' => ['/basket/index'],
                    'icon' => '<i class="fa fa-trash"></i>',
                    'visible' => Yii::$app->user->can('super_administrator'),
                    'items' => [
                        [
                            'label' => Yii::t('backend', 'Companies'),
                            'url' => ['/basket/company'],
                            'icon' => '<i class="fa fa-angle-double-right"></i>',
                            'visible' => Yii::$app->user->can('super_administrator'),
                        ],
                        [
                            'label' => Yii::t('backend', 'Users'),
                            'url' => ['/basket/user'],
                            'icon' => '<i class="fa fa-angle-double-right"></i>',
                            'visible' => Yii::$app->user->can('super_administrator'),
                        ],
                    ],
                ],
                [
                    'label' => Yii::t('backend', 'Other'),
                    'url' => '#',
                    'icon' => '<i class="fa fa-terminal"></i>',
                    'options' => ['class' => 'treeview'],
                    'items' => [
                        
                        [
                            'label' => Yii::t('backend', 'System information'),
                            'url' => ['/phpsysinfo/default/index'],
                            'icon' => '<i class="fa fa-angle-double-right"></i>',
                            'visible' => Yii::$app->user->can('super_administrator'),
                        ],
                        [
                            'label' => Yii::t('backend', 'Cache'),
                            'url' => ['/service/cache'],
                            'icon' => '<i class="fa fa-angle-double-right"></i>',
                            'visible' => Yii::$app->user->can('super_administrator'),
                        ],
                        [
                            'label' => Yii::t('backend', 'Clear assets'),
                            'url' => ['/service/clear-assets'],
                            'icon' => '<i class="fa fa-angle-double-right"></i>',
                            'visible' => Yii::$app->user->can('super_administrator'),
                        ],
                        [
                            'label' => Yii::t('backend', 'Logs'),
                            'url' => ['/log/index'],
                            'icon' => '<i class="fa fa-angle-double-right"></i>',
                            'badge' => Log::find()->count(),
                            'badgeOptions' => ['class' => 'label-danger'],
                        ],
                    ],
                ],
            ],
        ]) ?>
    </section>
</aside>

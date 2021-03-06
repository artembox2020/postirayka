<?php

use frontend\components\responsive\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\components\responsive\DetailView;
use frontend\services\custom\Debugger;
use frontend\models\Imei;

/* @var $this yii\web\View */
/* @var $model frontend\models\WmMashine */
/* @var $users common\models\User */
/* @var $balanceHolders */
/* @var $addresses */
/* @var $wm_machine */
/* @var $technical_work frontend\models\TechnicalWork */
?>
<?php $menu = [];
//Debugger::d($model->date_build);

?>


<b>
    <?= $this->render('/net-manager/_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b><br><br>
<h3><?= Yii::t('frontend', 'Wash machine card'); ?></h3>
<?= DetailView::widget([
        'model'=> $model,
        'attributes' => [
            [
                'label' => Yii::t('frontend', 'ID device'),
                'value' => $model->id,
            ],
            [
                'label' => Yii::t('frontend', 'Device photo gallery'),
                'format' => 'raw',
                'value' => $model->makePhotoGallery(),
            ],
            [
                'label' => Yii::t('frontend', 'Device number'),
                'value' => $model->number_device,
            ],
            [
                'label' => Yii::t('frontend', 'Inventory number'),
                'value' => $model->inventory_number,
            ],
                [
                    'label' => Yii::t('frontend', 'Serial number'),
                    'value' => $model->serial_number,
                ],
                [
                    'label' => Yii::t('frontend', 'Model'),
                    'value' => $model->model,
                ],
            'brand',
            [
                'label' => Yii::t('frontend', 'Date build'),
                'value' => $model->date_build,
                'format' => ['date', 'php:d.m.Y']
            ],
            [
                'label' => Yii::t('frontend', 'Date Purchase'),
                'value' => $model->date_purchase,
                'format' => ['date', 'php:d.m.Y']
            ],
            [
                'label' => Yii::t('frontend', 'Date connection to monitoring'),
                'value' => $model->date_connection_monitoring,
                'format' => ['date', 'php:d.m.Y']
            ],
            [
                'label' => Yii::t('frontend', 'Address Install'),
                'value' => !empty($address = $model->address) ? $address->address : null,
            ],
            [
                'label' => Yii::t('frontend', 'Last ping'),
                'value' => function($model) {
                return date('[H:i:s] d.m.Y', $model->ping);
            },
            ],
    ],
]);?>
<?= Html::a(Yii::t('frontend', 'Update'), ['/net-manager/wm-machine-update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>

<?= Html::a(Yii::t('frontend', 'Delete'), ['wm-mashine/delete', 'id' => $model->id], [
    'class' => 'btn btn-danger',
    'data' => [
        'confirm' => Yii::t('frontend', 'Are you sure you want to delete this item?'),
        'method' => 'post',
    ],
]) ?>
<br><br>
<div>
    <b><u>???????????????? ????????</u></b><br>
    <?= GridView::widget([
    'dataProvider'=> $provider,
    'gridClass' => GridView::OPTIONS_DEFAULT_GRID_CLASS.' grid-filter-hide',
        'columns' => [
            ['attribute' => 'inventory_number',
                'label' => Yii::t('frontend', 'Inventory number'),
            ],
            ['attribute' => 'address.address',
                'label' => Yii::t('frontend', 'Address Install'),
            ],
            [
                'label' => Yii::t('wash_machine/technical_work', 'Technical work'),
                'value' => function ($provider) {
                    return $provider->getState();
                },
            ],
            ['attribute' => 'Date',
                'label' => Yii::t('frontend', 'Date'),
                'value' => function($provider) {
                    return date('d/m/Y', $provider->created_at);
                },
            ],
//            [
//                'class' => 'yii\grid\ActionColumn',
//                'header' => Yii::t('common', 'Actions'),
//                'template' => '{delete}',
//                'buttons' => [
//                    'delete' => function($url, $provider) {
//                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', ['technical_work_delete', 'id' => $provider->id],
//                            [
//                                'class' => '',
//                                'data' => [
//                                    'confirm' => Yii::t('common', 'Delete Confirmation'),
//                                    'method' => 'post',
//                                ],
//                            ]);
//                    }
//
//                ],
//            ]

    ]
    ]);?>
    <b><u>?????????????????? ????????</u></b><br>
    ....
</div>

<?php
    if (Imei::findOne($model->imei_id)) {

        echo Yii::$app->runAction(
            '/journal/index-by-mashine',
            ['id' => $model->id, 'redirectAction' => '/net-manager/wm-machine-view']
        );
    }
?>

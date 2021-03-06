<?php

use yii\helpers\Html;
use frontend\components\responsive\GridView;
use yii\widgets\Pjax;
use frontend\models\WmMashine;
use frontend\services\globals\EntityHelper;

/* @var $this yii\web\View */
/* @var $dataProviderWmMashine yii\data\ActiveDataProvider */
/* @var $dataProviderGdMashine yii\data\ActiveDataProvider */
/* @var $searchModel frontend\models\ImeiDataSearch */
?>
<div>
    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProviderWmMashine,
        'filterModel' => $searchModel,
        'summary' => '',
        'columns' => [
            [
                'label' => Yii::t('frontend', 'Device'),
                'format' => 'raw',
                'attribute' => 'id',
                'value' => function($model)
                {

                    return (
                        Html::a(
                            Yii::t('frontend', $model->type_mashine).$model->number_device,
                            '/net-manager/'.strtolower($model->type_mashine).'-machine-view?id='.$model->id,
                            ['target' => '_blank']
                        ).
                        EntityHelper::makePopupWindow(
                            [],
                            Yii::t('frontend', 'Device'),
                            'top: -5px',
                            'height: 5px'
                        ).
                        '<input type="hidden" class="device-id" value="'.$model->id.'" />'
                    );
                },
                'contentOptions' => ['class' => 'cell-device'],
            ],
            [
                'attribute' => 'bill_cash',
                'format' => 'raw',
                'header' => \frontend\services\globals\EntityHelper::makePopupWindow(
                    [
                        '/static/img/monitoring/money_in_banknotes.png',
                    ],
                    Yii::t('frontend', 'On the bill of WM')
                ),
                'contentOptions' => ['class' => 'bill-cash'],
                'value' => function($model) use ($searchModel)
                {

                    return (
                        \Yii::$app->formatter->asDecimal($model->bill_cash, 0).
                        EntityHelper::makePopupWindow(
                            [],
                            Yii::t('frontend', 'On the bill of WM'),
                            'top: -5px',
                            'height: 5px'
                        )
                    );
                }
            ],
            [
                'attribute' => 'level_signal',
                'format' => 'raw',
                'value' => function($model) use ($searchModel)
                {

                    return (
                        $model->getLevelSignal().
                        EntityHelper::makePopupWindow(
                            [],
                            $searchModel->attributeLabels()['level_signal'],
                            'top: -5px',
                            'height: 5px'
                        )
                    );
                },
                'header' => \frontend\services\globals\EntityHelper::makePopupWindow(
                    [
                        '/static/img/monitoring/signal_station2.png',
                    ],
                    $searchModel->attributeLabels()['level_signal']
                ),
                'contentOptions' => ['class' => 'level-signal']
            ],    
            [
                'attribute' => 'current_status',
                'format' => 'raw',
                'value' => function($model)
                {
                    return Html::a(
                        $model->getStateView(),
                        '/net-manager/'.strtolower($model->type_mashine).'-machine-view?id='.$model->id,
                        ['target' => '_blank']
                    );
                },
                'contentOptions' => ['class' => 'current-status']
            ]
        ],
    ]); ?>

    <?php Pjax::end(); ?>
</div>
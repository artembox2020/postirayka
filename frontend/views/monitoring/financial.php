<?php

use yii\helpers\Html;
use yii\grid\GridView;
use frontend\models\ImeiDataSearch;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel  frontend\models\ImeiDataSearch*/
/* @var $monitoringController frontend\controllers\MonitoringController */
?>
<h1><?= Yii::t('frontend', 'Monitoring') ?></h1>
<div class="monitoring">
    <div class="form-group monitoring-shapter">
        <label for="type_packet"><?= Yii::t('frontend', 'Monitoring Shapter') ?></label>
        <?= Html::dropDownList(
                'monitoring_shapter', 
                'all',
                $monitoringShapters,
                [
                    'class' => 'form-control'
                ]
            );
        ?>
    </div>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => false,
        'summary' => '',
        'tableOptions' => [
            'class' => 'table table-striped table-bordered table-financial'
        ],
        'columns' => [
            [
                'label' => $monitoringShapters['common'],
                'format' => 'raw',
                'value' => function($model) use($monitoringController, $searchModel)
                {

                    return $monitoringController->renderCommonDataByImeiId($model->id, $searchModel);
                },
                'contentOptions' => ['class' => 'common all'],
                'headerOptions' => ['class' => 'common all']
            ],
            [
                'label' => $monitoringShapters['financial'],
                'format' => 'raw',
                'value' => function($model) use($monitoringController, $searchModel)
                {

                    return $monitoringController->renderFinancialRemoteConnectionDataByImeiId($model->id, $searchModel);
                },
                'contentOptions' => ['class' => 'financial all'],
                'headerOptions' => ['class' => 'financial all']
            ],
        ],
    ]); ?>
</div>
<?php echo $script; ?>

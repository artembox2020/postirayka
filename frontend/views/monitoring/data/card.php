<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use frontend\components\responsive\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel frontend\models\ImeiDataSearch */
?>
<div>
    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'summary' => '',
        'columns' => [
            [
                'attribute' => 'imei',
                'label' => Yii::t('frontend', 'Modem Card'),
                'format' => 'raw',
                'headerOptions' => ['class' => 'modem-card'],
                'value' => function($model)
                {
                    
                    return $model->getModemCard();
                }
            ],
        ],
    ]); ?>
    <?php Pjax::end(); ?>
</div>
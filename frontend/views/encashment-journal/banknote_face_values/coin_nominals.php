<?php

use yii\grid\GridView;

/* @var $dataProvider yii\data\ArrayDataProvider */

?>

<?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'options' => [
            'class' => 'nominals-grid coin-nominals-grid'
        ],
        'columns' => [
            [
                'attribute' => 'nominal',
                'label' => Yii::t('logs', 'Nominal')
            ],
            [
                'attribute' => 'value',
                'label' => Yii::t('logs', 'Number Of Coin Nominals')
            ],
            [
                'attribute' => 'sum',
                'label' => Yii::t('logs', 'Sum Of Coins')
            ]    
        ]
    ]);
?>
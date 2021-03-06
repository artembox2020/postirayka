<?php

use yii\grid\GridView;

/* @var $dataProvider yii\data\ArrayDataProvider */

?>

<?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'options' => [
            'class' => 'nominals-grid nominals-banknote-grid'
        ],
        'columns' => [
            [
                'attribute' => 'nominal',
                'label' => Yii::t('logs', 'Nominal')
            ],
            [
                'attribute' => 'value',
                'label' => Yii::t('logs', 'Number Of Nominals')
            ],
            [
                'attribute' => 'sum',
                'label' => Yii::t('logs', 'Sum Of Banknotes')
            ]    
        ]
    ]);
?>
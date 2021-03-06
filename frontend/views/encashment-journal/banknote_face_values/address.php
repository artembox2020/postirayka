<?php

use yii\grid\GridView;

/* @var $searchModel frontend\models\CbLogSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */

?>

<?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => [
            'class' => 'address-grid'
        ],
        'columns' => [
            [
                'label' => Yii::t('frontend', 'Address'),
                'format' => 'raw',
                'value' => function($model) use ($searchModel) {

                    return $searchModel->getAddressViewExtended($model);
                }
            ]
        ]
    ]);
?>
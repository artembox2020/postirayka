<?php

use yii\helpers\Html;
use frontend\components\responsive\GridView;
use \frontend\models\Jlog;
use frontend\models\Imei;
use frontend\services\parser\CParser;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\JlogDataSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */
/* @var $params array */

?>

<div class="table-responsives <?= Yii::$app->headerBuilder->getJournalResponsiveClass() ?>">

    <?= GridView::widget([
        'dataProvider' => $arrayProvider,
        'filterModel' => $searchModel,
        'options' => [
            'class' => 'journal-grid-view',
        ],
        'summary'=> $searchModel->getSummaryMessage($params, $dataProvider->query),
        'columns' => [
            [
                'attribute' => 'date',
                'format' => 'raw',
                'label' => Yii::t('imeiData', 'Date Start'),
                'filter' =>  $this->render(
                    '/journal/filters/main',
                    [
                        'name'=> 'date',
                        'params' => $params,
                        'searchModel' => $searchModel,
                        'sortType' => $searchFilter->getSortType($params, 'date')
                    ]
                ),
                'value' => function($model) use ($searchModel)
                {

                    return $searchModel->getDateStartView($model);
                },
                'contentOptions' => ['class' => 'inline']
            ],
            [
                'attribute' => 'date_end',
                'format' => 'raw',
                'label' => Yii::t('imeiData', 'Date End'),
                'filter' => false,
                'value' => function($model) use ($searchModel)
                {

                    return $searchModel->getDateEndView($model);
                },
                'contentOptions' => ['class' => 'inline']
            ],
            [
                'attribute' => 'address',
                'format' => 'raw',
                'filter' =>  $this->render(
                    '/journal/filters/main',
                    [
                        'name'=> 'address',
                        'params' => $params,
                        'searchModel' => $searchModel,
                        'sortType' => $searchFilter->getSortType($params, 'address')
                    ]
                ),
                'value' => function($model) use ($searchModel)
                {

                    return  $searchModel->getAddressView($model);
                },
                'contentOptions' => ['class' => 'inline']
            ],
            [
                'attribute' => 'number_device',
                'label' => Yii::t('frontend', 'Number Device'),
                'format' => 'raw',
                'filter' => $this->render(
                    '/journal/filters/main',
                    [
                        'name'=> 'number_device',
                        'params' => $params,
                        'searchModel' => $searchModel,
                        'sortType' => $searchFilter->getSortType($params, 'number_device')
                    ]
                ),
                'value' => function($model)
                {

                    return $model['number_device'];
                }
            ],
            /*[
                'attribute' => 'level_signal',
                'label' => Yii::t('frontend', 'Level Signal'),
                'value' => function($model)
                {

                    return $model['level_signal'];
                }
            ],*/
            [
                'attribute' => 'bill_cash',
                'label' => Yii::t('frontend', 'Bill Cash'),
                'value' => function($model)
                {

                    return $model['bill_cash'];
                }
            ],
            [
                'attribute' => 'current_status',
                'label' => Yii::t('frontend', 'Current Status'),
                'value' => function($model) use ($searchModel)
                {

                    return $searchModel->getWmMashineStatus($model);
                }
            ],
            /*[
                'attribute' => 'display',
                'label' => Yii::t('frontend' ,'Display'),
                'value' => function($model)
                {

                    return $model['display'];
                }
            ],*/
        ],
    ]);
    ?>
</div>
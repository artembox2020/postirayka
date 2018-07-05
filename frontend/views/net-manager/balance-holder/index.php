<?php

use frontend\models\BalanceHolder;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\BalanceHolderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('frontend', 'Balance Holders');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="balance-holder-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('frontend', 'Create Balance Holder'), ['/balance-holder/create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            ['attribute' => 'name',
                'label' => Yii::t('frontend', 'Balance Holder Name'),
                'value' => function ($dataProvider) {
                    return Html::a(Html::encode($dataProvider->name), Url::to(['view-balance-holder', 'id' => $dataProvider->id]));
                },
                'format' => 'raw',
            ],
//            'name',
//            'city',
//            [
//                'attribute' => 'status',
//                'value' => function ($model) {
//                    return BalanceHolder::getCountAddresses($model->status);
//                },
//                'filter' => BalanceHolder::statuses(),
//            ],
            ['attribute' => 'countAddresses',
                'label' => Yii::t('frontend', 'Count Addresses'),],
            ['attribute' => 'countWashpay',
                'label' => Yii::t('frontend', 'Count Imeis')],
            ['attribute' => 'countWmMachine',
                'label' => Yii::t('frontend', 'Count Wash Machine')],
            [
                'attribute' => 'countGdMachine',
                'label' => Yii::t('frontend', 'Count Gd Machine'),
            ],
//            'contact_person',
//            'position',
//            'phone',
//            'date_start_cooperation',
//            'date_connection_monitoring',
            //'contact_person',
            //'company_id',
            //'created_at',
            //'is_deleted',
            //'deleted_at',

//            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>

<?php
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
$menu = [];
?>
<b>
    <?= $this->render('/net-manager/_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a(Yii::t('frontend', 'Add WM Machine'), ['/net-manager/wm-machine-add'], ['class' => 'btn btn-success']) ?>
    <?= Html::a(Yii::t('frontend', 'Add GD Machine'), ['/gd-mashine/create'], ['class' => 'btn btn-success']) ?>
</p>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
//    'filterModel' => $searchModel,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        ['attribute' => 'type_mashine',
            'label' => Yii::t('frontend', 'Type mashine'),
            'value' => function ($dataProvider) {
                return Html::a(Html::encode($dataProvider->type_mashine), Url::to(['wm-machine-view', 'id' => $dataProvider->id]));
            },
            'format' => 'raw',],
        'model',
        ['attribute' => 'created_at',
            'label' => Yii::t('frontend', 'Date Install'),
            'format' => ['date', 'php:d/m/Y']   ],
        ['attribute' => 'address.address',
            'label' => Yii::t('frontend', 'Address Install'),],

    ]
]);
?>
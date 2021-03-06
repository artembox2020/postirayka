<?php

use frontend\components\responsive\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use frontend\components\responsive\DetailView;
use frontend\services\custom\Debugger;

/* @var $this yii\web\View */
$menu = [];
$machine_menu = [];
?>
<b>
    <?= $this->render('/net-manager/_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b>
<h1><?= Html::encode($this->title) ?></h1>

<b>
    <?= $this->render('/net-manager/_machine_menu', [
            'machine_menu' => $machine_menu,
    ]) ?>
</b>
<br><br>
<p>
    <?php if ( yii::$app->user->can('editTechData') ) echo Html::a(Yii::t('frontend', 'Add WM Machine'), ['/net-manager/wm-machine-add'], ['class' => 'btn btn-success']) ?>
    <?php if ( yii::$app->user->can('editTechData') ) echo Html::a(Yii::t('frontend', 'Add GD Machine'), ['/gd-mashine/create'], ['class' => 'btn btn-success']) ?>
</p>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'gridClass' => GridView::OPTIONS_DEFAULT_GRID_CLASS.' grid-filter-hide',
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        ['attribute' => 'serial_number',
            'label' => Yii::t('frontend', 'Serial number'),
        ],
        ['attribute' => 'number_device',
            'label' => Yii::t('frontend', 'Inventory number'),
            'value' => function ($dataProvider) {
                return Html::a(Html::encode($dataProvider->inventory_number), Url::to(['wm-machine-view', 'id' => $dataProvider->id]));
            },
            'format' => 'raw',],
//        ['attribute' => 'type_mashine',
//            'label' => Yii::t('frontend', 'Type mashine'),
//            'value' => function ($dataProvider) {
//                return Html::a(Html::encode($dataProvider->type_mashine), Url::to(['wm-machine-view', 'id' => $dataProvider->id]));
//            },
//            'format' => 'raw',],
        'model',
//        ['attribute' => 'created_at',
//            'label' => Yii::t('frontend', 'Date Install'),
//            'format' => ['date', 'php:d/m/Y']
//        ],
        [
            'attribute' => 'address.address',
            'label' => Yii::t('frontend', 'Address Install'),
            'format' => 'raw',
            'value' => function($dataProvider) {

                return Yii::$app->commonHelper->link($dataProvider->address);
            }
        ],
        [
            'attribute' => 'balanceHolder.name',
            'label' => Yii::t('frontend', 'Balance Holder'),
            'format' => 'raw',
            'value' => function($dataProvider) {

                return Yii::$app->commonHelper->link($dataProvider->balanceHolder);
            }
        ],
        ['attribute' => 'updated_at',
            'label' => Yii::t('frontend', 'Last ping'),
            'value' => function($dataProvider) {
                return date('[H:i:s] d.m.Y', $dataProvider->ping);
            },
        ],
        'buttons'=>[
            'label' => Yii::t('frontend', 'ID device'),
            'format' => 'raw',
            'options'=>['class' => 'btn btn-primary'],
            'value' => function ($dataProvider) {
                return Html::a(Html::encode($dataProvider->id), Url::to(['wm-machine-view', 'id' => $dataProvider->id]));
            },
        ],
    ]
]);
?>

<p><u><b><?= Yii::t('frontend','Consolidated technical data') ?></b></u><p/>

<!-- Summary by models -->
<?php ob_start(); ?>
<?= GridView::widget([
    'dataProvider' => $provider,
    'summary' => '',
    'columns' => [
        ['attribute' => 'model',
            'label' => Yii::t('frontend', 'By Models'),
            'value' => 'model',
        ],
        [
            'label' => Yii::t('frontend', 'General Count'),
            'value' => function ($provider) {
                return $provider->getModelNameCount($provider->model);
            },
        ],
    ]
]);
?>
<?php $modelWm = ob_get_clean(); ?>

<?php ob_start(); ?>
<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        [
            'label' => Yii::t('frontend', 'Up to 1 year'),
            'format' => 'raw',
            'value' => $model->getUpTo1Year()
        ],
        [
            'label' => Yii::t('frontend', 'Up to 2 years'),
            'format' => 'raw',
            'value' => $model->getUpTo2Year()
        ],
        [
            'label' => Yii::t('frontend', 'Up to 3 years'),
            'format' => 'raw',
            'value' => $model->getUpTo3Year()
        ],
        [
            'label' => Yii::t('frontend', 'Up to 4 years'),
            'format' => 'raw',
            'value' => $model->getUpTo4Year()
        ],
        [
            'label' => Yii::t('frontend', 'Up to 5 years'),
            'format' => 'raw',
            'value' => $model->getUpTo5Year()
        ],
        [
            'label' => Yii::t('frontend', 'Older than 5 years old'),
            'format' => 'raw',
            'value' => $model->getUp5Year()
        ],
    ]
]);
?>
<?php $byYearProd = ob_get_clean(); ?>


<?php ob_start(); ?>
<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        [
            'label' => Yii::t('frontend', 'Warehouse/Office'),
            'format' => 'raw',
            'value' => $model->getStockCountAll()
        ],
        [
            'label' => Yii::t('frontend', 'At the point'),
            'format' => 'raw',
            'value' => $model->getActiveCountAll()
        ],
        [
            'label' => Yii::t('frontend', 'Repair'),
            'format' => 'raw',
            'value' => $model->getRepairCountAll()
        ],
        [
            'label' => Yii::t('frontend', 'Junk'),
            'format' => 'raw',
            'value' => $model->getJunkCountAll()
        ],
    ]
]);
?>
<?php $byStatus = ob_get_clean(); ?>


<!-- Main Detail View -->
<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        [
            'label' => Yii::t('frontend', 'General Count'),
            'value' => $model->getGeneralCount()
        ],
        [
            'label' =>  Yii::t('frontend', 'By Models'),
            'format' => 'raw',
            'value' => $modelWm
        ],
        [
            'label' =>  Yii::t('frontend', 'By year of production'),
            'format' => 'raw',
            'value' => $byYearProd
        ],
        [
            'label' =>  Yii::t('frontend', 'Status'),
            'format' => 'raw',
            'value' => $byStatus
        ],
    ]
]);
?>
<p><u><b><?= Yii::t('frontend','General Info') ?></b></u><p/>

<p><u><b><?= Yii::t('frontend','Consolidated financial data') ?></b></u><p/>

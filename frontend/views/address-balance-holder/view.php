<?php

use yii\helpers\Html;
use frontend\components\responsive\DetailView;

/* @var $this yii\web\View */
/* @var $model frontend\models\AddressBalanceHolder */
/* @var $balanceHolder frontend\models\BalanceHolder */
/* @var $imeis frontend\models\Imei */

$this->title = $model->name;
$dateFormat = "d.m.Y";
?>
<?php $menu = []; ?>
<b>
    <?= $this->render('/net-manager/_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b><br>
<div class="address-balance-holder-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('frontend', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('frontend', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('frontend', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

<?=
    DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'label' => Yii::t('frontend','Address'),
                'value' => $model->address
            ],
            'number_of_citizens',
            [
                'label' => Yii::t('frontend','Balance Holder'),
                'format' => 'raw',
                'value' => function($model) {
                    $title = (
                        ($balanceHolder = $model->balanceHolder) ? $balanceHolder->name :
                        (
                            (($fakeBalanceHolder = $model->getFakeBalanceHolder()) ? $fakeBalanceHolder->name : '').
                            '<br>['.Yii::t('frontend', 'Deleted').']'
                        )
                    );

                    return $balanceHolder ? Yii::$app->commonHelper->link($balanceHolder) : $title;
                }
            ],
            [    
                'label' => Yii::t('frontend','Date Inserted'),
                'value' =>  !empty($model->date_inserted) ?  date($dateFormat, $model->date_inserted) : ''
            ],
            [
                'label' => Yii::t('frontend','Date Monitoring'),
                'value' => !empty($model->date_connection_monitoring) ? date($dateFormat, $model->date_connection_monitoring): ''
            ],
            'number_of_floors',
            'countWashMachine',
            'countGelDispenser',
        ],
    ])
?>

<h3 align="center"><?= Yii::t('frontend', 'Address Card') ?></h3>
<div><b><u><?= Yii::t('frontend','Summary Technical Data') ?></u></b></div>
<br/>

<?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'label' => Yii::t('frontend','Number Washine Cycles'),
                'value' => ''
            ],
            [
                'label' => Yii::t('frontend','Average Currency Amount'),
                'value' => ''
            ],
            [
                'label' => Yii::t('frontend','Money Amount'),
                'value' => ''
            ],
            [
                'label' => Yii::t('frontend','Last errors'),
                'value' => ''
            ],
        ],
    ])
?>

<div><b><u><?= Yii::t('frontend','Consolidated Financial Data') ?></u></b></div>
<br/>

<?php if (!empty($model->getTerminalInfoView())): ?>
    <h3 align="center"><?= Yii::t('frontend', 'Terminal Features') ?></h3>
    <div class="address-terminal-view"><?= $model->getTerminalInfoView() ?></div>
<?php endif; ?>

<div><b><u><?= Yii::t('frontend', 'Wm Mashines') ?></u></b></div>
<br/>

<?php
    echo Yii::$app->runAction(
        '/address-balance-holder/wm-mashines',
        ['address' => $model]
    );
?>

<b><?= Yii::t('graph', 'Address Loading'); ?></b>

    <div class="chart-container-al graph-block">
        <img src="<?= Yii::$app->homeUrl . '/static/gif/loader.gif'?>" class="img-processor" alt>
    </div>

    <?php echo Yii::$app->runAction(
        '/dashboard/render-engine',
        [
            'selector' => '.chart-container-al',
            'action' => '/dashboard/address-loading',
            'active' => 'current ten',
            'other' => $model->id,
            'actionBuilder' => 'builds/action-mls-builder'
        ]);
    ?>

<b><?= Yii::t('graph', 'Level Signal'); ?></b>
<br>

<div class="chart-container-mls graph-block">
    <img src="<?= Yii::$app->homeUrl . '/static/gif/loader.gif'?>" class="img-processor" alt>
</div>

<?php
    echo Yii::$app->runAction(
        '/dashboard/render-engine',
        [
            'selector' => '.chart-container-mls',
            'action' => '/dashboard/modem-level-signal',
            'active' => 'current ten',
            'actionBuilder' => 'builds/action-mls-builder',
            'other' => $model->id
        ]
    );
?>

<?php
    echo Yii::$app->runAction(
        '/journal/index-by-address',
        ['id' => $model->id, 'redirectAction' => '/address-balance-holder/view']
    );
?>
</div>
<div class="margin-bottom-274"></div>

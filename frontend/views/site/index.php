<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\components\responsive\DetailView;
use frontend\services\custom\Debugger;
use frontend\storages\GoogleGraphStorage;
use frontend\storages\AddressStatStorage;
use frontend\storages\MashineStatStorage;
use frontend\models\WmMashineDataSearch;
use frontend\services\globals\DateTimeHelper;
use frontend\models\Jsummary;
use frontend\models\BalanceHolder;
use frontend\models\BalanceHolderSummarySearch;
use frontend\models\AddressBalanceHolder;
use frontend\services\globals\EntityHelper;
use console\controllers\ModemLevelSignalController;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model frontend\models\Company */
/* @var $users common\models\User */
/* @var $balanceHolders  */
/* @var $balanceHoldersData  */

$this->title = $model->name;
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="company-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            /*[
                'attribute' => 'img',
                'format' => 'html',
                'value' => function ($data) {
                    return Html::img(Yii::getAlias('@storageUrl/logos/'. $data['img'],['max-width' => '80px']));
                },
            ],*/
            'phone',
            'website',
        ],
    ]) ?>
    <br>
    <b><?= Yii::t('graph', 'WM Mashine Statistics'); ?></b>

    <div class="chart-container graph-block">
        <img src="<?= Yii::$app->homeUrl . '/static/gif/loader.gif'?>" class="img-processor" alt>
    </div>

    <?php echo Yii::$app->runAction(
        '/dashboard/render-engine',
        [
            'selector' => '.chart-container',
            'action' => '/dashboard/all-green-grey-work-error', 
            'active' => 'current day'
        ]);
    ?>

    <?php if (yii::$app->user->can('viewFinData')) { ?>
    <b><?= Yii::t('graph', 'Balance Holders Incomes'); ?></b>
    <br>

    <div class="chart-container-bh graph-block">
        <img src="<?= Yii::$app->homeUrl . '/static/gif/loader.gif'?>" class="img-processor" alt>
    </div>

    <?php echo Yii::$app->runAction(
        '/dashboard/render-engine',
        [
            'selector' => '.chart-container-bh',
            'action' => '/dashboard/balance-holder-incomes', 
            'active' => 'current day'
        ]);
    ?>

    <?php } ?>

    <br>
    <b><?= Yii::t('graph', 'Address average loading by last 30 days'); ?></b>

    <?= Yii::$app->runAction(
            '/dashboard/address-average-loading'
        )
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
            'active' => 'current day',
            'actionBuilder' => 'builds/action-mls-builder'
        ]);
    ?>

    <b><?= Yii::t('graph', 'Modem Level Signal'); ?></b>
    <br>

    <div class="chart-container-mls graph-block">
        <img src="<?= Yii::$app->homeUrl . '/static/gif/loader.gif'?>" class="img-processor" alt>
    </div>

    <?php echo Yii::$app->runAction(
        '/dashboard/render-engine',
        [
            'selector' => '.chart-container-mls',
            'action' => '/dashboard/modem-level-signal', 
            'active' => 'current day',
            'actionBuilder' => 'builds/action-mls-builder'
        ]);
    ?>

    <?php if (yii::$app->user->can('viewCompanyData')) { ?>

    <b><?= Yii::t('frontend', 'Employees company') ?></b>

    <div class="employees-list">
        <?= \frontend\components\responsive\GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => [
                [
                    'attribute' => 'name',
                    'label' => Yii::t('frontend', 'Employee'),
                    'value' => function($data) {
                        return $data->userProfile->firstname." ".$data->userProfile->lastname;
                    },
                ],

                [
                    'attribute' => 'position',
                    'label' => Yii::t('common', 'Position'),
                    'value' => function($data) {
                        return $data->userProfile->position;
                    },
                ],

                [
                    'label' => Yii::t('frontend', 'Access Level'),
                    'value' => function($data) {
                        return  $data->getUserRoleName($data->id);
                    },
                ],
            ],
        ]); ?>
    </div>

    <?php } ?>

    <b><?= Yii::t('frontend', 'Balance Holders'); ?></b>
    <br>

    <div class="balance-holders-list">
        <?= Yii::$app->view->render('balance_holders', ['balanceHolders' => $balanceHolders, 'balanceHoldersData' => $balanceHoldersData]) ?>
    </div>

</div>
<div class="margin-bottom-274"></div>
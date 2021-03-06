<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model frontend\models\Base */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Bases', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="base-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'date',
            'imei',
            'gsmSignal',
            'fvVer',
            'numBills',
            'billAcceptorState',
            'id_hard',
            'type',
            'collection',
            'ZigBeeSig',
            'billCash',
            'tariff',
            'event',
            'edate',
            'billModem',
            'sumBills',
            'ost',
            'numDev',
            'devSignal',
            'statusDev',
            'colGel',
            'colCart',
            'price',
            'timeout',
            'doorpos',
            'doorled',
            'kpVer',
            'srVer',
            'mTel',
            'sTel',
            'ksum',
        ],
    ]) ?>

</div>

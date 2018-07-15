<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;
use yii\grid\GridView;
use frontend\services\custom\Debugger;
use frontend\controllers\OtherContactPersonController;
/* @var $this yii\web\View */
/* @var $model frontend\models\Company */
/* @var $users common\models\User */
/* @var $balanceHolders  */
?>
<?php $menu = []; ?>
<b>
    <?= $this->render('/net-manager/_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b><br><br>
<p><u><b><?= Yii::t('frontend','Balance Holder') ?></b></u><p/>
<?php
    $person = $dataProvider->query->one();
    $dataProvider->query = $dataProvider->query->offset(1);
    
    $contactPerson = 
        [
            'label' => Yii::t('frontend','Contact Person'),
            'format' => 'raw',
            'value' => (!empty($person->name) ? $person->name : '')
                ." ".
                (
                    count($model->otherContactPerson) <= OtherContactPersonController::NINE_DIGIT 
                    ? OtherContactPersonController::getCreateLink() : ''
                )
        ];
        
    if(!empty($person->id)) {
        $contactPersonPosition =
            [
                'label' => Yii::t('common','Position'),
                'value' => $person->position
            ];
            
        $contactPersonPhone =
            [
                'label' => Yii::t('frontend','Phone'),
                'value' => $person->phone
            ];;
            
        $contactPersonCreated =
            [
                'label' => Yii::t('frontend','Created'),
                'value' => date("M j, Y g:i:s A",$person->created_at)
            ];
            
        $contactPersonControls =
            [
                'label' => Yii::t('common','Actions'),
                'format' => 'raw',
                'value' => OtherContactPersonController::getUpdateLink($person->id)." ".OtherContactPersonController::getDeleteLink($person->id)
            ];    
    }
    else {
        $contactPersonPosition = [];
        $contactPersonPhone = [];
        $contactPersonCreated = [];
        $contactPersonControls = [];
    }
    
    $widgetAttributes = [
            [
                'label' => Yii::t('common','Name'),
                'value' => $model->name
            ],
            [
                'label' => Yii::t('frontend','Address'),
                'value' => $model->address
            ],
            [
                'label' => Yii::t('frontend','Date Start'),
                'value' => date("M j, Y g:i:s A",$model->date_start_cooperation)
            ],
            [
                'label' => Yii::t('frontend','Date Monitoring'),
                'value' => date("M j, Y g:i:s A",$model->date_connection_monitoring)
            ],
            $contactPerson,
            $contactPersonPosition,
            $contactPersonPhone,
            $contactPersonCreated,
            $contactPersonControls
        ];
        
    $widgetAttributes= array_filter($widgetAttributes, function($value) { return ( is_array($value) && count($value) > 0 ); });
    
?>

<?= DetailView::widget([
        'model' => $model,
        'attributes' => $widgetAttributes
    ]);
?>

<p><u><b><?= Yii::t('frontend','Summary Technical Data') ?></b></u><p/>

<?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'label' =>  Yii::t('frontend', 'Count Addresses'),
                'value' => $model->countAddresses
            ],
            [
                'label' =>  Yii::t('frontend', 'Count Imeis'),
                'value' => $model->countWashpay
            ],
            [
                'label' =>  Yii::t('frontend', 'Count Wash Machine'),
                'value' => $model->countWmMachine
            ],
            [
                'label' =>  Yii::t('frontend', 'Count Gd Machine'),
                'value' => $model->countGdMachine
            ],
            [
                'label' => Yii::t('frontend', 'Last errors'),
                'value' => Yii::t('frontend', 'Last errors'),
            ],
            [
                'label' => Yii::t('frontend', 'Last repairs'),
                'value' => Yii::t('frontend', 'Last repairs'),
            ]
        ]
    ]);
?>

<div><b><u><?= Yii::t('frontend','Other Contact People') ?></u></b></div>
<br>
<p>
    <?= OtherContactPersonController::getCreateLink() ?>
</p>
<?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'name',
            'position',
            'phone',
            [
                'label' => Yii::t('common','Actions'),
                'format' => 'raw',
                'value' => function($model) {
                    return OtherContactPersonController::getUpdateLink($model->id)." ".OtherContactPersonController::getDeleteLink($model->id);
                }
            ]
        ]
]); ?>
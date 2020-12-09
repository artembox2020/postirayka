<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use bs\Flatpickr\FlatpickrWidget;

/* @var $this yii\web\View */
/* @var $model frontend\models\AddressBalanceHolder */
/* @var $balanceHolder frontend\models\BalanceHolder */
/* @var $form yii\widgets\ActiveForm */
/* @var $company \frontend\models\Company */
?>

<?php
    $balanceHolderOptions = [];
    if(!empty($balanceHolder)) {
        $balanceHolderOptions = [
            'value' => $balanceHolder->id
        ];
    }
?>

<div class="address-balance-holder-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'number_of_citizens')->input('number', ['maxlength' => true]) ?>

    <?= $form->field($model, 'floor')->textInput() ?>

    <?= $form->field($model, 'number_of_floors')->textInput(['maxlength' => true]) ?>
<?php if (!$model->isNewRecord): ?>

    <?= $form->field($model, 'date_inserted')->widget(FlatpickrWidget::className(), [
        'locale' => strtolower(substr(Yii::$app->language, 0, 2)),
        'groupBtnShow' => true,
        'options' => [
            'class' => 'form-control',
        ],
        'clientOptions' => [
            'allowInput' => true,
            'defaultDate' => $model->date_inserted ? date(DATE_ATOM, $model->date_inserted) : null,
        ],
    ]) ?>

    <?= $form->field($model, 'date_connection_monitoring')->widget(FlatpickrWidget::className(), [
        'locale' => strtolower(substr(Yii::$app->language, 0, 2)),
        'groupBtnShow' => true,
        'options' => [
            'class' => 'form-control',
        ],
        'clientOptions' => [
            'allowInput' => true,
            'defaultDate' => $model->date_connection_monitoring ? date(DATE_ATOM, $model->date_connection_monitoring) : null,
        ],
    ]) ?>
<?php endif ?>

    <?= $form->field($model, 'company_id')->hiddenInput(['value'=> $company->id])->label(false); ?>

    <?= $form->field($model, 'balance_holder_id')->dropDownList(
            \yii\helpers\ArrayHelper::map($balanceHolders, 'id', 'name'),
            $balanceHolderOptions
    ) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('frontend', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

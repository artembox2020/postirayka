<?php

use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\UserForm */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $roles yii\rbac\Role[] */
/* @var $permissions yii\rbac\Permission[] */

$this->title = Yii::t('backend', 'Create user');
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Users'), 'url' => ['users']];
$this->params['breadcrumbs'][] = $this->title;
?>
    <div class="user-create">

        <?php $form = ActiveForm::begin() ?>

        <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'position')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'birthday')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'status')->checkbox(['label' => Yii::t('backend', 'Activate'), 'checked' => true]) ?>

        <?= $form->field($model, 'roles')->checkboxList($roles) ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('backend', 'Create'), ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end() ?>

    </div>

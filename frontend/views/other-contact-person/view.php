<?php

use yii\helpers\Html;
use frontend\components\responsive\DetailView;

/* @var $this yii\web\View */
/* @var $model frontend\models\OtherContactPerson */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('frontend', 'Other Contact People'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php $menu = []; ?>
<b>
    <?= $this->render('/net-manager/_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b><br>
<div class="other-contact-person-view">

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

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'balance_holder_id',
            'name',
            'position',
            'phone',
            'created_at:datetime',
//            'is_deleted',
//            'deleted_at',
        ],
    ]) ?>

</div>

<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;
use frontend\services\custom\Debugger;
/* @var $this yii\web\View */
/* @var $model frontend\models\Company */
/* @var $users common\models\User */
/* @var $balanceHolders frontend\models\BalanceHolder */
/* @var $addresses */
?>
<?php $menu = []; ?>
<b>
    <?= $this->render('_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b><br><br>
<?php foreach ($balanceHolders as $balanceHolder) : ?>
    <?php foreach ($balanceHolder->addressBalanceHolders as $address) : ?>
        <?php foreach ($address->imeis as $imei) : ?>
            IMEI: <a href="/net-manager/washpay-view?id=<?= $imei->id ?>"><b><?= $imei->imei ?></b></a>
            Адреса: <?= $address->address ?>
            Балансоутримувач: <?= $balanceHolder->name ?>
            Останній пінг: <?= Yii::$app->formatter->asDate($imei->updated_at, 'dd.MM.yyyy H:i:s');?><br>
        <?php endforeach; ?>
    <?php endforeach;?>
<?php endforeach; ?>

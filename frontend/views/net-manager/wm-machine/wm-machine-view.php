<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;
use frontend\services\custom\Debugger;
/* @var $this yii\web\View */
/* @var $model frontend\models\Company */
/* @var $users common\models\User */
/* @var $balanceHolders  */
/* @var $addresses */
/* @var $wm_machine */
?>
<?php $menu = []; ?>
<b>
    <?= $this->render('/net-manager/_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b><br><br>
<div>
    <b><u>Картка Пральной машины</u></b><br>
    Серийный номер: <?= $wm_machine->serial_number ?><br>
    Дата производства: 346567<br>
    Дата підключення до моніторінга: 45665<br>
    Адрес установки: Надається можливість обрати розташування пральної машини<br>
    Номер пральної машини: Надається можливість обрати номер пральної машини зі списку<br>
    Последний пинг: 12.08.2023 23:37:01<br>
</div>

<a href="wm-machine-update?id=<?= $wm_machine->id ?>">Update</a>

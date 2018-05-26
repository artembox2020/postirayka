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
/* @var $address */
/* @var $balanceHolder frontend\models\BalanceHolder */
/* @var $imei frontend\models\Imei */

?>
<?php $menu = []; ?>
<b>
    <?= $this->render('/net-manager/_sub_menu', [
        'menu' => $menu,
    ]) ?>
</b><br><br>
IMEI: <?= $imei->imei ?><br>
Номер телефона: <?= $imei->phone_module_number ?><br>
Балансотримач: <?= $balanceHolder->name . ' ' . $balanceHolder->address; ?><br>
Адреса/поверх: <?= $address->address ?><br>
Версія плати: <?= $imei->firmware_version ?><br>
Версія бутлоадера / дата: <?= $imei->type_bill_acceptance ?> | Дата - Откуда?<br>
Версія основной прошивки / дата: <?= $imei->type_bill_acceptance ?> | Дата - Откуда?<br>
Последний пинг: <?= Yii::$app->formatter->asDate($imei->updated_at, 'dd.MM.yyyy H:i:s'); ?><br>
<div><b><a href="/net-manager/edit">edit</a></b></div>
<br>
<div>
    <b><u>Історія</u></b><br>
    Кількість циклів прання: 23454<br>
    Час роботи: 346567<br>
    Кількість грошей: 45665<br>
    Останні помилки: список<br>
</div>

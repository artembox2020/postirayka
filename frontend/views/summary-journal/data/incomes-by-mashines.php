<?php

use yii\helpers\Html;
use frontend\models\BalanceHolderSummarySearch;
use frontend\services\globals\EntityHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel frontend\models\BalanceHoderSummarySearch */
/* @var $data array */

?>
<div>
    <table class="table table-bordered table-income">
        <tbody>
        <?php
            for ($j = 0; $j < count($data) - 3; ++$j):
        ?>
            <tr>
            <?php
                for ($i = 1; $i <= count($data[$j]['incomes']); ++$i):
            ?>
                <td
                    data-timestamp-start = "<?= $data[$j][$i]['timestampStart'] ?>"
                    data-timestamp-end = "<?= $data[$j][$i]['timestampEnd'] ?>"
                    data-income = "<?= $data[$j]['incomes'][$i]['income'] ?>"
                    data-idle-hours = "<?= $data[$j]['incomes'][$i]['idleHours'] ?>"
                    data-work-idle-hours = "<?= explode("**", $data[$j]['incomes'][$i]['idleHoursReasons'])[0] ?>"
                    data-connect-idle-hours = "<?= explode("**", $data[$j]['incomes'][$i]['idleHoursReasons'])[1] ?>"
                    data-connect-cb-idle-hours = "<?= explode("**", $data[$j]['incomes'][$i]['idleHoursReasons'])[2] ?>"
                    data-cp-idle-hours = "<?= explode("**", $data[$j]['incomes'][$i]['idleHoursReasons'])[3] ?>"
                    data-damage-idle-hours = "<?= $data[$j]['incomes'][$i]['damageIdleHours'] ?>"
                    data-all-hours = "<?= $data[$j]['incomes'][$i]['allHours'] ?>"
                    data-is-deleted = "<?= $data[$j]['incomes'][$i]['deleted'] ?>"
                    data-is-created = "<?= $data[$j]['incomes'][$i]['created'] ?>"
                    data-address-id = "<?= $data[$j]['incomes'][$i]['mashine_id'] ?>"
                    data-mashine-id = "<?= $data[$j]['incomes'][$i]['mashine_id'] ?>"
                    class = " timestamp <?= $data[$j][$i]['class'] ?>"
                >
                <?php
                    echo (
                        isset($data[$j]['incomes'][$i]['income']) ?
                        '<span class="td-cell">'.$data[$j]['incomes'][$i]['income'].'</span>' : '<span class="td-cell"> &nbsp;</span>'
                    ).
                    EntityHelper::makePopupWindow(
                        [],
                        $summaryJournalController->renderPopupLabelDetailed(
                            $data[$j]['incomes'][$i],
                            $data[$j][$i]['timestampStart'],
                            $data[$j][$i]['timestampEnd']
                        ),
                        'color: black; text-align: left;',
                        'height: 10px; position: absolute;'
                    );
                ?>
                </td>
            <?php
                endfor; 
            ?>
            </tr>
        <?php
            endfor;
        ?>
            <tr class="summary-total">
                <?php for ($i = 1, $j = 0; $i <= $days; ++$i) { ?>
                    <td
                        class="summary-total-cell"
                        data-idles-total="<?= $data['idlesTotal'][$i] ?>"
                        data-count-total="<?= $data['countTotal'] ?>"
                    >
                        <?= isset($data['summaryTotal'][$i]) ? $data['summaryTotal'][$i] : '&nbsp;&nbsp;' ?>
                    </td>
                <?php } ?>
            </tr>
            <tr class="count-total">
                <?php for ($i = 1, $j = 0; $i <= $days; ++$i) { ?>
                    <td
                        class="summary-count-total"
                        data-idles-total="<?= $data['idlesTotal'][$i] ?>"
                        data-count-total="<?= $data['countTotal'] ?>"
                    >
                    <?= !empty($data['countTotal']) ?
                        $searchModel->parseFloat($data['summaryTotal'][$i] / $data['countTotal'], 2) : 0 
                    ?>
                    </td>
                <?php } ?>
            </tr>
        </tbody>
    </table>
</div>
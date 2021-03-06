<?php

/* @var $numberOfDays integer */
/* @var $monthName string */
/* @var $year integer */
/* @var $timestampStart timestamp */
/* @var $timestampEnd timestamp */
?>
<div>
    <div class = "green-color red-color"><?= $monthName.', '.$year ?></div>
    <table data-stamp-start="<?= $timestampStart ?>" data-stamp-end="<?= $timestampEnd ?>" class="table table-bordered table-month">
        <tbody>
            <tr data-key="1">
            <?php for ($i = 1; $i <= $numberOfDays; ++$i): ?>
                <?php
                    $class = $searchModel->isWeekend($year, $month, $i) ? 'rest' : '';
                ?>
                <td class="cell-device <?= $class ?>"><?= $i ?></td>
            <?php endfor; ?>
            </tr>
        </tbody>
    </table>
</div>
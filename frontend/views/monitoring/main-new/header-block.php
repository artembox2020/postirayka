<?php

use frontend\services\globals\EntityHelper;
use frontend\components\MonitoringBuilder;

?>
<thead>
    <tr class="header-block">
        <th style="width: 40px;">
            <div class="<?= MonitoringBuilder::setHeaderClass($postParams, 'number') ?> number">
                <?= Yii::t('frontend', 'Number') ?>
                <span class="dropdown-toggle"> </span>
            </div>
        </th>
        <th class="bg-lightgrey">
            <div class="<?= MonitoringBuilder::setHeaderClass($postParams, 'bhname') ?> bhname">
                <?= Yii::t('frontend', 'Balance Holder Name') ?>
                <span class="dropdown-toggle"> </span>
            </div>
        </th>
        <th>
            <div class="<?= MonitoringBuilder::setHeaderClass($postParams, 'address') ?> address">
                <?= Yii::t('frontend', 'Address') ?>
                <span class="dropdown-toggle"> </span>
            </div>
        </th>
        <th class="fin">
            <img src="<?= Yii::getAlias('@storageUrl/main-new') ?>/img/Group_2.png" alt="roll">
            <span class="triangle-down" ></span>
            <?= EntityHelper::makePopupWindow(
                [],
                $searchModel->attributeLabels()['fireproof_residue'],
                'top: -5px',
                'height: 5px'
            );
            ?>
        </th>
        <th valign="center" class="fin">
            <img src="<?= Yii::getAlias('@storageUrl/main-new') ?>/img/Fill_1.png" alt=""> 
            <span class="triangle-down" ></span>
            <?= EntityHelper::makePopupWindow(
                [],
                $searchModel->attributeLabels()['money_in_banknotes'],
                'top: -5px',
                'height: 5px'
            );
            ?>
        </th>
        <th class="text-left fin">
            <img src="<?= Yii::getAlias('@storageUrl/main-new') ?>/img/Fill_Copy.jpg" alt="car">
            <span class="font10"> <?= Yii::t('frontend', 'Last') ?></span>
            <?= EntityHelper::makePopupWindow(
                [],
                $searchModel->attributeLabels()['date_sum_last_encashment'],
                'top: -5px',
                'height: 5px'
            );
            ?>
        </th>
        <th class="text-left fin">
            <img src="<?= Yii::getAlias('@storageUrl/main-new') ?>/img/Fill_Copy.jpg" alt="car">
            <span class="font10"> <?= Yii::t('frontend', 'PreLast') ?></span>
            <?= EntityHelper::makePopupWindow(
                [],
                $searchModel->attributeLabels()['date_sum_pre_last_encashment'],
                'top: -5px',
                'height: 5px'
            );
            ?>
        </th>
        <th class="fin">
            <img src="<?= Yii::getAlias('@storageUrl/main-new') ?>/img/Group.png" alt="money">
            <?= EntityHelper::makePopupWindow(
                [],
                $searchModel->attributeLabels()['in_banknotes'],
                'top: -5px',
                'height: 5px'
            );
            ?>
        </th>
        <th class="tech"> <?= Yii::t('frontend', 'Software') ?></th>
        <th colspan="2" class="tech"> <?= Yii::t('frontend', 'Terminal') ?></th>
        <th colspan="6" class="tech"> <?= Yii::t('frontend', 'Wm Mashines') ?></th>
    </tr>
</thead>
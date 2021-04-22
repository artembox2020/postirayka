<?php

/* @var $menuItems array */
/* @var $brand_url string */
/* @var $userMenuItems array */

?>

<header class="top-menu container-fluid">
    <div class="logo ml-1 ml-md-5">
        <a href="<?= $brand_url ?>">
            <img src="<?= Yii::getAlias('@storageUrl/main-new') ?>/img/logo.png" alt="Постирайка">
        </a>
    </div>
    <div class="menu-items mr-5 nav">
        <?php foreach ($menuItems as $item): ?>
            <?php if (isset($item['visible']) && !$item['visible']): ?>
                <?php continue; ?>
            <?php endif; ?>
            <?php if ($item['label'] == 'add-card'): ?>
                <?= Yii::$app->view->render('@frontend/views/layouts/main-new/add-card') ?>
            <?php else: ?>
                <a href ="<?= $item['url'][0] ?>"><?= $item['label'] ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
        <button class="btn-user"><img src="<?= Yii::getAlias('@storageUrl/main-new') ?>/img/user.svg" alt="user"></button>
    </div>

    <?php if (!empty($userMenuItems)): ?>
        <div class="user-actions">
            <?php foreach ($userMenuItems as $item): ?>
                <?php if (isset($item['visible']) && !$item['visible']): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <a href = "<?= $item['url'][0] ?>" data-method = "post"><?= $item['label'] ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="burger-menu">
        <svg 
            class="svg-inline--fa fa-bars fa-w-14 fa-2x white"
            aria-hidden="true"
            focusable="false"
            data-prefix="fas"
            data-icon="bars"
            role="img"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 448 512"
            data-fa-i2svg=""
        >
            <path 
                fill="currentColor"
                d="M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z"
            >
            </path>
        </svg>
        <i class="fas fa-bars fa-2x white"></i>
    </div>
    <div class="mobile-menu nav">
        <?php foreach ($menuItems as $item): ?>
            <?php if ($item['label'] == 'add-card'): ?>
                <?= Yii::$app->view->render('@frontend/views/layouts/main-new/add-card') ?>
            <?php else: ?>
                <a href ="<?= $item['url'][0] ?>"><?= $item['label'] ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</header>

<?= Yii::$app->view->render("@frontend/views/layouts/main-new/header-script") ?>
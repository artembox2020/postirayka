<?php
require __DIR__ . '/../../common/env.php';
/**
 * Setting path aliases
 */
Yii::setAlias('root', realpath(__DIR__ . '/../../'));
Yii::setAlias('common', realpath(__DIR__ . '/../../common'));
Yii::setAlias('frontend', realpath(__DIR__ . '/../../frontend'));
Yii::setAlias('backend', realpath(__DIR__ . '/../../backend'));
Yii::setAlias('console', realpath(__DIR__ . '/../../console'));
Yii::setAlias('storage', realpath(__DIR__ . '/../../storage'));
Yii::setAlias('api', realpath(__DIR__ . '/../../api'));
Yii::setAlias('.well-known', realpath(__DIR__ . '/../../.well-known'));

/**
 * Setting url aliases
 */
Yii::setAlias('frontendUrl', env('FRONTEND_URL'));
Yii::setAlias('backendUrl', env('BACKEND_URL'));
Yii::setAlias('storageUrl', env('STORAGE_URL'));
Yii::setAlias('dashboardUrl', '/frontend/views/dashboard');

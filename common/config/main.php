<?php

$config = [
    'name'=>'Postirayka',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'extensions' => require __DIR__ . '/../../vendor/yiisoft/extensions.php',
    'timeZone' => env('TIMEZONE'),
    'sourceLanguage' => 'en-US',
    'language' => env('LANGUAGE'),
    'bootstrap' => ['frontend\config\LoggerBootstrap',],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'modules' => [
        'rbac' => [
            'class' => 'developeruz\db_rbac\Yii2DbRbac',
//            'layout' => '//admin',
            'params' => [
                'userClass' => 'common\models\User'
            ]
        ],
        'noty' => [
            'class' => 'lo\modules\noty\Module',
        ],
        'v1' => [
            'class' => 'api\modules\v1\Module',
        ],
        'v2d00' => [
            'class' => 'api\modules\v2d00\Module',
        ],
        't1' => [
            'class' => 'api\modules\t1\Module',
        ],
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => env('DB_DSN'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'tablePrefix' => env('DB_TABLE_PREFIX'),
            'charset' => 'utf8',
            'enableSchemaCache' => YII_ENV_PROD,
        ],
        'formatter' => [
            'decimalSeparator' => '.',
            'thousandSeparator' => '',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'as AccessBehavior' => [
            'class' => \developeruz\db_rbac\behaviors\AccessBehavior::className(),
        ],
        'assetManager' => [
            'class' => 'yii\web\AssetManager',
            'linkAssets' => env('LINK_ASSETS'),
            'appendTimestamp' => YII_ENV_DEV,
        ],
        'log' => [
            'traceLevel' => YII_ENV_DEV ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\DbTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['yii\web\HttpException:*', 'yii\i18n\I18N\*'],
                    'prefix' => function () {
                        $url = !Yii::$app->request->isConsoleRequest ? Yii::$app->request->getUrl() : null;

                        return sprintf('[%s][%s]', Yii::$app->id, $url);
                    },
                    'logVars' => [],
                ],
            ],
        ],
        'i18n' => [
            'translations' => [
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                ],
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                    'fileMap' => [
                        'common' => 'common.php',
                        'backend' => 'backend.php',
                        'frontend' => 'frontend.php',
                        'app' => 'app.php'
                    ],
                ],
            ],
        ],
        'keyStorage' => [
            'class' => 'common\components\keyStorage\KeyStorage',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => array(
                '<module:\w+>/<controller:\w+>/<action:(\w|-)+>' => '<module>/<controller>/<action>',
                '<module:\w+>/<controller:\w+>/<action:(\w|-)+>/<id:\d+>' => '<module>/<controller>/<action>',
            ),
        ],
        'mailer' => [
            
            'class' => 'yii\swiftmailer\Mailer',
//            One more suggestion is to use port "465" and encryption as "ssl" instead of port "587", encryption "tls".
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.yandex.ru',
                'username' => 'artem-voloshyn21@yandex.ru',
                'password' => 'Zend12__',
                'port' => 587,
                'encryption' => 'tls',
            ],
            'useFileTransport' => false,
        ],
        'cache' => [
            'class' => YII_ENV_DEV ? 'yii\caching\DummyCache' : 'yii\caching\FileCache',
//            'class' => 'yii\caching\DummyCache',
        ],
    ],
];

return $config;

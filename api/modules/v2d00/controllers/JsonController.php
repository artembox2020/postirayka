<?php

namespace api\modules\v2d00\controllers;

use api\modules\v2d00\UseCase\Encashment\Encashment;
use api\modules\v2d00\UseCase\ImeiInit\ImeiInit;
use api\modules\v2d00\UseCase\Log\Log;
use api\modules\v2d00\UseCase\StatePackage\StatePackage;
use frontend\controllers\Controller;
use frontend\services\custom\Debugger;
use Yii;
use yii\web\Response;

/**
 * Class JsonController
 * @package api\modules\v2d00\controllers
 */
class JsonController extends \yii\web\Controller
{
    const LOG = 'L';
    const INI = 'I';
    const STATUS = 'S';
    const ENCASHMENT = 'C';

    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [],
                'actions' => [
                    'incoming' => [
                        'Origin' => ['*'],
                        'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'HEAD', 'OPTIONS'],
                        'Access-Control-Request-Headers' => ['*'],
                        'Access-Control-Allow-Credentials' => null,
                        'Access-Control-Max-Age' => 86400,
                        'Access-Control-Expose-Headers' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['index'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $input = file_get_contents("php://input");
        $this->retranslatePackage($input);

        $items = json_decode($input);

        // сотояние
//        Debugger::dd($items->type);
        if ($items->type == self::LOG) {
            $log = new Log();

            return $log->create($items);
        }

        // инициализация
        if ($items->type == self::INI) {
            $init = new ImeiInit();
//            $initLog = new InitLog();
//            $initLog->create($items);
            return $init->add($items);
        }

        if ($items->type == self::STATUS) {
            $status = new StatePackage();
            return $status->create($items);
        }

        // инкассация
        if ($items->type == self::ENCASHMENT) {
            $encashment = new Encashment();
            return $encashment->add($items);
        }
        return Yii::$app->response->statusCode = 400;
    }

    /**
     * Puts down log into the file '/log/packets.dump'
     * @param string $packet
     */
    public function putLog(string $packet): void
    {
        $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/log/packets.dump', 'a+');
        fwrite($fp, $packet."\n");
        fclose($fp);
    }

    /** retranslates packets to another server **/
    public function retranslatePackage($input): void
    {
        ob_start();


        $url = 'http://167.86.119.116:6080'.\yii\helpers\Url::to(['/v2d00/json/index']);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getallheaders());

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);
        curl_multi_exec($mh, $active);

        //execute the multi handle
        do {
            $status = curl_multi_exec($mh, $active);

            if ($active) {
                // Wait a short time for more activity
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);


        $url = 'http://167.86.98.115:6080'.\yii\helpers\Url::to(['/v2d00/json/index']);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getallheaders());

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);
        curl_multi_exec($mh, $active);

        //execute the multi handle
        do {
            $status = curl_multi_exec($mh, $active);

            if ($active) {
                // Wait a short time for more activity
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);


        ob_get_clean();
    }
}

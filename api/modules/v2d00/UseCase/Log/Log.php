<?php

namespace api\modules\v2d00\UseCase\Log;

use frontend\services\custom\Debugger;
use api\modules\v2d00\UseCase\Command\Command;
use Yii;

class Log
{
    const CENTRAL_BOARD = '-1';
    const WASH_MACHINE = '0';

    public function create($items)
    {
        if ($items->pac->devType == self::CENTRAL_BOARD) {
            try {
                $create = new CentralBoardLog();

                if (!$create->add($items)) {
                    Yii::$app->response->statusCode = 500;

                    return 'CB';
                }

                if (!Command::getCommand((int)$items->imei)) {
                    Yii::$app->response->statusCode = 201;
                }

                return Command::getCommand((int)$items->imei);

            } catch (\Exception $exception) {
                return $exception;
            }
        }

        if ($items->pac->devType == self::WASH_MACHINE) {
            $create = new WashMachineLog();

            if ($create->add($items)) {
                Yii::$app->response->statusCode = 201;
            } else {
                Yii::$app->response->statusCode = 500;
            }

            return 'WM';
        }
    }
}

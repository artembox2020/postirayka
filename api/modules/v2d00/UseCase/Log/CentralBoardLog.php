<?php
declare(strict_types=1);

namespace api\modules\v2d00\UseCase\Log;

use frontend\models\CbLog;
use frontend\models\CbLogSearch;
use frontend\models\Imei;
use frontend\services\custom\Debugger;
use frontend\services\globals\DateTimeHelper;
use Yii;

class CentralBoardLog
{
    const ONE_CONST = 1;
    const CB_CONST = 'cb';

    public function add($items): bool
    {
        if (Imei::findOne(['imei' => (int)$items->imei])) {
            $imei = $this->getImei($items->imei);
            if (Imei::getStatus($imei) == self::ONE_CONST) {
                $cbl = new CbLog();

                $dateTimeHelper = new DateTimeHelper();
                $cbLogSearch = new CbLogSearch();
                $cbl->company_id = $imei->company_id;
                $cbl->address_id = $imei->address_id;
                $cbl->imei_id = $imei->id;

                if (!empty($items->time)) {
                    $cbl->date = $items->time;
                }

                $cbl->imei = $items->imei;
                $cbl->device = self::CB_CONST;
                $cbl->signal = $items->pac->rssi;
                $cbl->unix_time_offset = $dateTimeHelper->getRealUnixTimeOffset((int)$items->pac->utc);
                $cbl->status = $items->pac->event->cenBoard;
                $cbl->fireproof_counter_hrn = $items->pac->money->total;
                $cbl->fireproof_counter_card = $items->pac->money->totalCards;
                $cbl->collection_counter = $items->pac->money->collection;
                $cbl->notes_billiards_pcs = $items->pac->money->numberNotes;
                //$cbl->rate = $items->pac->tariff;
                $cbl->refill_amount = $items->pac->devCash;
//                $cbl->banknote_face_values = $cbLogSearch->normalizeBanknoteFaceValuesString($items->numberNotes);
                $cbl->is_deleted = false;

                $imei->ping = time();
                $imei->save();

                return $cbl->save();
            }
        }

        Yii::$app->response->statusCode = 400;

        return false;
    }

    /**
     * @param $imei
     * @return Imei|null
     */
    public function getImei($imei)
    {
        return Imei::findOne(['imei' => (int)$imei]);
    }
}

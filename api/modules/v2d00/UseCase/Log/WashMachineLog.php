<?php
declare(strict_types=1);

namespace api\modules\v2d00\UseCase\Log;

use frontend\models\Imei;
use frontend\models\WmLog;
use frontend\services\globals\DateTimeHelper;
use yii\helpers\Json;

class WashMachineLog
{
    const ONE_CONST = 1;
    const WM_CONST = 'wm';

    public function add($items): bool
    {
        $dateTimeHelper = new DateTimeHelper();

        if (Imei::findOne(['imei' => (int)$items->imei])) {
            $imei = $this->getImei($items->imei);

            if (Imei::getStatus($imei) == self::ONE_CONST) {
                $wml = new WmLog();
                $wml->company_id = $imei->company_id;
                $wml->address_id = $imei->address_id;
                $wml->imei_id = $imei->id;

                if (!empty($items->time)) {
                    $wml->date = $items->time;
                }

                $wml->imei = $items->imei;
                $wml->device = self::WM_CONST;
                $wml->signal = $items->pac->rssi;
                $wml->unix_time_offset = $dateTimeHelper->getRealUnixTimeOffset((int)$items->pac->utc);
                $wml->number = $items->pac->numberDev;
                $wml->status = $items->pac->event->washer;
                $wml->price = $items->pac->priceMode;
                $wml->rate = $items->pac->tariff;
                $wml->account_money = $items->pac->devCash;
                $wml->washing_mode = $items->pac->washMode;
                $wml->wash_temperature = $items->pac->washTemp;
                $wml->spin_type = $items->pac->washExtrac;
                $wml->prewash = $items->pac->washAddition->prewash;
                $wml->rinsing = $items->pac->washAddition->rising_plus;
                $wml->intensive_wash = $items->pac->washAddition->intensive_washing;
                $wml->is_deleted = false;

                $imei->ping = time();
                $imei->save();

                return $wml->save();
            }
        }
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

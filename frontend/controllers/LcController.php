<?php

namespace frontend\controllers;

use frontend\models\CbLog;
use frontend\models\CbLogSearch;
use frontend\models\dto\CentralBoardDto;
use frontend\models\Imei;
use frontend\services\custom\Debugger;
use frontend\services\globals\DateTimeHelper;
use yii\web\Controller;

/**
 * Class LcController
 * @package frontend\controllers
 */
class LcController extends \frontend\controllers\Controller
{
    const ONE_CONST = 1;

    public $type_packet = 'cb';

    /**
     * @param $p
     */
    public function actionIndex($p)
    {
        $this->retranslatePackage('/lc/index', $p);
        $result = $this->iParse($p);
        $centralBoardDto = new CentralBoardDto($result);
        $dateTimeHelper = new DateTimeHelper();

        $cbLogSearch = new CbLogSearch();

        if (Imei::findOne(['imei' => $centralBoardDto->imei])) {
            $imei = $this->getImei($centralBoardDto->imei);
            if (Imei::getStatus($imei) == self::ONE_CONST) {
                $cbl = new CbLog();
                $cbl->company_id = $imei->company_id;
                $cbl->address_id = $imei->address_id;
                $cbl->imei_id = $imei->id;
                $cbl->date = $centralBoardDto->date;
                $cbl->imei = $centralBoardDto->imei;
                $cbl->device = $this->type_packet;
                $cbl->signal = $imei->level_signal;
                $cbl->unix_time_offset = $dateTimeHelper->getRightUtcTimestampByLocalTimestamp(
                    $centralBoardDto->unix_time_offset, env('TIMEZONE')
                );
                $cbl->status = $centralBoardDto->status;
                $cbl->fireproof_counter_hrn = $centralBoardDto->fireproof_counter_hrn;
                $cbl->fireproof_counter_card = $centralBoardDto->fireproof_counter_card;
                $cbl->collection_counter = $centralBoardDto->collection_counter;
                $cbl->notes_billiards_pcs = $centralBoardDto->notes_billiards_pcs;
                $cbl->rate = $centralBoardDto->rate;
                $cbl->refill_amount = $centralBoardDto->refill_amount;
                $cbl->last_collection_counter = $centralBoardDto->last_collection_counter;
                $cbl->banknote_face_values = $cbLogSearch->normalizeBanknoteFaceValuesString($centralBoardDto->banknote_face_values);
                $cbl->is_deleted = false;
                $cbl->save();
                $imei->ping = time();
                $imei->save();
                // echo 'cbl data save!';
                exit;
            } else {
                // echo 'Imei not Active';
                exit;
            }
        } else {
            // echo 'Imei not exists';
            exit;
        }
    }

    /**
     * Parsers the packet type data of TYPE PACKET LOG Central Board
     *
     * @param $p
     * @return array
     */
    public function iParse($p)
    {
        $arrOut = array();

        $column = [
            'date',
            'imei',
            'unix_time_offset',
            'status',
            'fireproof_counter_hrn',
            'fireproof_counter_card',
            'collection_counter',
            'notes_billiards_pcs',
            'rate',
            'refill_amount'
        ];

        // new cental board log version
        $columnNew = [
            'imei',
            'unix_time_offset',
            'status',
            'fireproof_counter_hrn',
            'collection_counter',
            'notes_billiards_pcs',
            'rate',
            'last_collection_counter',
            'banknote_face_values'
        ];

        $array = array_map("str_getcsv", explode('*', $p));

        foreach ($array as $subArr) {
            $arrOut = array_merge($arrOut, $subArr);
        }

        if (count($arrOut) == count($columnNew)) {
            $result = array_combine($columnNew, $arrOut);
            $result['date'] = null;
            $result['fireproof_counter_card'] = null;
            $result['refill_amount'] = null;
        } else {
            $result = array_combine($column, $arrOut);
            $result['last_collection_counter'] = null;
            $result['banknote_face_values'] = null;
        }

        return $result;
    }

    /**
     * @param $imei
     * @return Imei|null
     */
    public function getImei($imei)
    {
        return Imei::findOne(['imei' => $imei]);
    }

    public function tempPars($p)
    {

    }
}

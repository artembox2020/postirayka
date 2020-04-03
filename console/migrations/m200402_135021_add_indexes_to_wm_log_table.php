<?php

use yii\db\Migration;

/**
 * Class m200402_135021_add_indexes_to_wm_log_table
 */
class m200402_135021_add_indexes_to_wm_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // creates index for columns `address_id`, `company_id`, `unix_time_offset`
        $this->createIndex(
            'idx-wm_log-address_id-company_id-unix_time_offset',
            'wm_log',
            [
                'address_id',
                'company_id',
                'unix_time_offset'
            ],
            false
        );

        // creates index for columns `address_id`, `company_id`, `created_at`
        $this->createIndex(
            'idx-wm_log-address_id-company_id-created_at',
            'wm_log',
            [
                'address_id',
                'company_id',
                'created_at'
            ],
            false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex(
            'idx-wm_log-address_id-company_id-unix_time_offset',
            'wm_log'
        );

        $this->dropIndex(
            'idx-wm_log-address_id-company_id-created_at',
            'wm_log'
        );
    }
}

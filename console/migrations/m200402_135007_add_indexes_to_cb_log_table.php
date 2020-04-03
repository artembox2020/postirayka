<?php

use yii\db\Migration;

/**
 * Class m200402_135007_add_indexes_to_cb_log_table
 */
class m200402_135007_add_indexes_to_cb_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // creates index for columns `address_id`, `company_id`, `unix_time_offset`
        $this->createIndex(
            'idx-cb_log-address_id-company_id-unix_time_offset',
            'cb_log',
            [
                'address_id',
                'company_id',
                'unix_time_offset'
            ],
            false
        );

        // creates index for columns `address_id`, `company_id`, `created_at`
        $this->createIndex(
            'idx-cb_log-address_id-company_id-created_at',
            'cb_log',
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
            'idx-cb_log-address_id-company_id-unix_time_offset',
            'cb_log'
        );

        $this->dropIndex(
            'idx-cb_log-address_id-company_id-created_at',
            'cb_log'
        );
    }
}

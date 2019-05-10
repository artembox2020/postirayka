<?php

namespace frontend\services\logger\src\service;

use frontend\services\custom\Debugger;
use frontend\services\logger\src\LoggerDtoInterface;
use frontend\services\logger\src\storage\StorageInterface;

/**
 * Class LoggerService
 * @package frontend\services\logger\src\service
 */
class LoggerService
{
    const MINUS_ONE = -1;
    private $storage;
    private $dto;
    private $_items = [];

    /**
     * LoggerService constructor.
     * @param StorageInterface $storage
     * @param LoggerDtoInterface $dto
     */
    public function __construct(StorageInterface $storage, LoggerDtoInterface $dto)
    {
        $this->storage = $storage;
        $this->dto = $dto;
    }


    public function createLog($data, $event): void
    {
//        $res = $this->dto->createDto($data, $event);
//
//        Debugger::dd($res);

        $this->storage->save($this->dto->createDto($data, $event));
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        $this->loadItems();

        return $this->_items;
    }

    /**
     * Load Items
     */
    private function loadItems(): void
    {
        $this->_items = $this->storage->load();
    }

    public function getArray($data)
    {
        $long = strtotime($data->date_start_cooperation);
        $data->setAttribute('date_start_cooperation', $long);
        $long = strtotime($data->date_connection_monitoring);
        $data->setAttribute('date_connection_monitoring', $long);

        $newAttributes = $data->getDirtyAttributes();
//        $oldAttributes = $data->getOldAttributes();

        return $newAttributes;
    }
}

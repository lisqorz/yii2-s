<?php


namespace lisq\s\server;


use yii\base\Component;

abstract class Server
{
    abstract public function __construct($address,$port);

    /**
     * @param $handler
     * @param $moduleConfig
     */
    abstract public function start($handler,$moduleConfig);
}
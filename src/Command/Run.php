<?php


namespace Sgenmi\eYaf\Command;

class Run
{
    private static $instance;
    static function getInstance(...$args)
    {
        if(!isset(self::$instance)){
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }

    public function __construct()
    {

    }
    public function exec($args){

    }

}
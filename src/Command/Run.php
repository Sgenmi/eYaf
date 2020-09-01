<?php


namespace Sgenmi\eYaf\Command;

use Sgenmi\eYaf\Command\Action\Install;

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
        $execAction = implode('_',$args);
        switch ($execAction){
            case 'install' || 'install_init':
                (new Install())->init();
                break;
            case 'install_rbac':
                (new Install())->rbac();
                break;
        }
    }

}
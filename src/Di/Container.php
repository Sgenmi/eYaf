<?php

namespace Sgenmi\eYaf\Di;

use Sgenmi\eYaf\Contract\ContainerInterface;

/**
 * Author: Sgenmi
 * Date: 2023/6/15 3:18 PM
 * Email: 150560159@qq.com
 */

class Container implements ContainerInterface
{
    private static Container $instance;
    private array $container=[];

    private function __construct()
    {
        $this->__init();
    }

    /**
     * @return Container|static
     * @author Sgenmi
     */
    public static function getInstance(): Container
    {
        if(!isset(self::$instance)){
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __init(): void
    {
        $this->container=[
            self::class => $this,
        ];
    }

    public function set(string $id, $entry)
    {
        $this->container[$id] = $entry;
    }

    public function get(string $id)
    {
        if (isset($this->container[$id]) || array_key_exists($id, $this->container)) {
            return $this->container[$id];
        }
        return (new $id);
    }

    public function has(string $id): bool
    {
        return false;
    }

    private function make(){

    }

    public function getAll(){
        return $this->container;
    }
}
<?php

namespace Sgenmi\eYaf\Di;

use Sgenmi\eYaf\Context;
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

    public function set(string $id, $entry): void
    {
        $this->container[$id] = $entry;
    }

    public function get(string $id):mixed
    {
        if (isset($this->container[$id]) || array_key_exists($id, $this->container)) {
            return $this->container[$id];
        }
        return $this->make($id);
    }

    public function has(string $id): bool
    {
       return isset($this->container[$id]);
    }

    /**
     * @param string $id
     * @return mixed
     * @author Sgenmi
     */
    private function make(string $id):mixed{

        if(Context::has($id)){
            return Context::get($id);
        }
        $val = new $id();
        Context::set($id,$val);
        return $val;
    }

    public function destroy(string $id): void
    {
        unset($this->container[$id]);
    }

    public function getContainer():array{
        return $this->container;
    }
}
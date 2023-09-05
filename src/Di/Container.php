<?php
declare(strict_types=1);

namespace Sgenmi\eYaf\Di;

use Sgenmi\eYaf\Context;
use Sgenmi\eYaf\Contract\ContainerInterface;
use Sgenmi\eYaf\Contract\RequestInterface;
use Sgenmi\eYaf\Contract\ResponseInterface;

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
        if(empty($id)){
            return;
        }
        //特殊二个，走短生命周期
        if($id==RequestInterface::class || $id==ResponseInterface::class ){
            Context::set($id,$entry);
        }else{
            $this->container[$id] = $entry;
        }
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
     * @param array $parameters
     * @return mixed
     * @author Sgenmi
     */
    public function make(string $id,array $parameters=[] ):mixed{
        if(Context::has($id)){
            return Context::get($id);
        }
        $val = new $id(...$parameters);
        Context::set($id,$val);
        return $val;
    }

    public function destroy(string $id): void
    {
        unset($this->container[$id]);
        Context::destroy($id);
    }

    public function getContainer():array{
        return $this->container;
    }
}
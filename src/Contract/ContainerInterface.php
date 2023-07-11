<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/6/20 11:17 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Contract;

interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * @param string $id
     * @param mixed $entry
     * @return mixed
     * @author Sgenmi
     */
    public function set(string $id, mixed $entry);

    /**
     * @param string $id
     * @param array $parameters
     * @return mixed
     * @author Sgenmi
     */
    public function make(string $id,array $parameters=[]):mixed;

    /**
     * @return array
     * @author Sgenmi
     */
    public function getContainer():array;
}
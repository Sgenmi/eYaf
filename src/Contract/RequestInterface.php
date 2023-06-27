<?php

/**
 * Author: Sgenmi
 * Date: 2023/6/25 9:51 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Contract;

use Psr\Http\Message\ServerRequestInterface;

interface RequestInterface extends ServerRequestInterface
{
    public function getParams():array;
    public function getParam(string $name):string;
    public function setParams(array $params):RequestInterface;
}
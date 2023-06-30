<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/6/21 10:28 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Http;


use Nyholm\Psr7\ServerRequest;
use Sgenmi\eYaf\Contract\RequestInterface;


class Request extends ServerRequest implements RequestInterface
{
    //兼容 Yaf $request->getParams()
    private array $params=[];

    public function getParams(): array
    {
        return $this->params;
    }
    public function getParam(string $name): string
    {
        return strval($this->params[$name]??'');
    }

    public function setParams(array $params ): RequestInterface {
        $this->params = $params;
        return $this;
    }
}
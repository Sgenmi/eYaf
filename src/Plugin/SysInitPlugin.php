<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/7/3 19:09 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Plugin;

use Sgenmi\eYaf\Contract\RequestInterface;
use Sgenmi\eYaf\Contract\ResponseInterface;
use Sgenmi\eYaf\Contract\StdoutLoggerInterface;
use Sgenmi\eYaf\Di\Container;
use Sgenmi\eYaf\Http\Request;
use Sgenmi\eYaf\Http\Response;
use Sgenmi\eYaf\Logger\MonoLog;
use Yaf\Plugin_Abstract;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;

final class SysInitPlugin extends Plugin_Abstract
{
    public function routerShutdown(Request_Abstract $request, Response_Abstract $response)
    {
        $exeClient =  php_sapi_name();
        if($exeClient!='fpm-fcgi' && $exeClient!='cgi-fcgi'){
            return;
        }
        $serverArr= $request->getServer();
        if($exeClient=='cgi-fcgi'){
            unset($serverArr['CommonProgramFiles(x86)']);
            unset($serverArr['ProgramFiles(x86)']);
        }
        $con = Container::getInstance();
        $url = ($serverArr['REQUEST_SCHEME']??'http').'://'.$serverArr['HTTP_HOST'].($serverArr['REQUEST_URI']??'/');
        $req = new Request($request->getMethod(),$url,$serverArr,$request->getRaw()?:'','1.1',$serverArr);
        $req->setParams($request->getParams());
        $con->set(RequestInterface::class,
            $req->withParsedBody($request->getPost())
            ->withCookieParams($request->getCookie())
            ->withUploadedFiles($request->getFiles())
            ->withQueryParams($request->getQuery())
        );
        $con->set(ResponseInterface::class,new Response(200,[],null));
        $con->set(StdoutLoggerInterface::class,new MonoLog());
    }

    /**
     * @param Request_Abstract $request
     * @param Response_Abstract $response
     * @return void
     * @author Sgenmi
     */
    public function dispatchLoopShutdown(Request_Abstract $request, Response_Abstract $response){

        $con = Container::getInstance();
        $resp = $con->get(ResponseInterface::class);
        $content = $resp->getBody()->getContents();
        if($content){
            //防止存在模板的情况，会影响模板正常输出
            if(!$response->getBody()){
                $headers = $resp->getHeaders();
                foreach ($headers as $k=> $v){
                    $response->setHeader($k,$v[0]??'');
                }
            }
            $response->setBody($content,'json');
        }
    }

}
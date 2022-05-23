<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2022/4/21 1:21 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Tcp;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

interface WebSocketInterface
{
    /**
     * @param Response|Server $server
     * @param Request $request
     */
    public function onOpen($server, Request $request): void;

    /**
     * @param Response|Server $server
     * @param int $fd
     * @param int $reactorId
     * @return void
     */
    public function onClose($server, int $fd, int $reactorId): void;

    /**
     * @param Response|Server $server
     * @param Frame $frame
     * @return void
     */
    public function onMessage($server, Frame $frame): void;

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function onRequest(Request $request, Response $response): void;

}
<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2022/4/21 1:22 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Contract;

use Swoole\Server;

interface TcpInterface
{

    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     */
    public function OnConnect(Server $server, int $fd,int $reactor_id):void;

    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param string $data
     * @return void
     */
    public function OnReceive(Server $server, int $fd, int $reactor_id,string $data):void;

    /**
     * @param Server $server
     * @param int $fd
     * @return void
     */
    public function OnClose(Server $server,int $fd):void;


}
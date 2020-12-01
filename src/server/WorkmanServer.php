<?php

namespace lisq\s\server;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as HttpServer;
use Swoole\Server\Port;
use Swoole\Table;
use Swoole\WebSocket\Server as WebSocketServer;

class WorkmanServer
{
    private $server = null;
}
<?php

namespace lisq\s\server;

use lisq\s\hook\web\SApplication;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as HttpServer;
use Swoole\Server\Port;
use Swoole\Table;
use Swoole\WebSocket\Server as WebSocketServer;

class SwooleServer extends Server
{
    private $server = null;


    public function __construct($address, $port)
    {
        $this->server = new HttpServer($address, $port);
    }

    public function start($handler, $moduleConfig)
    {
        $this->server->on('request', function ($request, $response) use ($handler, $moduleConfig) {
            $_GET = $request->get ?? [];
            $_POST = $request->post ?? [];
            $_FILES = $request->files ?? [];
            $_COOKIE = $request->cookie ?? [];
            foreach ($request->server as $key => $item) {
                $_SERVER[strtoupper($key)] = $item;
            }
            $_SERVER['SCRIPT_NAME'] = parse_url($_SERVER['REQUEST_URI'] == '/' ? 'index.php' : $_SERVER['REQUEST_URI'])['path'];
            $_SERVER['SCRIPT_FILENAME'] = $moduleConfig['path'] . "/web/" . $_SERVER['SCRIPT_NAME'];
            $ext = pathinfo($_SERVER['SCRIPT_NAME'])['extension'];
            if ($ext == 'php') {
                ob_start();
                $handler($request, $response);
                $content = ob_get_clean();
                if (!empty($content)) {
                    $response->write($content);
                }
            } else if (in_array($ext, ['css', 'js'])) {
                if (file_exists($_SERVER['SCRIPT_FILENAME'])) {
                    $response->status(200);
                    $response->sendfile($_SERVER['SCRIPT_FILENAME']);
                } else {
                    $response->status(404);
                }
            }
            @$response->end();
            return;
        });
        $this->server->start();
    }

    public function __destruct()
    {

    }
}

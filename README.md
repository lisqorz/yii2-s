```bash
$ compose require lisqorz/yii2-s

$ yii s/{start|reload|restart|stop|status}

```

```php

        'errorHandler' => [
            'class' => \lisq\s\hook\web\SErrorHandler::class,
            'silentExitOnException' => true,
        ],
        'serverManager' => [
            'class' => \lisq\s\server\ServerManager::class,
            'inotifyReload' => [
                'enable' => true,
                'watch_path' => dirname(dirname(__DIR__)),
                'file_types' => ['.php'],
                'excluded_dirs' => [],
                'log' => true,
            ],

            'websocket' => [
                'enable' => true,
                'handler' => ''
            ],
            'timer' => [
                'enable' => true,
                'jobs' => [
//                    'xxxClass'
                    //['xxxClass',[args...]]
                ]
            ],
            'processes' => [],
            'httpServer' => [
                'frontend' => [
                    'path' => dirname(dirname(__DIR__)) . '/frontend',
                    'port' => 8998,
                    'address' => '127.0.0.1',
                    'dependentServer' => \lisq\s\server\SwooleServer::class,
                ],
                'backend' => [
                    'path' => dirname(dirname(__DIR__)) . '/backend',
                    'port' => 8998,
                    'address' => '127.0.0.1',
                    'dependentServer' => \lisq\s\server\SwooleServer::class,
                ]
            ],
        ],

```
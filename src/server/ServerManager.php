<?php


namespace lisq\s\server;


use lisq\s\hook\web\SApplication;
use Yii;
use yii\base\Component;
use yii\web\Application;

class ServerManager extends Component
{
    private static $server = null;

    public $websocket;
    public $timer;
    public $processes;
    public $httpServer;
    public $inotifyReload;

    public static function getServer()
    {
        return self::$server;
    }

    public function init()
    {

    }

    public function mergerConfig($path)
    {
//        require $path . '/../vendor/autoload.php';
//        require $path . '/../common/config/bootstrap.php';
        require $path . '/config/bootstrap.php';

        return \yii\helpers\ArrayHelper::merge(
            require $path . '/../common/config/main.php',
            require $path . '/../common/config/main-local.php',
            require $path . '/config/main.php',
            require $path . '/config/main-local.php'
        );
    }

    public function start($moduleName)
    {

        $moduleConfig = $this->httpServer[$moduleName];
        self::$server = new $moduleConfig['dependentServer']($moduleConfig['address'] ?? '127.0.0.1', $moduleConfig['port']);
        $config = $this->mergerConfig($moduleConfig['path']);
//        TODO 放在一个合适的地方
//        defined('YII_ENV') or define('YII_ENV', 'prod');
        self::$server->start(function ($request, $response) use ($config) {
            $app = (new SApplication($config));
            $app->start($request, $response);
            $app->close();
            unset($app);
        }, $moduleConfig);
    }

    public function reload($moduleName)
    {
        $moduleConfig = $this->httpServer[$moduleName];

    }

    public function stop($moduleName)
    {
        $moduleConfig = $this->httpServer[$moduleName];

    }

    public function status($moduleName)
    {
        $moduleConfig = $this->httpServer[$moduleName];

    }


}
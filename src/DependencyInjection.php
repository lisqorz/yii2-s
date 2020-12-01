<?php

namespace lisq\s;

use lisq\s\controllers\CommandController;
use Yii;
use yii\base\BootstrapInterface;

class DependencyInjection implements BootstrapInterface
{

    public function bootstrap($app)
    {
        $this->addControllers($app);
    }

    /**
     * 添加控制器到console
     * @param $app
     */
    private function addControllers($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[Configuration::EXTENSION_CONTROLLER_ALIAS] = CommandController::class;
        }
    }
}
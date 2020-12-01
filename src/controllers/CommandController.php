<?php

namespace lisq\s\controllers;

use lisq\s\server\ServerManager;
use yii\console\Controller;

class CommandController extends Controller
{
    /**
     * @var ServerManager|object|null
     */
    public $manager = null;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id,$module,$config);
        if (!\Yii::$app->has('serverManager')) {
            $this->stderr("未配置serverManger");
            return 1;
        }
        $this->manager = \Yii::$app->get('serverManager');
    }

    public function actionStart($moduleName = 'frontend')
    {
        $str = "  ___    ___ ___  ___    _______                ________      
 |\  \  /  /|\  \|\  \  /  ___  \              |\   ____\     
 \ \  \/  / | \  \ \  \/__/|_/  /| ____________\ \  \___|_    
  \ \    / / \ \  \ \  \__|//  / /|\____________\ \_____  \   
   \/  /  /   \ \  \ \  \  /  /_/_\|____________|\|____|\  \  
 __/  / /      \ \__\ \__\|\________\              ____\_\  \ 
|\___/ /        \|__|\|__| \|_______|             |\_________\
\|___|/                                           \|_________|
                                                              
                                                              ";
        $this->stdout($str);
        $this->manager->start($moduleName);
    }

    public function actionStop($moduleName = 'frontend')
    {
        $this->manager->stop($moduleName);
    }

    public function actionReload($moduleName = 'frontend')
    {
        $this->manager->reload($moduleName);
    }

    public function actionStatus($moduleName = 'frontend')
    {
        $this->manager->status($moduleName);
    }

}
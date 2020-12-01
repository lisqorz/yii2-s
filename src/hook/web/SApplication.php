<?php

namespace lisq\s\hook\web;

use Yii;
use yii\base\ExitException;
use yii\base\InvalidRouteException;
use yii\helpers\Url;
use yii\log\Logger;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UrlNormalizerRedirectException;

class SApplication extends \yii\web\Application
{
    private $responseHandler = null;
    private $requestHandler = null;

    /**
     * Registers the errorHandler component as a PHP error handler.
     * @param array $config application config
     */
    protected function registerErrorHandler(&$config)
    {
        if (YII_ENABLE_ERROR_HANDLER) {
            if (!isset($config['components']['errorHandler']['class'])) {
                echo "Error: no errorHandler component is configured.\n";
                return 1;
            }
            $this->set('errorHandler', $config['components']['errorHandler']);
            unset($config['components']['errorHandler']);
            $this->getErrorHandler()->register();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => SRequest::class],
            'response' => ['class' => SResponse::class],
            'session' => ['class' => SSession::class],
            'errorHandler' => ['class' => SErrorHandler::class],
        ]);
    }

    public function start($requestHandler, $responseHandler)
    {
        Yii::setLogger(new SLogger);
        $this->responseHandler = $responseHandler;
        $this->requestHandler = $requestHandler;
        Yii::$app->set('requestHandler', $requestHandler);
        Yii::$app->set('responseHandler', $responseHandler);
        $this->run();
    }

    public function run()
    {

        try {
            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
            $response->send();
            $this->state = self::STATE_END;

        } catch (ExitException $e) {
            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;
        }
    }


    public function end($status = 0, $response = null)
    {
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END) {
            $this->state = self::STATE_END;
            $response = $response ?: $this->getResponse();
            $response->send();
        }

        if (YII_ENV_TEST) {
            throw new ExitException($status);
        }
        $this->responseHandler->end("");
    }

    public function close()
    {
        // close db
        $this->getDb()->close();
        // close session
        $this->getSession()->close();
        // close log
        $this->getLog()->getLogger()->flush(true);
    }
}
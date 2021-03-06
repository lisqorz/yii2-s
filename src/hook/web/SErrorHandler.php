<?php


namespace lisq\s\hook\web;


use Yii;
use yii\base\ErrorException;
use yii\base\ExitException;
use yii\base\UserException;
use yii\helpers\VarDumper;
use yii\web\ErrorHandler;
use yii\web\Response;

class SErrorHandler extends ErrorHandler
{
    private $__registered = false;
    private $__memoryReserve = '';

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
    }

    public function register()
    {
        if (!Yii::$app->has('responseHandler')) {
            return parent::register();
        }

        {
            // 取消parent 注册的错误事件
            restore_error_handler();
            restore_exception_handler();
        }

        if (!$this->__registered) {
            ini_set('display_errors', false);
            set_exception_handler([$this, 'handleException']);
            if (defined('HHVM_VERSION')) {
                set_error_handler([$this, 'handleHhvmError']);
            } else {
                set_error_handler([$this, 'handleError']);
            }
            if ($this->memoryReserveSize > 0) {
                $this->__memoryReserve = str_repeat('x', $this->memoryReserveSize);
            }
            register_shutdown_function([$this, 'handleFatalError']);
            $this->__registered = true;
        }

    }

    public function handleFatalError()
    {
        if (!Yii::$app->has('responseHandler')) {
            return parent::handleFatalError();
        }
        unset($this->__memoryReserve);

        // load ErrorException manually here because autoloading them will not work
        // when error occurs while autoloading a class
        if (!class_exists('yii\\base\\ErrorException', false)) {
            require_once Yii::getAlias('@vendor') . '/yiisoft/yii2/base/ErrorException.php';
        }

        $error = error_get_last();

        if (ErrorException::isFatalError($error)) {
            $exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            $this->exception = $exception;

            $this->logException($exception);

            if ($this->discardExistingOutput) {
                $this->clearOutput();
            }
            $this->renderException($exception);

            // need to explicitly flush logs because exit() next will terminate the app immediately
            Yii::getLogger()->flush(true);
            if (defined('HHVM_VERSION')) {
                flush();
            }
            return 1;
        }
    }

    protected function handleFallbackExceptionMessage($exception, $previousException)
    {
        if (!Yii::$app->has('responseHandler')) {
            return parent::handleFallbackExceptionMessage($exception, $previousException);
        }
        $msg = "An Error occurred while handling another error:\n";
        $msg .= (string)$exception;
        $msg .= "\nPrevious exception:\n";
        $msg .= (string)$previousException;
        if (YII_DEBUG) {
            if (PHP_SAPI === 'cli') {
                echo $msg . "\n";
            } else {
                echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES, Yii::$app->charset) . '</pre>';
            }
        } else {
            echo 'An internal server error occurred.';
        }
        $msg .= "\n\$_SERVER = " . VarDumper::export($_SERVER);
        error_log($msg);
        if (defined('HHVM_VERSION')) {
            flush();
        }
        return 1;
    }

    /**
     * Handles uncaught PHP exceptions.
     *
     * This method is implemented as a PHP exception handler.
     *
     * @param \Exception $exception the exception that is not caught
     */
    public function handleException($exception)
    {
        if (!Yii::$app->has('responseHandler')) {
            return parent::handleException($exception);
        }
        if ($exception instanceof ExitException) {
            return;
        }

        $this->exception = $exception;

        $this->unregister();


        try {
            $this->logException($exception);
            if ($this->discardExistingOutput) {
                $this->clearOutput();

            }
            $this->renderException($exception);
            if (!$this->silentExitOnException) {
                \Yii::getLogger()->flush(true);
                if (defined('HHVM_VERSION')) {
                    flush();
                }
                return 1;
            }
        } catch (\Exception $e) {
            // an other exception could be thrown while displaying the exception
            $this->handleFallbackExceptionMessage($e, $exception);
        } catch (\Throwable $e) {
            // additional check for \Throwable introduced in PHP 7
            $this->handleFallbackExceptionMessage($e, $exception);
        }

        $this->exception = null;
    }

    /**
     * Handles PHP execution errors such as warnings and notices.
     *
     * This method is used as a PHP error handler. It will simply raise an [[ErrorException]].
     *
     * @param int $code the level of the error raised.
     * @param string $message the error message.
     * @param string $file the filename that the error was raised in.
     * @param int $line the line number the error was raised at.
     * @return bool whether the normal error handler continues.
     *
     * @throws ErrorException
     */
    public function handleError($code, $message, $file, $line)
    {
        if (!Yii::$app->has('responseHandler')) {
            return parent::handleError($code, $message, $file, $line);
        }
        if (error_reporting() & $code) {
            // load ErrorException manually here because autoloading them will not work
            // when error occurs while autoloading a class
            if (!class_exists('yii\\base\\ErrorException', false)) {

                require_once Yii::getAlias('@vendor') . '/yiisoft/yii2/base/ErrorException.php';
            }
            $exception = new ErrorException($message, $code, $code, $file, $line);

            if (PHP_VERSION_ID < 70400) {
                // prior to PHP 7.4 we can't throw exceptions inside of __toString() - it will result a fatal error
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                array_shift($trace);
                foreach ($trace as $frame) {
                    if ($frame['function'] === '__toString') {
                        $this->handleException($exception);
                        if (defined('HHVM_VERSION')) {
                            flush();
                        }
                        return 1;
                    }
                }
            }

            throw $exception;
        }

        return false;
    }

    protected function renderException($exception)
    {
        if (!Yii::$app->has('responseHandler')) {
            return parent::renderException($exception);
        }
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            // reset parameters of response to avoid interference with partially created response data
            // in case the error occurred while sending the response.
            $response->isSent = false;
            $response->stream = null;
            $response->data = null;
            $response->content = null;
        } else {
            $response = new Response();
        }

        $response->setStatusCodeByException($exception);

        $useErrorView = $response->format === Response::FORMAT_HTML && (!YII_DEBUG || $exception instanceof UserException);

        if ($useErrorView && $this->errorAction !== null) {
            Yii::$app->view->clear();
            $result = Yii::$app->runAction($this->errorAction);
            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response->data = $result;
            }
        } elseif ($response->format === Response::FORMAT_HTML) {
            if ($this->shouldRenderSimpleHtml()) {
                // AJAX request
                $response->data = '<pre>' . $this->htmlEncode(static::convertExceptionToString($exception)) . '</pre>';
            } else {
                // if there is an error during error rendering it's useful to
                // display PHP error in debug mode instead of a blank screen
                if (YII_DEBUG) {
                    ini_set('display_errors', 1);
                }
                $file = $useErrorView ? $this->errorView : $this->exceptionView;
                $response->data = $this->renderFile($file, [
                    'exception' => $exception,
                ]);
            }
        } elseif ($response->format === Response::FORMAT_RAW) {
            $response->data = static::convertExceptionToString($exception);
        } else {
            $response->data = $this->convertExceptionToArray($exception);
        }
        $responseHandler = Yii::$app->get('responseHandler');
        ob_start();
        $response->send();
        $content = ob_get_clean();
        $responseHandler->write($content);
    }


}
<?php


namespace lisq\s\hook\web;


use lisq\s\server\ServerManager;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\web\HeadersAlreadySentException;

class SResponse extends \yii\web\Response
{

    protected function sendContent()
    {
        if ($this->stream === null) {
            echo $this->content;
            return;
        }

        // Try to reset time limit for big files
        if (!function_exists('set_time_limit') || !@set_time_limit(0)) {
            Yii::warning('set_time_limit() is not available', __METHOD__);
        }

        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list($handle, $begin, $end) = $this->stream;

            // only seek if stream is seekable
            if ($this->isSeekable($handle)) {
                fseek($handle, $begin);
            }

            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                echo fread($handle, $chunkSize);
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                echo fread($this->stream, $chunkSize);
                flush();
            }
            fclose($this->stream);
        }
    }

    /**
     * Sends the response to the client.
     */
    public function send()
    {
        if ($this->isSent) {
            return;
        }
        $this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepare();
        $this->trigger(self::EVENT_AFTER_PREPARE);
        $this->sendHeaders();
        $this->sendContent();
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }


    /**
     * Sends the response headers to the client.
     */
    protected function sendHeaders()
    {
        if (headers_sent($file, $line)) {
            throw new HeadersAlreadySentException($file, $line);
        }
        /** @var \Swoole\Http\Response $responseHandler */
        $responseHandler = Yii::$app->get('responseHandler');
        foreach ($this->getHeaders() as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            foreach ($values as $value) {
                $responseHandler->setHeader($name, $value);
            }
        }
        $statusCode = $this->getStatusCode();
        $responseHandler->setStatusCode($statusCode);
        $this->sendCookies();
    }

    /**
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        if ($this->getHeaders() === null) {
            return;
        }
        /** @var \Swoole\Http\Response $responseHandler */
        $responseHandler = Yii::$app->get('responseHandler');
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation) {
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        foreach ($this->getCookies() as $cookie) {
            $value = $cookie->value;
            if ($cookie->expire != 1 && isset($validationKey)) {
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }

            if (PHP_VERSION_ID >= 70300) {
                $responseHandler->setCookie(
                    $cookie->name,
                    $value,
                    $cookie->expire,
                    $cookie->path,
                    $cookie->domain,
                    $cookie->secure,
                    $cookie->httpOnly,
                    !empty($cookie->sameSite) ? $cookie->sameSite : null
                );
//                setcookie($cookie->name, $value, [
//                    'expires' => $cookie->expire,
//                    'path' => $cookie->path,
//                    'domain' => $cookie->domain,
//                    'secure' => $cookie->secure,
//                    'httpOnly' => $cookie->httpOnly,
//                    'sameSite' => !empty($cookie->sameSite) ? $cookie->sameSite : null,
//                ]);
            } else {
                // Work around for setting sameSite cookie prior PHP 7.3
                // https://stackoverflow.com/questions/39750906/php-setcookie-samesite-strict/46971326#46971326
                if (!is_null($cookie->sameSite)) {
                    $cookie->path .= '; samesite=' . $cookie->sameSite;
                }
                $responseHandler->setCookie(
                    $cookie->name,
                    $value,
                    $cookie->expire,
                    $cookie->path,
                    $cookie->domain,
                    $cookie->secure,
                    $cookie->httpOnly);
//                setcookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
            }
        }
    }
}
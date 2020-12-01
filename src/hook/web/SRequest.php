<?php


namespace lisq\s\hook\web;


use yii\web\HeaderCollection;

class SRequest extends \yii\web\Request
{
    private $__headers = null;

    public function getHeaders()
    {
        if ($this->__headers === null) {
            /** @var \Swoole\Http\Request $requestHandler */
            $requestHandler = \Yii::$app->get('requestHandler');
            $this->__headers = new HeaderCollection();
            foreach ($requestHandler->header as $name => $value) {
                $this->__headers->add($name, $value);
            }
            $this->filterHeaders($this->__headers);
        }
        return $this->__headers;
    }

}
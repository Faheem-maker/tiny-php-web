<?php

namespace framework\web\widgets;

use framework\blaze\interfaces\RootContext;

abstract class Widget
{
    public function props()
    {
    }

    public $css = [];
    public $js = [];

    public $content;

    public function begin(RootContext $ctx)
    {
    }
    public abstract function run(RootContext $ctx);
    public function end(RootContext $ctx)
    {
    }

    /**
     * Helper Methods
     */

    protected function renderPartial($view, $params = [])
    {
        $dir = $this->get_widget_dir();
        $view = view("@widgets.$dir.$view", $params);

        ob_start();
        $view->render();

        return ob_get_clean();
    }

    /**
     * This method gets the relative path
     * to current widget's directory.
     */
    protected function get_widget_dir()
    {
        $cls = static::class;

        $cls = str_replace('\\', '/', $cls);
        $cls = str_replace('app/resources/widgets/', '', $cls);

        $lastPos = strrpos($cls, '/');

        return substr($cls, 0, $lastPos);
    }

    protected function attributes($attrs)
    {
        // Build the string
        $result = "";

        foreach ($attrs as $key => $value) {
            if (isset($value) && $value !== false) {
                if ($value === true) {
                    $result .= $key . ' ';
                } else {
                    $result .= "$key=\"$value\" ";
                }
            }
        }

        return rtrim($result);
    }
}
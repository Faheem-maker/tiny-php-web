<?php

namespace framework\web\components;

use framework\Application;
use framework\blaze\interfaces\RootContext;
use framework\Component;
use framework\web\widgets\Widget;

class WidgetManager extends Component
{
    protected RootContext $ctx;

    public function __construct()
    {
        $this->ctx = new RootContext();
    }

    public function get($widget, $params)
    {
        $segs = explode('.', $widget);

        $cls = $this->path($segs);

        // Add the class name
        $cls .= '\\' . $segs[count($segs) - 1];

        $obj = new $cls();

        // Register params
        foreach ($params as $param => $value) {
            $param = str_replace('-', '_', $param);

            $obj->$param = $value;
        }

        // Register Assets
        $this->registerAssets($obj);

        return $obj;
    }

    public function begin($widget)
    {
        $widget->begin($this->ctx);
    }

    /**
     * Renders a widget and returns its
     * HTML representation as string.
     * Automatically registers any required
     * assets
     * 
     * @param string $widget Name of the widget
     * @return string Rendered widget
     */
    public function render($widget, $params, $content)
    {
        if ($widget instanceof Widget) {
            $widget->content = $content;
            return $widget->run($this->ctx);
        }
        if (!empty($content)) {
            $params['content'] = $content;
        }

        $obj = $this->get($widget, $params);

        return $obj->run();
    }

    public function end($widget)
    {
        $widget->end($this->ctx);
    }

    protected function registerAssets(Widget $widget): void
    {
        $assets = Application::get()->assets;

        foreach ($widget->css as $css) {
            $assets->addCss($css);
        }

        foreach ($widget->js as $js) {
            $assets->addScript($js);
        }
    }

    protected function path($segments)
    {
        $result = '\\app\\resources\\widgets\\';

        foreach ($segments as $segment) {
            $result .= $segment . '\\';
        }

        return rtrim($result, '\\');
    }
}
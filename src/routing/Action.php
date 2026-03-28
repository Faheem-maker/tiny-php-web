<?php

framework\web\routing;

use framework\contracts\ActionInterface;

class Action implements ActionInterface
{
    public function __construct(protected array $action)
    {

    }

    public function execute(array $params)
    {
        if (is_callable($this->action)) {
            return call_user_func($this->action, $params);
        } else if (is_array($this->action)) {
            $class = $this->action[0];
            $method = $this->action[1];
            $instance = new $class();
            return $instance->$method($params);
        } else {
            throw new \Exception("Invalid action");
        }
    }
}
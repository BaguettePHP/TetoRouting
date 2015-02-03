<?php
namespace Teto\Routing;

class Router
{
    /** @var \Teto\Routing\Action[] */
    public $actions = [];

    /** @var array */
    public $error_action = [];

    public function __construct(array $route_map)
    {
        foreach ($route_map as $k => $m) {
            is_numeric($k)
                ? $this->setAction($m)
                : $this->setSpecialAction($k, $m);
        }
    }

    public function match($method, $path)
    {
        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));

        foreach ($this->actions as $action) {
            if ($matched = $action->match($method, $split_path)) {
                return $matched;
            }
        }

        return new Action([$method], $split_path, [], $this->error_action['#404']);
    }

    /**
     * @param array $action_tuple
     */
    public function setAction(array $action_tuple)
    {
        $method = array_shift($action_tuple);
        $path   = array_shift($action_tuple);
        $value  = array_shift($action_tuple) ?: true ;
        $params = array_shift($action_tuple) ?: [] ;

        $this->actions[] = Action::create($method, $path, $value, $params);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setSpecialAction($name, $value)
    {
        $this->error_action[$name] = $value;
    }
}

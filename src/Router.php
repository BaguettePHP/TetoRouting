<?php
namespace Teto\Routing;

/**
 * Router
 *
 * @package    Teto\Routing
 * @author     USAMI Kenta <tadsan@zonu.me>
 * @copyright  2015 USAMI Kenta
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
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

    /**
     * @param   string $method
     * @param   string $path
     * @return  \Teto\Routing\Action
     */
    public function match($method, $path)
    {
        if (strpos($path, '//') !== false) {
            return $this->getNotFoundAction($method, $path);
        }

        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));
        $count = count($split_path);

        if (empty($this->actions[$count])) {
            return $this->getNotFoundAction($method, $path);
        }

        foreach ($this->actions[$count] as $action) {
            if ($matched = $action->match($method, $split_path)) {
                return $matched;
            }
        }

        return $this->getNotFoundAction($method, $path);
    }

    /**
     * @param   string $method
     * @param   string $path
     * @return  \Teto\Routing\Action
     */
    public function getNotFoundAction($method, $path)
    {
        $split_path = explode('/', $path);
        array_shift($split_path);

        return new Action(
            [$method],
            $split_path,
            [],
            $this->error_action['#404']
        );
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
        $action = Action::create($method, $path, $value, $params);
        $count  = count($action->split_path);

        if (!isset($this->actions[$count])) {
            $this->actions[$count] = [];
        }

        $this->actions[$count][] = $action;
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

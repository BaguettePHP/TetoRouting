<?php

namespace Teto\Routing;

/**
 * Router
 *
 * @author    USAMI Kenta <tadsan@zonu.me>
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */
class Router
{
    const _ext = '?ext';
    const _sep = "\x1E";

    /** @var \Teto\Routing\Action[] */
    public $variable_actions = [];

    /** @var \Teto\Routing\Action[][] */
    public $fixed_actions = [];

    /** @var \Teto\Routing\Action[] */
    public $named_actions = [];

    /** @var array */
    public $error_action = [];

    public function __set($name, $value)
    {
        throw new \OutOfRangeException("Unexpected key:'$name'");
    }

    /**
     * @param  array  $route_map
     * @param  string $method
     * @param  string $path
     * @return \Teto\Routing\Action
     */
    public static function dispatch(array $route_map, $method, $path)
    {
        return (new Router($route_map))->match($method, $path);
    }

    /**
     * @param array $route_map
     */
    public function __construct(array $route_map)
    {
        foreach ($route_map as $k => $m) {
            ($k !== '#404')
                ? $this->setAction($k, $m)
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
        if ($method === 'HEAD') { $method = 'GET'; }
        if (strpos($path, '//') !== false || strpos($path, self::_sep) !== false) {
            return $this->getNotFoundAction($method, $path);
        }

        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));
        $count = count($split_path);

        $ext  = '';

        if ($count > 0) {
            $file = explode('.', $split_path[$count - 1], 2);
            if (isset($file[1]) && strlen($file[1]) > 0) {
                if (strlen($file[1]) > 0) {
                    list($split_path[$count - 1], $ext) = $file;
                } else {
                    $split_path[$count - 1] .= '.';
                }
            }
        }

        $fixed_key = implode(self::_sep, $split_path);
        if (isset($this->fixed_actions[$fixed_key][$method])) {
            $action = $this->fixed_actions[$fixed_key][$method];
            if ($matched = $action->match($method, $split_path, $ext)) {
                return $matched;
            }
        }

        if (isset($this->variable_actions[$count])) {
            foreach ($this->variable_actions[$count] as $action) {
                if ($matched = $action->match($method, $split_path, $ext)) {
                    return $matched;
                }
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
        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));

        return new NotFoundAction(
            [$method],
            $split_path,
            [],
            [],
            $this->error_action['#404']
        );
    }

    /**
     * @param int|string $key
     * @param array      $action_tuple
     */
    public function setAction($key, array $action_tuple)
    {
        if (isset($action_tuple[self::_ext])) {
            $ext = $action_tuple[self::_ext];
            unset($action_tuple[self::_ext]);
        } else {
            $ext = [];
        }

        $method = array_shift($action_tuple);
        $path   = array_shift($action_tuple);
        $value  = array_shift($action_tuple) ?: true ;
        $params = array_shift($action_tuple) ?: [] ;
        $action = Action::create($method, $path, $value, $ext, $params);

        if (!empty($action->param_pos)) {
            $count  = count($action->split_path);
            if (!isset($this->variable_actions[$count])) {
                $this->variable_actions[$count] = [];
            }
            $this->variable_actions[$count][] = $action;
        } else {
            $fixed_key = implode(self::_sep, $action->split_path);
            foreach ($action->methods as $m) {
                $this->fixed_actions[$fixed_key][$m] = $action;
            }
        }

        if (!is_numeric($key)) {
            $this->named_actions[$key] = $action;
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setSpecialAction($name, $value)
    {
        $this->error_action[$name] = $value;
    }

    /**
     * @param string  $name
     * @param array   $param
     * @param boolean $strict
     */
    public function makePath($name, array $param = [], $strict = false)
    {
        if (empty($this->named_actions[$name])) {
            throw new \OutOfRangeException("\"$name\" is not exists.");
        }

        if (isset($param[self::_ext])) {
            $ext = $param[self::_ext];
            unset($param[self::_ext]);
        } else {
            $ext = null;
        }

        return $this->named_actions[$name]->makePath($param, $ext, $strict);
    }
}

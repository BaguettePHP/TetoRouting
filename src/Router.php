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
    const _ext = '?ext';

    /** @var \Teto\Routing\Action[] */
    public $actions = [];

    /** @var array */
    public $error_action = [];

    /**
     * @param array $route_map
     * @param array $options
     */
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

        if (empty($this->actions[$count])) {
            return $this->getNotFoundAction($method, $path, $ext);
        }

        foreach ($this->actions[$count] as $action) {
            if ($matched = $action->match($method, $split_path, $ext)) {
                return $matched;
            }
        }

        return $this->getNotFoundAction($method, $path, $ext);
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
            null,
            $this->error_action['#404']
        );
    }

    /**
     * @param array $action_tuple
     */
    public function setAction(array $action_tuple)
    {
        if (isset($action_tuple[self::_ext])) {
            $ext = $action_tuple[self::_ext];
            unset($action_tuple[self::_ext]);
        } else {
            $ext = null;
        }

        $method = array_shift($action_tuple);
        $path   = array_shift($action_tuple);
        $value  = array_shift($action_tuple) ?: true ;
        $params = array_shift($action_tuple) ?: [] ;
        $action = Action::create($method, $path, $value, $ext, $params);
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

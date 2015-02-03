<?php
namespace Teto\Routing;

/**
 * Action object
 *
 * @package    Teto\Routing
 * @author     USAMI Kenta <tadsan@zonu.me>
 * @copyright  2015 USAMI Kenta
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
class Action
{
    use \Teto\Object\TypedProperty;

    private static $property_types = [
        'methods'    => 'enum[]',
        'split_path' => 'string[]',
        'param_pos'  => 'array',
        'value'      => 'mixed',
        'param'      => 'array',
    ];

    private static $enum_values = [
        'methods' => ['GET', 'POST'],
    ];

    /**
     * @param string[] $methods
     * @param string[] $split_path
     * @param array    $param_pos
     * @param mixed    $value
     */
    public function __construct(array $methods, array $split_path, array $param_pos, $value)
    {
        $this->methods    = $methods;
        $this->split_path = $split_path;
        $this->param_pos  = $param_pos;
        $this->value      = $value;
        $this->param      = [];
    }

    /**
     * @param  string   $request_method
     * @param  string[] $request_path
     * @return Action|false
     */
    public function match($request_method, array $request_path)
    {
        if (!in_array($request_method, $this->methods, true) ||
            count($request_path) !== count($this->split_path)) {
            return false;
        }

        if (empty($this->param_pos) && ($request_path === $this->split_path)) {
            return $this;
        }

        foreach ($this->split_path as $i => $p) {
            $q = $request_path[$i];

            if (isset($this->param_pos[$i])) {
                if (!preg_match($p, $q, $matches)) {
                    $this->param = [];
                    return false;
                }

                $k = $this->param_pos[$i];
                $param_tmp = $this->param;
                $param_tmp[$k] = isset($matches[1]) ? $matches[1] : $matches[0];
                $this->param = $param_tmp;
            } elseif ($q !== $p) {
                $this->param = [];
                return false;
            }
        }

        return $this;
    }

    /**
     * @param  string $method_str ex. "GET|POST"
     * @param  string $path       ex. "/dirname/path"
     * @param  mixed  $value
     * @param  array  $params
     * @return Action new instance object
     */
    public static function create($method_str, $path, $value, array $params = [])
    {
        $methods = explode('|', $method_str);
        list($split_path, $param_pos) = self::parsePathParam($path, $params);

        return new Action($methods, $split_path, $param_pos, $value);
    }

    /**
     * @param  string $path
     * @param  array  $params
     * @return array  [$split_path, $param_pos]
     */
    public static function parsePathParam($path, array $params)
    {
        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));

        if (!$params) { return [$split_path, []]; }

        $new_split_path = [];
        $param_pos = [];
        foreach ($split_path as $i => $p) {
            $variable = null;

            if (strpos($p, ':') !== false) {
                $v = substr($p, 1);
                if (isset($params[$v])) { $variable = $v; }
            }

            if ($variable === null) {
                $new_split_path[] = $p;
            } else {
                $param_pos[$i]    = $variable;
                $new_split_path[] = $params[$v];
            }
        }

        return [$new_split_path, $param_pos];
    }

    /**
     * @param string[] $methods ex. ['GET', 'POST', 'PUT', 'DELETE']
     */
    public static function setHTTPMethod(array $methods)
    {
        self::$enum_values['methods'] = $methods;
    }
}

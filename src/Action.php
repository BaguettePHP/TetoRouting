<?php
namespace Teto\Routing;

/**
 * Action object
 *
 * @package    Teto\Routing
 * @author     USAMI Kenta <tadsan@zonu.me>
 * @copyright  2015 USAMI Kenta
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 *
 * @property-read string[] $methods
 * @property-read string[] $split_path
 * @property-read array    $param_pos
 * @property-read mixed    $value
 * @property-read string   $extension
 * @property-read boolean  $is_wildcard
 * @property-read string[] $available_extensions
 */
class Action
{
    use \Teto\Object\TypeAssert;
    const WILDCARD = '*';

    /** @var string[] */
    public $methods;
    /** @var string[] */
    public $split_path;
    /** @var array */
    public $param_pos;
    /** @var mixed */
    public $value;
    /** @var string */
    public $extension;
    /** @var string */
    public $is_wildcard;
    /** @var array */
    public $available_extensions;

    private static $enum_values = [
        'methods' => array('GET', 'POST'),
    ];

    /**
     * @param string[] $methods
     * @param string[] $split_path
     * @param array    $param_pos
     * @param string[] $extension
     * @param mixed    $value
     */
    public function __construct(array $methods, array $split_path, array $param_pos, array $available_extensions, $value)
    {
        static::assertMethods($methods);

        $this->methods     = $methods;
        $this->split_path  = $split_path;
        $this->param_pos   = $param_pos;
        $this->value       = $value;
        $this->param       = array();
        $this->is_wildcard = in_array(self::WILDCARD, $available_extensions, true);
        $this->available_extensions
            = empty($available_extensions) ? array('' => true)
            : array_fill_keys($available_extensions, true) ;
    }

    /**
     * @param  string   $request_method
     * @param  string[] $request_path
     * @param  string   $extension
     * @return Action|false
     */
    public function match($request_method, array $request_path, $extension)
    {
        $request_len = count($request_path);

        if (!in_array($request_method, $this->methods, true) ||
            $request_len !== count($this->split_path)) {
            return false;
        }

        if ($this->available_extensions === array('' => true)) {
            if (strlen($extension) > 0) {
                $request_path[$request_len - 1] .= '.' . $extension;
            }
            $extension = '';
        }

        if ($this->matchExtension($extension)) {
            $this->extension = $extension;
        } else {
            return false;
        }

        if (empty($this->param_pos) && ($request_path === $this->split_path)) {
            return $this;
        }

        foreach ($this->split_path as $i => $p) {
            $q = $request_path[$i];

            if (isset($this->param_pos[$i])) {
                if (!preg_match($p, $q, $matches)) {
                    $this->param = array();
                    return false;
                }

                $k = $this->param_pos[$i];
                $param_tmp = $this->param;
                $param_tmp[$k] = isset($matches[1]) ? $matches[1] : $matches[0];
                $this->param = $param_tmp;
            } elseif ($q !== $p) {
                $this->param = array();
                return false;
            }
        }

        return $this;
    }

    /**
     * @param  string  $extension
     * @return boolean
     */
    public function matchExtension($extension)
    {
        if (isset($this->available_extensions[$extension])) {
            return true;
        } else {
            return $this->is_wildcard && $extension !== '';
        }
    }

    /**
     * @param  array   $param
     * @param  string  $ext
     * @param  boolean $strict
     * @return string
     */
    public function makePath(array $param, $ext, $strict)
    {
        $path = '';

        if ($strict) {
            $got_keys = array_keys($param);
            $expects  = array_values($this->param_pos);
            $diff     = array_diff($got_keys, $expects);

            if ($diff !== array()) {
                $json = json_encode(array_values($diff));
                throw new \DomainException('unnecessary parameters: ' . $json);
            }
        }

        foreach ($this->split_path as $i => $pattern) {
            if (!isset($this->param_pos[$i])) {
                $path .= '/' . $pattern;
                continue;
            }

            $name = $this->param_pos[$i];

            if (!isset($param[$name]) || !preg_match($pattern, $param[$name], $matches)) {
                throw new \DomainException("Error");
            }

            $path .= '/' . $param[$name];
        }

        if ($ext !== null && $ext !== '') {
            $path .= '.' . $ext;
        }

        return ($path === '') ? '/' : $path;
    }

    /**
     * @param  string   $method_str ex. "GET|POST"
     * @param  string   $path       ex. "/dir_name/path"
     * @param  mixed    $value
     * @param  string[] $ext
     * @param  array    $params
     * @return Action new instance object
     */
    public static function create($method_str, $path, $value, array $ext, array $params = array())
    {
        $methods = explode('|', $method_str);
        list($split_path, $param_pos)
            = self::parsePathParam($path, $params);

        return new Action($methods, $split_path, $param_pos, $ext, $value);
    }

    /**
     * @param  string $path
     * @param  array  $params
     * @return array  [$split_path, $param_pos]
     */
    public static function parsePathParam($path, array $params)
    {
        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));

        if (!$params) { return array($split_path, array()); }

        $new_split_path = array();
        $param_pos = array();
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

        return array($new_split_path, $param_pos);
    }

    /**
     * @param string[] $methods ex. ['GET', 'POST', 'PUT', 'DELETE']
     */
    public static function setHTTPMethod(array $methods)
    {
        self::$enum_values['methods'] = $methods;
    }

    protected static function assertMethods(array $methods)
    {
        foreach ($methods as $m) {
            self::assertValue('enum', 'methods', $m, false);
        }
    }
}

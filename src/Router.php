<?php

namespace Teto\Routing;

use function array_filter;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_numeric;
use function is_string;
use function str_contains;

/**
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */
class Router
{
    private const string _ext = '?ext';

    private const string _sep = "\x1E";

    /** @var array<int, list<Action>> */
    public array $variable_actions = [];

    /** @var array<string, array<string, Action>> */
    public array $fixed_actions = [];

    /** @var array<string, Action> */
    public array $named_actions = [];

    /** @var array<string, mixed> */
    public array $error_action = [];

    public function __set(string $name, mixed $value): void
    {
        throw new \OutOfRangeException("Unexpected key:'$name'");
    }

    /**
     * @param array<int|string, array{0: non-empty-string, 1: non-empty-string, 2?: mixed, 3?: array<string, string>, '?ext'?: list<string>}|string> $route_map
     */
    public static function dispatch(array $route_map, string $method, string $path): Action
    {
        return (new Router($route_map))->match($method, $path);
    }

    /**
     * @param array<int|string, array{0: non-empty-string, 1: non-empty-string, 2?: mixed, 3?: array<string, string>, '?ext'?: list<string>}|string> $route_map
     */
    public function __construct(array $route_map)
    {
        foreach ($route_map as $k => $m) {
            if ($k !== '#404') {
                if (!is_array($m)) {
                    throw new \TypeError('Route definitions must be arrays.');
                }
                $this->setAction($k, $m);
            } else {
                $this->setSpecialAction($k, $m);
            }
        }
    }

    public function match(string $method, string $path): Action
    {
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        if (str_contains($path, '//') || str_contains($path, self::_sep)) {
            return $this->getNotFoundAction($method, $path);
        }

        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));
        $count = count($split_path);
        $not_found_path = $split_path;

        $ext = '';

        if ($count > 0) {
            $file = explode('.', $split_path[$count - 1], 2);
            if (isset($file[1]) && strlen($file[1]) > 0) {
                [$split_path[$count - 1], $ext] = $file;
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
            $variable_actions = $this->variable_actions[$count];
            $filter_static_segments = count($variable_actions) > 1;
            foreach ($variable_actions as $action) {
                if ($filter_static_segments && $ext === '' && $action->param === [] && $action->extension === '') {
                    foreach ($action->split_path as $position => $segment) {
                        if (!isset($action->param_pos[$position]) && $segment !== $split_path[$position]) {
                            continue 2;
                        }
                    }
                }
                if ($matched = $action->match($method, $split_path, $ext)) {
                    return $matched;
                }
            }
        }

        return $this->createNotFoundAction($method, $not_found_path);
    }

    public function getNotFoundAction(string $method, string $path): Action
    {
        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));

        return $this->createNotFoundAction($method, $split_path);
    }

    /** @param list<string> $split_path */
    private function createNotFoundAction(string $method, array $split_path): Action
    {
        return new NotFoundAction(
            [$method],
            $split_path,
            [],
            [],
            $this->error_action['#404']
        );
    }

    /**
     * @param array{0: non-empty-string, 1: non-empty-string, 2?: mixed, 3?: array<string, string>, '?ext'?: list<string>} $action_tuple
     */
    public function setAction(int|string $key, array $action_tuple): void
    {
        if (isset($action_tuple[self::_ext])) {
            $ext = $action_tuple[self::_ext];
            unset($action_tuple[self::_ext]);
        } else {
            $ext = [];
        }

        $method = $action_tuple[0];
        $path = $action_tuple[1];
        $value = ($action_tuple[2] ?? null) ?: true;
        $params = $action_tuple[3] ?? [];
        $action = Action::create($method, $path, $value, $ext, $params);

        if (!empty($action->param_pos)) {
            $count = count($action->split_path);
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

    public function setSpecialAction(string $name, mixed $value): void
    {
        $this->error_action[$name] = $value;
    }

    /**
     * @param array<string, int|string> $param
     */
    public function makePath(string $name, array $param = [], bool $strict = false): string
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

        if ($ext !== null && !is_string($ext)) {
            throw new \TypeError('The extension parameter must be a string or null.');
        }

        return $this->named_actions[$name]->makePath($param, $ext, $strict);
    }
}

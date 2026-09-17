<?php

namespace Teto\Routing;

use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_values;
use function count;
use function explode;
use function get_debug_type;
use function implode;
use function in_array;
use function preg_match;
use function sprintf;
use function str_contains;
use function strlen;
use function substr;

/**
 * Action object
 *
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */
class Action
{
    private const string WILDCARD = '*';

    /** @var array<int, string> */
    public array $methods;

    /** @var array<int, string> */
    public array $split_path;

    /** @var array<int, string> */
    public array $param_pos;

    public mixed $value;

    /** @var array<string, string> */
    public array $param;

    public string $extension;

    public bool $is_wildcard;

    /** @var array<string,bool> */
    public array $available_extensions;

    /** @var non-empty-list<string> */
    private static array $allowed_methods = ['GET', 'POST'];

    /**
     * @param array<int, string> $methods
     * @param array<int, string> $split_path
     * @param array<int, string> $param_pos
     * @param array<int, string> $available_extensions
     */
    public function __construct(array $methods, array $split_path, array $param_pos, array $available_extensions, mixed $value)
    {
        static::assertMethods($methods);

        $this->methods = $methods;
        $this->split_path = $split_path;
        $this->param_pos = $param_pos;
        $this->value = $value;
        $this->param = [];
        $this->extension = '';
        $this->is_wildcard = in_array(self::WILDCARD, $available_extensions, true);
        $this->available_extensions = match (count($available_extensions)) {
            0 => [
                '' => true,
            ],
            default => array_fill_keys($available_extensions, true),
        };
    }

    /**
     * @param  array<int, string> $request_path
     */
    public function match(string $request_method, array $request_path, string $extension): ?Action
    {
        $request_len = count($request_path);

        if (!in_array($request_method, $this->methods, true) ||
            $request_len !== count($this->split_path)) {
            return null;
        }

        if ($this->available_extensions === [
            '' => true,
        ]) {
            if (strlen($extension) > 0) {
                $request_path[$request_len - 1] .= '.' . $extension;
            }
            $extension = '';
        }

        if ($this->matchExtension($extension)) {
            $this->extension = $extension;
        } else {
            return null;
        }

        if ($this->param_pos === [] && $request_path === $this->split_path) {
            return $this;
        }

        foreach ($this->split_path as $i => $p) {
            $q = $request_path[$i];

            if (isset($this->param_pos[$i])) {
                if (!preg_match($p, $q, $matches)) {
                    $this->param = [];
                    return null;
                }

                $k = $this->param_pos[$i];
                $param_tmp = $this->param;
                $param_tmp[$k] = $matches[1] ?? $matches[0]; // @phpstan-ignore offsetAccess.invalidOffset
                $this->param = $param_tmp;
            } elseif ($q !== $p) {
                $this->param = [];
                return null;
            }
        }

        return $this;
    }

    public function matchExtension(string $extension): bool
    {
        if (isset($this->available_extensions[$extension])) {
            return true;
        } else {
            return $this->is_wildcard && $extension !== '';
        }
    }

    /**
     * @param array<string, int|string> $param
     */
    public function makePath(array $param, ?string $ext, bool $strict): string
    {
        $path = '';

        if ($strict) {
            $got_keys = array_keys($param);
            $expects = $this->param_pos;
            $diff = array_diff($got_keys, $expects);

            if ($diff !== []) {
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

            if (!isset($param[$name]) || !preg_match($pattern, (string)$param[$name], $matches)) {
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
     * @param non-empty-string $method_str ex. "GET|POST"
     * @param non-empty-string $path ex. "/dir_name/path"
     * @param array<int, string> $ext
     * @param array<string, string> $params
     */
    public static function create(string $method_str, string $path, mixed $value, array $ext, array $params = []): Action
    {
        $methods = explode('|', $method_str);
        [$split_path, $param_pos] = self::parsePathParam($path, $params);

        return new Action($methods, $split_path, $param_pos, $ext, $value);
    }

    /**
     * @param array<string, string> $params
     * @return list{array<int, string>, array<int, string>}
     */
    public static function parsePathParam(string $path, array $params): array
    {
        $split_path = array_values(array_filter(explode('/', $path), 'strlen'));

        $new_split_path = [];
        $param_pos = [];
        foreach ($split_path as $i => $p) {
            $variable = null;

            if (str_contains($p, ':')) {
                $v = substr($p, 1);
                if (isset($params[$v])) {
                    $variable = $v;
                }
            }

            if ($variable === null) {
                $new_split_path[] = $p;
            } else {
                $param_pos[$i] = $variable;
                $new_split_path[] = $params[$v];
            }
        }

        return [$new_split_path, $param_pos];
    }

    /**
     * @param non-empty-list<string> $methods ex. ['GET', 'POST', 'PUT', 'DELETE']
     */
    public static function setHTTPMethod(array $methods): void
    {
        self::$allowed_methods = $methods;
    }

    /**
     * @param array<int, string> $methods
     * @throws \InvalidArgumentException
     */
    protected static function assertMethods(array $methods): void
    {
        foreach ($methods as $m) {
            if (!in_array($m, self::$allowed_methods, true)) {
                $message = sprintf('got $methods as %s (expects [%s])', get_debug_type($m), implode(', ', self::$allowed_methods));
                throw new \InvalidArgumentException($message);
            }
        }
    }
}

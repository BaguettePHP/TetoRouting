<?php

namespace Teto\Routing;

use function is_array;
use function is_string;
use function ord;
use function sprintf;
use function strlen;
use function substr;

/**
 * 共通接頭辞木構造を連想配列で実装したRouter
 *
 * @copyright 2015 Yusuke Koashi
 * @license MIT
 * @see https://gist.github.com/neo-nanikaka/c2e2f7742b311696d50b
 * @see http://inside.pixiv.net/entry/2015/12/13/145741
 */
final class CommonPrefixTrieRouter
{
    private const URL_PARAMETER_TYPE_NUM = '[';

    private const URL_PARAMETER_TYPE_STRING = ']';

    /** ルーティングが存在するノードにおいて、値はこのキーで引く */
    private const VALID_STATE_MARK = '>';

    /** URLパラメータがあった場合、このキーで引く */
    private const URL_PARAMETER_NAME = 'name';

    /**
     * ルーティング決定のための探索を行う
     *
     * @param array<string, array<string, mixed>> $trie 指定の形式の連想配列
     * @param string $request_uri 解析したいURL
     * @param string $http_method HTTPメソッド
     *
     * @return array{value: mixed, params: array<int|string, string>}|null
     *   `value` はルーティングの結果、`params` はURLパラメータの値。
     */
    public static function search($trie, $request_uri, $http_method): ?array
    {
        $p = $trie[$http_method];

        $length = strlen($request_uri);
        $i = 0;
        $ok = (0 < $length); // request_uriが空文字列だった場合にnullを返せるように
        $result = []; // URLパラメータの値を記憶しておく変数
        while ($i < $length) {
            if ($request_uri[$i] !== '/') {
                $ok = false;
                break;
            }
            $str = '' . $request_uri[$i++];
            $num_only = true;
            while ($i < $length && $request_uri[$i] !== '/') {
                $str .= $request_uri[$i];
                $x = ord($request_uri[$i]);
                $num_only &= (48 <= $x && $x <= 57);
                $i++;
            }
            if (isset($p[$str]) && is_array($p[$str])) {
                $p = $p[$str];
            } elseif ($num_only && isset($p[self::URL_PARAMETER_TYPE_NUM]) && is_array($p[self::URL_PARAMETER_TYPE_NUM])) {
                $parameter_node = $p[self::URL_PARAMETER_TYPE_NUM];
                if (!isset($parameter_node[self::URL_PARAMETER_NAME]) || !is_string($parameter_node[self::URL_PARAMETER_NAME])) {
                    $ok = false;
                    break;
                }
                $p = $parameter_node;
                $result[$parameter_node[self::URL_PARAMETER_NAME]] = substr($str, 1);
            } elseif (isset($p[self::URL_PARAMETER_TYPE_STRING]) && is_array($p[self::URL_PARAMETER_TYPE_STRING])) {
                $parameter_node = $p[self::URL_PARAMETER_TYPE_STRING];
                if (!isset($parameter_node[self::URL_PARAMETER_NAME]) || !is_string($parameter_node[self::URL_PARAMETER_NAME])) {
                    $ok = false;
                    break;
                }
                $p = $parameter_node;
                $result[$parameter_node[self::URL_PARAMETER_NAME]] = substr($str, 1);
            } else {
                $ok = false;
                break;
            }
        }
        return match ($ok && isset($p[self::VALID_STATE_MARK])) {
            true => ['value' => $p[self::VALID_STATE_MARK], 'params' => $result],
            false => null,
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function createTrie(): array
    {
        return [];
    }

    /**
     * Trie木を表現した連想配列を構築する
     *
     * 動的に木を組み立てるために参照 & を多用している
     *
     * @param list<list{string, string, mixed, 3?: array<string, string>}> $conf
     * @return array<string, array<string, mixed>>
     */
    public static function trieConstruction(array $conf)
    {
        $trie = self::createTrie();
        foreach ($conf as $con) {
            $http_method = $con[0];
            $path = $con[1];
            $value = $con[2];
            $param_mapping = isset($con[3]) ? $con[3] : [];

            if (!isset($trie[$http_method])) {
                $trie[$http_method] = [];
            }

            $node = &$trie[$http_method];

            $path_length = strlen($path);
            $i = 0;
            while ($i < $path_length) {
                if ($path[$i++] !== '/') {
                    throw new \Exception(sprintf("不正なパスが設定されています %s", $path));
                }
                $partial_path = '/';
                while ($i < $path_length && $path[$i] !== '/') {
                    $partial_path .= $path[$i++];
                }

                $is_url_parameter = false;
                $url_param_name = null;
                // URLパラメータだった場合
                if (1 < strlen($partial_path) && $partial_path[1] === ':') {
                    $is_url_parameter = true;
                    $url_param_name = substr($partial_path, 2);
                    if (!isset($param_mapping[$url_param_name])) {
                        throw new \Exception(sprintf("URLパラメータ :%s に対する設定が足りません", $url_param_name));
                    }
                    $partial_path = $param_mapping[$url_param_name];
                }

                if (!isset($node[$partial_path])) {
                    if ($is_url_parameter) {
                        $node[$partial_path] = [self::URL_PARAMETER_NAME => $url_param_name];
                    } else {
                        $node[$partial_path] = [];
                    }
                } else {
                    $existing_node = $node[$partial_path];
                    if (!is_array($existing_node)) {
                        throw new \Exception(sprintf("不正なTrieノードです %s", $path));
                    }
                    $existing_param_name = $existing_node[self::URL_PARAMETER_NAME] ?? '';
                    if (!is_string($existing_param_name)) {
                        $existing_param_name = '';
                    }
                    if ($is_url_parameter && $existing_param_name !== $url_param_name) {
                        throw new \Exception(sprintf("URLパラメータに別名をつけようとしています %s (:%s, :%s)", $path, $url_param_name, $existing_param_name));
                    }
                }
                if (!is_array($node[$partial_path])) {
                    throw new \Exception(sprintf("不正なTrieノードです %s", $path));
                }
                $node = &$node[$partial_path];
            }
            if (isset($node[self::VALID_STATE_MARK])) {
                throw new \Exception(sprintf("重複したルーティングルールがあります %s", $path));
            }
            $node[self::VALID_STATE_MARK] = $value;
        }

        return $trie;
    }
}

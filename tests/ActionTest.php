<?php

namespace Teto\Routing;

/**
 * @author    USAMI Kenta <tadsan@zonu.me>
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */
final class ActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderFor_match
     */
    public function test_match(
        $expected,
        $param,
        array $methods,
        array $split_path,
        $extension,
        array $param_pos,
        array $request
    ) {
        $action = new Action($methods, $split_path, $param_pos, $extension, 'matched!');
        $actual = $action->match($request['method'], $request['path'], $request['ext']);

        if (isset($param_pos['?ext'])) { unset($param_pos['?ext']); }

        $this->assertSame($expected, $actual !== false);
        $this->assertSame($action->param, $param);

        if ($expected) {
            $this->assertInstanceOf('\Teto\Routing\Action', $actual);
            $this->assertSame($action, $actual);
        }
    }

    public function dataProviderFor_match()
    {
        return [
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => [],
                'extension'  => [],
                'param_pos'  => [],
                'request'    => ['method' => 'GET', 'path' => ['foo'], 'ext' => ''],
            ],
            [
                'expected'   => true,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => [],
                'extension'  => [],
                'param_pos'  => [],
                'request'    => ['method' => 'GET', 'path' => [], 'ext' => ''],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => [],
                'param_pos'  => [],
                'request'    => ['method' => 'GET', 'path' => [], 'ext' => ''],
            ],
            [
                'expected'   => true,
                'param'      => ['id' => '1'],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => [],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '1'], 'ext' => ''],
            ],
            [
                'expected'   => true,
                'param'      => ['id' => '1'],
                'methods'    => ['GET', 'POST'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => [],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '1'], 'ext' => ''],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['POST'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => [],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '1'], 'ext' => ''],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => [],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', 'a'], 'ext' => ''],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => ['users', '/\A(\d+)\.json\z/'],
                'extension'  => [],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '1234'], 'ext' => ''],
            ],
            [
                'expected'   => true,
                'param'      => ['id' => '1234'],
                'methods'    => ['GET'],
                'split_path' => ['users', '/\A(\d+)\.json\z/'],
                'extension'  => [],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '1234'], 'ext' => 'json'],
            ],
            [
                'expected'   => true,
                'param'      => ['id' => '0401'],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => ['', 'jpg'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '0401'], 'ext' => 'jpg'],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => ['', 'jpg'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '0401'], 'ext' => 'png'],
            ],
            [
                'expected'   => true,
                'param'      => ['id' => '0401'],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => ['jpg', 'gif'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '0401'], 'ext' => 'jpg'],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => ['jpg', 'gif'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '0401'], 'ext' => 'png'],
            ],
            [
                'expected'   => true,
                'param'      => ['id' => '0401'],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => ['*'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '0401'], 'ext' => 'png'],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension'  => ['jpg', 'gif'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '0401'], 'ext' => ''],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderFor_parsePathParam
     */
    public function test_parsePathParam($expected, $path, $params)
    {
        $this->assertEquals($expected, Action::parsePathParam($path, $params));
    }

    public function dataProviderFor_parsePathParam()
    {
        return [
            [
                'expected' => [[], []],
                'path'     => '/',
                'params'   => [],
            ],
            [
                'expected' => [['login'], []],
                'path'     => '/login',
                'params'   => [],
            ],
            [
                'expected' => [
                    ['user', '/(@[-A-Za-z]{3,15})/', 'works'],
                    [1 => 'name'],
                ],
                'path'     => '/user/:name/works',
                'params'   => ['name' => '/(@[-A-Za-z]{3,15})/'],
            ],
            [
                'expected' => [['login'], []],
                'path'     => '/login',
                'params'   => [],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderFor_test_makePath
     */
    public function test_makePath($expected, $split_path, $param_pos, $param, $ext, $strict)
    {
        $action = new Action(['GET'], $split_path, $param_pos, [], "returns!");
        $actual = $action->makePath($param, $ext, $strict);

        $this->assertEquals($expected, $actual);
    }

    public function dataProviderFor_test_makePath()
    {
        return [
            [
                'expected'   => "/a/12/d",
                'split_path' => ['a', '(^\d+$)', 'd'],
                'param_pos'  => [1 => 'b'],
                'param'      => ['b' => 12],
                'ext'        => null,
                'strict'     => false,
            ],
        ];
    }
}

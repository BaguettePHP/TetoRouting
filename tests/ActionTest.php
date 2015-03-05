<?php
namespace Teto\Routing;

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
}

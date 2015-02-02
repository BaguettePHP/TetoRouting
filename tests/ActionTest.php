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
        array $param_pos,
        array $request
    ) {
        $action = new Action($methods, $split_path, $param_pos, 'matched!');
        $actual = $action->match($request['method'], $request['path']);

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
                'param_pos'  => [],
                'request'    => ['method' => 'GET', 'path' => ['foo']],
            ],
            [
                'expected'   => true,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => [],
                'param_pos'  => [],
                'request'    => ['method' => 'GET', 'path' => []],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'param_pos'  => [],
                'request'    => ['method' => 'GET', 'path' => []],
            ],
            [
                'expected'   => true,
                'param'      => ['id' => '1'],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '1']],
            ],
            [
                'expected'   => true,
                'param'      => ['id' => '1'],
                'methods'    => ['GET', 'POST'],
                'split_path' => ['users', '/(\d+)/'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '1']],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['POST'],
                'split_path' => ['users', '/(\d+)/'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', '1']],
            ],
            [
                'expected'   => false,
                'param'      => [],
                'methods'    => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'param_pos'  => [1 => 'id'],
                'request'    => ['method' => 'GET', 'path' => ['users', 'a']],
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
                    [1 => 'name']
                ],
                'path'     => '/user/:name/works',
                'params'   => ['name' => '/(@[-A-Za-z]{3,15})/'],
            ],
        ];
    }
}

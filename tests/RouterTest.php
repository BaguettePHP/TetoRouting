<?php

namespace Teto\Routing;

/**
 * @author    USAMI Kenta <tadsan@zonu.me>
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */
final class RouterTest extends \PHPUnit_Framework_TestCase
{
    private static $router;
    private static $route_map;

    public static function setUpBeforeClass()
    {
        $re_user = '/^@([-A-Za-z]{3,15})$/';
        $re_id   = '/^\d+$/';
        self::$route_map = [
            'root' => ['GET', '/',        'index'],
            ['GET', '/:user',             'show_user',       ['user' => $re_user]],
            ['GET', '/:user/works',       'show_user_works', ['user' => $re_user]],
            'user_work' => ['GET', '/:user/works/:id',   'show_user_work',  ['user' => $re_user, 'id' => $re_id]],
            ['GET', '/et al.',            'etal'],
            ['GET', '/articles',          'article_index'],
            ['GET', '/articles/:id',      'article_page',    ['id' => $re_id]],
            'data' => ['GET', '/data',    'data_json',        '?ext' => ['', 'json']],
            'pdata' => ['POST', '/data',  'post_data_json',   '?ext' => ['', 'json']],
            'info' => ['GET', '/info',    'info_feed',        '?ext' => ['', 'rss', 'rdf', 'xml']],
            ['GET|POST', '/search/:word', 'search',          ['word' => '/^.{1,10}$/']],
             '#404' => 'Not Found!'
        ];

        self::$router = new Router(self::$route_map);
    }

    /**
     * @dataProvider dataProviderFor_match
     */
    public function test_match($method, $path, $expected_value, $expected_param)
    {
        $actual = self::$router->match($method, $path);
        $split_path = (strlen($path) === 1) ? [] : explode('/', substr($path, 1));

        $this->assertInstanceOf('\Teto\Routing\Action', $actual);
        $this->assertEquals($expected_value, $actual->value);
        $this->assertEquals($expected_param, $actual->param);
        $this->assertEquals(count($split_path), count($actual->split_path));

        if ($actual->param_pos) {
            foreach ($actual->split_path as $i => $path) {
                if (empty($actual->param_pos[$i])) {
                    $this->assertEquals($split_path[$i], $path);
                } else {
                    $this->assertRegExp($path, $split_path[$i]);
                }
            }
        } else {
            if ($actual->extension) {
                $last = count($split_path) - 1;
                $pattern = '/\.'. $actual->extension . '$/';
                $split_path[$last] = preg_replace($pattern, '', $split_path[$last]);
            }

            $this->assertEquals($split_path, $actual->split_path);
        }
    }

    public function dataProviderFor_match()
    {
        $not_found = 'Not Found!';

        return [
            ['GET', '/',                   'index',           []],
            ['POST', '/',                  $not_found,        []],
            ['PUT', '/',                   $not_found,        []],
            ['HEAD', '/',                  'index',           []],
            ['GET', '/foo',                $not_found,        []],
            ['DELETE', '/foo',             $not_found,        []],
            ['GET', '/@foo',               'show_user',       ['user' => 'foo']],
            ['GET', '/@foo.json',          $not_found,        []],
            ['GET', '/@foo/works',         'show_user_works', ['user' => 'foo']],
            ['HEAD', '/@foo/works',        'show_user_works', ['user' => 'foo']],
            ['POST', '/@foo/works',        $not_found,        []],
            ['GET', '/@foo/works/123',     'show_user_work',  ['user' => 'foo', 'id' => 123]],
            ['GET', '/@foo/works/abc',     $not_found,        []],
            ['GET', '/articles',           'article_index',   []],
            ['GET', '/data',               'data_json',       []],
            ['POST', '/data',              'post_data_json',  []],
            ['GET', '/data.',              $not_found,        []],
            ['GET', '/data.json',          'data_json',       []],
            ['GET', '/et al',              $not_found,        []],
            ['GET', '/et al.',             'etal',            []],
            ['GET', '/et al.json',         $not_found,        []],
            ['GET', '/et al..json',        $not_found,        []],
            ['GET', '/search/1234567890',  'search',          ['word' => '1234567890']],
            ['GET', '/search/12345678901', $not_found,        []],
        ];
    }

    /**
     * @dataProvider dataProviderFor_match
     */
    public function test_dispatch($method, $path, $expected_value, $expected_param)
    {
        $actual = Router::dispatch(self::$route_map, $method, $path);

        $this->assertEquals($expected_value, $actual->value);
        $this->assertEquals(self::$router->match($method, $path), $actual);
    }

    /**
     * @dataProvider dataProviderFor_makePath
     */
    public function test_makePath($expected, $name, array $param, $strict)
    {
        $this->assertEquals($expected, self::$router->makePath($name, $param, $strict));
    }

    public function dataProviderFor_makePath()
    {
        return [
            ['/',     'root', [],                    'strict' => false],
            ['/',     'root', ['dummy' => 'val'],    'strict' => false],
            ['/',     'root', [],                    'strict' => true],
            ['/data', 'data', [],                    'strict' => false],
            ['/data', 'data', [],                    'strict' => true],
            ['/info.rss', 'info', ['?ext' => 'rss'], 'strict' => true],
            ['/@john/works/12', 'user_work', ['user' => '@john', 'id' => 12], 'strict' => false],
            ['/@john/works/12', 'user_work', ['user' => '@john', 'id' => 12], 'strict' => true],
        ];
    }

    /**
     * @dataProvider dataProviderFor_makePath_throws_DomainException
     */
    public function test_makePath_throws_DomainException($expected, $name, array $param)
    {
        $this->setExpectedException('\DomainException', $expected);
        $this->assertEquals($expected, self::$router->makePath($name, $param, true));
    }

    public function dataProviderFor_makePath_throws_DomainException()
    {
        return [
            ['unnecessary parameters', 'root',      ['dummy' => 'val']],
            ['unnecessary parameters', 'data',      ['dummy' => 'val']],
            ['Error',                  'user_work', [] ],
            ['Error',                  'user_work', ['user'  => 'john'] ],
            ['Error',                  'user_work', ['user'  => '@john'] ],
            ['unnecessary parameters', 'user_work', ['dummy' => 'val']],
            ['unnecessary parameters', 'user_work', ['user'  => '@john', 'id' => 12, 'dummy' => 'val']],
        ];
    }
}

<?php
namespace Teto\Routing;

final class RouterTest extends \PHPUnit_Framework_TestCase
{
    private static $router;

    public static function setUpBeforeClass()
    {
        $re_user = '/^@([-A-Za-z]{3,15})$/';
        $re_id   = '/^\d+$/';
        $route_map = [
            ['GET', '/',                  'index'],
            ['GET', '/:user',             'show_user',       ['user' => $re_user]],
            ['GET', '/:user/works',       'show_user_works', ['user' => $re_user]],
            ['GET', '/:user/works/:id',   'show_user_work',  ['user' => $re_user, 'id' => $re_id]],
            ['GET', '/articles',          'article_index'],
            ['GET', '/articles/:id',      'article_page',    ['id' => $re_id]],
            ['GET', '/data',              'data_json',        '?ext' => ['', 'json']],
            ['GET|POST', '/search/:word', 'search',          ['word' => '/^.{1,10}$/']],
             '#404' => 'Not Found!'
        ];

        self::$router = new Router($route_map);
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
            ['GET', '/foo',                $not_found,        []],
            ['GET', '/@foo',               'show_user',       ['user' => 'foo']],
            ['GET', '/@foo.json',          $not_found,        []],
            ['GET', '/@foo/works',         'show_user_works', ['user' => 'foo']],
            ['GET', '/@foo/works/123',     'show_user_work',  ['user' => 'foo', 'id' => 123]],
            ['GET', '/@foo/works/abc',     $not_found,        []],
            ['GET', '/articles',           'article_index',   []],
            ['GET', '/data',               'data_json',       []],
            ['GET', '/data.',              $not_found,        []],
            ['GET', '/data.json',          'data_json',       []],
            ['GET', '/search/1234567890',  'search',          ['word' => '1234567890']],
            ['GET', '/search/12345678901', $not_found,        []],
        ];
    }
}

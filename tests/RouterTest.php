<?php
namespace Teto\Routing;

final class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function test_match()
    {
        $not_found = 'Not Found!';
        $re_user = '/^@([-A-Za-z]{3,15})/';
        $re_id   = '/^\d+/';
        $route_map = [
            ['GET', '/',                  'index'],
            ['GET', '/:user',             'show_user',       ['user' => $re_user]],
            ['GET', '/:user/works',       'show_user_works', ['user' => $re_user]],
            ['GET', '/:user/works/:id',   'show_user_work',  ['user' => $re_user, 'id' => $re_id]],
            ['GET', '/articles',          'article_index'],
            ['GET', '/articles/:id',      'article_page',    ['id' => $re_id]],
            ['GET|POST', '/search/:word', 'search',          ['word' => '/^.{1,10}$/']],
             '#404' => $not_found
        ];
        $router = new Router($route_map);

        $requests = [
            [$not_found,        'GET', '/foo'],
            ['show_user',       'GET', '/@foo'],
            ['show_user_works', 'GET', '/@foo/works'],
            ['show_user_work',  'GET', '/@foo/works/123'],
            [$not_found,        'GET', '/@foo/works/abc'],
            ['article_index',   'GET', '/articles'],
            ['search',          'GET', '/search/1234567890'],
            [$not_found,        'GET', '/search/12345678901'],
        ];

        foreach ($requests as $req) {
            list($expected, $method, $path) = $req;
            $this->assertEquals($expected, $router->match($method, $path)->value);
        }
    }
}

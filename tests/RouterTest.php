<?php

namespace Teto\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use function count;
use function explode;
use function preg_replace;
use function strlen;
use function substr;

/**
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */
#[CoversClass(Router::class)]
#[UsesClass(Action::class)]
#[UsesClass(NotFoundAction::class)]
final class RouterTest extends TestCase
{
    private static Router $router;

    /** @var array<int|string, array{0: non-empty-string, 1: non-empty-string, 2?: mixed, 3?: array<string, string>, '?ext'?: list<string>}|string> */
    private static array $route_map;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $re_user = '/^@([-A-Za-z]{3,15})$/';
        $re_id = '/^\d+$/';
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
            '#404' => 'Not Found!',
        ];

        self::$router = new Router(self::$route_map);
    }

    /** @param array<string, int|string> $expected_param */
    #[DataProvider('dataProviderFor_match')]
    public function test_match(string $method, string $path, mixed $expected_value, array $expected_param): void
    {
        $actual = self::$router->match($method, $path);
        $split_path = (strlen($path) === 1) ? [] : explode('/', substr($path, 1));

        $this->assertEquals($expected_value, $actual->value);
        $this->assertEquals($expected_param, $actual->param);
        $this->assertCount(count($split_path), $actual->split_path);

        if ($actual->param_pos) {
            foreach ($actual->split_path as $i => $path) {
                if (empty($actual->param_pos[$i])) {
                    $this->assertEquals($split_path[$i], $path);
                } else {
                    $this->assertMatchesRegularExpression($path, $split_path[$i]);
                }
            }
        } else {
            if ($actual->extension) {
                $last = count($split_path) - 1;
                $pattern = '/\.' . $actual->extension . '$/';
                $split_path[$last] = preg_replace($pattern, '', $split_path[$last]);
            }

            $this->assertEquals($split_path, $actual->split_path);
        }
    }

    /**
     * @return iterable<list{string, string, string, array<string, int|string>}>
     */
    public static function dataProviderFor_match(): iterable
    {
        $not_found = 'Not Found!';

        yield ['GET', '/',                   'index',           []];
        yield ['POST', '/',                  $not_found,        []];
        yield ['PUT', '/',                   $not_found,        []];
        yield ['HEAD', '/',                  'index',           []];
        yield ['GET', '/foo',                $not_found,        []];
        yield ['DELETE', '/foo',             $not_found,        []];
        yield ['GET', '/@foo',               'show_user',       [
            'user' => 'foo',
        ]];
        yield ['GET', '/@foo.json',          $not_found,        []];
        yield ['GET', '/@foo/works',         'show_user_works', [
            'user' => 'foo',
        ]];
        yield ['HEAD', '/@foo/works',        'show_user_works', [
            'user' => 'foo',
        ]];
        yield ['POST', '/@foo/works',        $not_found,        []];
        yield ['GET', '/@foo/works/123',     'show_user_work',  [
            'user' => 'foo',
            'id' => 123,
        ]];
        yield ['GET', '/@foo/works/abc',     $not_found,        []];
        yield ['GET', '/articles',           'article_index',   []];
        yield ['GET', '/data',               'data_json',       []];
        yield ['POST', '/data',              'post_data_json',  []];
        yield ['GET', '/data.',              $not_found,        []];
        yield ['GET', '/data.json',          'data_json',       []];
        yield ['GET', '/data.json.foo',       $not_found,        []];
        yield ['GET', '/et al',              $not_found,        []];
        yield ['GET', '/et al.',             'etal',            []];
        yield ['GET', '/et al.json',         $not_found,        []];
        yield ['GET', '/et al..json',        $not_found,        []];
        yield ['GET', '/search/1234567890',  'search',          [
            'word' => '1234567890',
        ]];
        yield ['GET', '/search/12345678901', $not_found,        []];
    }

    /** @param array<string, int|string> $expected_param */
    #[DataProvider('dataProviderFor_match')]
    public function test_dispatch(string $method, string $path, mixed $expected_value, array $expected_param): void
    {
        $actual = Router::dispatch(self::$route_map, $method, $path);

        $this->assertEquals($expected_value, $actual->value);
        $this->assertEquals(self::$router->match($method, $path), $actual);
    }

    /** @param array<string, int|string> $param */
    #[DataProvider('dataProviderFor_makePath')]
    public function test_makePath(string $expected, string $name, array $param, bool $strict): void
    {
        $this->assertEquals($expected, self::$router->makePath($name, $param, $strict));
    }

    /**
     * @return iterable<array{string, string, array<string, int|string>, strict: bool}>
     */
    public static function dataProviderFor_makePath(): iterable
    {
        yield [
            '/',
            'root',
            [],
            'strict' => false,
        ];
        yield [
            '/',
            'root',
            [
                'dummy' => 'val',
            ],
            'strict' => false,
        ];
        yield [
            '/',
            'root',
            [],
            'strict' => true,
        ];
        yield [
            '/data',
            'data',
            [],
            'strict' => false,
        ];
        yield [
            '/data',
            'data',
            [],
            'strict' => true,
        ];
        yield [
            '/info.rss',
            'info',
            [
                '?ext' => 'rss',
            ],
            'strict' => true,
        ];
        yield [
            '/@john/works/12',
            'user_work',
            [
                'user' => '@john',
                'id' => 12,
            ],
            'strict' => false,
        ];
        yield [
            '/@john/works/12',
            'user_work',
            [
                'user' => '@john',
                'id' => 12,
            ],
            'strict' => true,
        ];
    }

    public function test_makePathDefaultsToNonStrict(): void
    {
        $this->assertSame('/', self::$router->makePath('root', ['dummy' => 'val']));
    }

    /** @param array<string, int|string> $param */
    #[DataProvider('dataProviderFor_makePath_throws_DomainException')]
    public function test_makePath_throws_DomainException(string $expected, string $name, array $param): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($expected);
        self::$router->makePath($name, $param, true);
    }

    /**
     * @return iterable<list{string, string, array<string, int|string>}>
     */
    public static function dataProviderFor_makePath_throws_DomainException(): iterable
    {
        yield [
            'unnecessary parameters',
            'root',
            [
                'dummy' => 'val',
            ],
        ];
        yield [
            'unnecessary parameters',
            'data',
            [
                'dummy' => 'val',
            ],
        ];
        yield [
            'Error',
            'user_work',
            [],
        ];
        yield [
            'Error',
            'user_work',
            [
                'user' => 'john',
            ],
        ];
        yield [
            'Error',
            'user_work',
            [
                'user' => '@john',
            ],
        ];
        yield [
            'unnecessary parameters',
            'user_work',
            [
                'dummy' => 'val',
            ],
        ];
        yield [
            'unnecessary parameters',
            'user_work',
            [
                'user' => '@john',
                'id' => 12,
                'dummy' => 'val',
            ],
        ];
    }

    public function test_makePathReportsUnexpectedParameterAsList(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('unnecessary parameters: ["dummy"]');

        self::$router->makePath('user_work', [
            'user' => '@john',
            'id' => 12,
            'dummy' => 'val',
        ], true);
    }

    public function test_publicRouteHelpersRemainCallable(): void
    {
        $router = new Router(['#404' => 'Not Found!']);

        $router->setAction('home', ['GET', '/home', 'Home']);
        $router->setSpecialAction('#404', 'Still not found!');

        $this->assertSame('Home', $router->match('GET', '/home')->value);
        $this->assertSame(['GET'], $router->getNotFoundAction('GET', '/missing')->methods);
        $this->assertSame('Still not found!', $router->match('GET', '/missing')->value);
    }

    public function test_setActionUsesTrueForFalsyValue(): void
    {
        $router = new Router([
            'zero' => ['GET', '/zero', 0],
            '#404' => 'Not Found!',
        ]);

        $this->assertTrue($router->match('GET', '/zero')->value);
    }

    public function test_matchClearsParametersFromPreviouslyMatchedVariableAction(): void
    {
        $router = new Router([
            'article' => ['GET', '/articles/:id', 'Article', [
                'id' => '/^\d+$/',
            ]],
            'post' => ['GET', '/posts/:id', 'Post', [
                'id' => '/^\d+$/',
            ]],
            '#404' => 'Not Found!',
        ]);

        $router->match('GET', '/articles/42');
        $router->match('GET', '/posts/42');

        $this->assertSame([], $router->named_actions['article']->param);
    }

    public function test_setRejectsUnexpectedProperty(): void
    {
        $router = new Router(['#404' => 'Not Found!']);

        $this->expectException(\OutOfRangeException::class);

        $router->__set('unexpected', 'value');
    }

    public function test_constructorRejectsNonArrayRouteDefinition(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Route definitions must be arrays.');

        new Router([
            'invalid' => 'not an array',
            '#404' => 'Not Found!',
        ]);
    }

    public function test_matchRejectsUnsafePaths(): void
    {
        $router = new Router([
            'home_extra' => ['GET', '/home/extra', 'Home extra'],
            '#404' => 'Not Found!',
        ]);

        $this->assertSame('Not Found!', $router->match('GET', '/home//extra')->value);
        $this->assertSame(['GET'], $router->match('GET', '/home//extra')->methods);

        $router = new Router([
            'encoded' => ['GET', "/home\x1Eextra", 'Encoded'],
            '#404' => 'Not Found!',
        ]);

        $this->assertSame('Not Found!', $router->match('GET', "/home\x1Eextra")->value);
    }

    public function test_makePathRejectsUnknownName(): void
    {
        $router = new Router([
            'home' => ['GET', '/home', 'Home'],
            '#404' => 'Not Found!',
        ]);

        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('"unknown" is not exists.');

        $router->makePath('unknown');
    }

    public function test_makePathRejectsNonStringExtension(): void
    {
        $router = new Router([
            'data' => ['GET', '/data', 'Data', '?ext' => ['json']],
            '#404' => 'Not Found!',
        ]);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('The extension parameter must be a string or null.');

        $router->makePath('data', ['?ext' => 1]);
    }
}

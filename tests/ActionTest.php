<?php

namespace Teto\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */
#[CoversClass(Action::class)]
final class ActionTest extends TestCase
{
    /**
     * @param array<string, string> $param
     * @param array<int, string> $methods
     * @param array<int, string> $split_path
     * @param array<int, string> $extension
     * @param array<int, string> $param_pos
     * @param array{method: string, path: array<int, string>, ext: string} $request
     */
    #[DataProvider('dataProviderFor_match')]
    public function test_match(
        bool $expected,
        array $param,
        array $methods,
        array $split_path,
        array $extension,
        array $param_pos,
        array $request
    ): void {
        $action = new Action($methods, $split_path, $param_pos, $extension, 'matched!');
        $actual = $action->match($request['method'], $request['path'], $request['ext']);

        $this->assertSame($expected, $actual !== null);
        $this->assertSame($action->param, $param);

        if ($expected) {
            $this->assertInstanceOf(Action::class, $actual);
            $this->assertSame($action, $actual);
        }
    }

    public static function dataProviderFor_match(): array
    {
        return [
            [
                'expected' => false,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => [],
                'extension' => [],
                'param_pos' => [],
                'request' => [
                    'method' => 'GET',
                    'path' => ['foo'],
                    'ext' => '',
                ],
            ],
            [
                'expected' => true,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => [],
                'extension' => [],
                'param_pos' => [],
                'request' => [
                    'method' => 'GET',
                    'path' => [],
                    'ext' => '',
                ],
            ],
            [
                'expected' => false,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => [],
                'param_pos' => [],
                'request' => [
                    'method' => 'GET',
                    'path' => [],
                    'ext' => '',
                ],
            ],
            [
                'expected' => true,
                'param' => [
                    'id' => '1',
                ],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => [],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '1'],
                    'ext' => '',
                ],
            ],
            [
                'expected' => true,
                'param' => [
                    'id' => '1',
                ],
                'methods' => ['GET', 'POST'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => [],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '1'],
                    'ext' => '',
                ],
            ],
            [
                'expected' => false,
                'param' => [],
                'methods' => ['POST'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => [],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '1'],
                    'ext' => '',
                ],
            ],
            [
                'expected' => false,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => ['users', 'profile'],
                'extension' => [],
                'param_pos' => [],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', 'settings'],
                    'ext' => '',
                ],
            ],
            [
                'expected' => false,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => [],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', 'a'],
                    'ext' => '',
                ],
            ],
            [
                'expected' => false,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => ['users', '/\A(\d+)\.json\z/'],
                'extension' => [],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '1234'],
                    'ext' => '',
                ],
            ],
            [
                'expected' => true,
                'param' => [
                    'id' => '1234',
                ],
                'methods' => ['GET'],
                'split_path' => ['users', '/\A(\d+)\.json\z/'],
                'extension' => [],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '1234'],
                    'ext' => 'json',
                ],
            ],
            [
                'expected' => true,
                'param' => [
                    'id' => '0401',
                ],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => ['', 'jpg'],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '0401'],
                    'ext' => 'jpg',
                ],
            ],
            [
                'expected' => false,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => ['', 'jpg'],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '0401'],
                    'ext' => 'png',
                ],
            ],
            [
                'expected' => true,
                'param' => [
                    'id' => '0401',
                ],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => ['jpg', 'gif'],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '0401'],
                    'ext' => 'jpg',
                ],
            ],
            [
                'expected' => false,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => ['jpg', 'gif'],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '0401'],
                    'ext' => 'png',
                ],
            ],
            [
                'expected' => true,
                'param' => [
                    'id' => '0401',
                ],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => ['*'],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '0401'],
                    'ext' => 'png',
                ],
            ],
            [
                'expected' => false,
                'param' => [],
                'methods' => ['GET'],
                'split_path' => ['users', '/(\d+)/'],
                'extension' => ['jpg', 'gif'],
                'param_pos' => [
                    1 => 'id',
                ],
                'request' => [
                    'method' => 'GET',
                    'path' => ['users', '0401'],
                    'ext' => '',
                ],
            ],
        ];
    }

    /**
     * @param array<int|string, mixed> $expected
     * @param array<string, string> $params
     */
    #[DataProvider('dataProviderFor_parsePathParam')]
    public function test_parsePathParam(array $expected, string $path, array $params): void
    {
        $this->assertEquals($expected, Action::parsePathParam($path, $params));
    }

    public static function dataProviderFor_parsePathParam(): array
    {
        return [
            [
                'expected' => [[], []],
                'path' => '/',
                'params' => [],
            ],
            [
                'expected' => [['login'], []],
                'path' => '/login',
                'params' => [],
            ],
            [
                'expected' => [
                    ['user', '/(@[-A-Za-z]{3,15})/', 'works'],
                    [
                        1 => 'name',
                    ],
                ],
                'path' => '/user/:name/works',
                'params' => [
                    'name' => '/(@[-A-Za-z]{3,15})/',
                ],
            ],
            [
                'expected' => [['login'], []],
                'path' => '/login',
                'params' => [],
            ],
        ];
    }

    /**
     * @param array<int, string> $split_path
     * @param array<int, string> $param_pos
     * @param array<string, int|string> $param
     */
    #[DataProvider('dataProviderFor_test_makePath')]
    public function test_makePath(string $expected, array $split_path, array $param_pos, array $param, ?string $ext, bool $strict): void
    {
        $action = new Action(['GET'], $split_path, $param_pos, [], "returns!");
        $actual = $action->makePath($param, $ext, $strict);

        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderFor_test_makePath(): array
    {
        return [
            [
                'expected' => "/a/12/d",
                'split_path' => ['a', '(^\d+$)', 'd'],
                'param_pos' => [
                    1 => 'b',
                ],
                'param' => [
                    'b' => 12,
                ],
                'ext' => null,
                'strict' => false,
            ],
            [
                'expected' => '/a/12/d.json',
                'split_path' => ['a', '(^\d+$)', 'd'],
                'param_pos' => [
                    1 => 'b',
                ],
                'param' => [
                    'b' => 12,
                ],
                'ext' => 'json',
                'strict' => true,
            ],
        ];
    }

    public function test_makePathRejectsUnexpectedParameterInStrictMode(): void
    {
        $action = new Action(['GET'], ['a'], [], [], 'returns!');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('unnecessary parameters: ["dummy"]');

        $action->makePath(['dummy' => 'value'], null, true);
    }

    public function test_makePathRejectsMissingParameter(): void
    {
        $action = new Action(['GET'], ['(^\d+$)'], [0 => 'id'], [], 'returns!');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Error');

        $action->makePath([], null, false);
    }

    public function test_matchRejectsRequestPathThatEqualsParameterPattern(): void
    {
        $action = new Action(['GET'], ['users', '(^\d+$)'], [1 => 'id'], [], 'returns!');

        $this->assertNull($action->match('GET', ['users', '(^\d+$)'], ''));
    }

    public function test_matchUsesWholeMatchWhenPatternHasNoCaptureGroup(): void
    {
        $action = new Action(['GET'], ['/^\d+$/'], [0 => 'id'], [], 'returns!');

        $matched = $action->match('GET', ['123'], '');

        $this->assertNotNull($matched);
        $this->assertSame(['id' => '123'], $matched->param);
    }

    public function test_matchExtensionIsPublic(): void
    {
        $action = new Action(['GET'], [], [], ['json'], 'returns!');

        $this->assertTrue($action->matchExtension('json'));
    }

    public function test_createBuildsAction(): void
    {
        $action = Action::create('GET', '/users/:id', 'returns!', [], ['id' => '/^\d+$/']);

        $this->assertSame(['users', '/^\d+$/'], $action->split_path);
        $this->assertSame([1 => 'id'], $action->param_pos);
        $this->assertSame('returns!', $action->value);
    }

    public function test_setHTTPMethodUpdatesAllowedMethods(): void
    {
        Action::setHTTPMethod(['GET', 'POST', 'PUT']);

        try {
            $action = new Action(['PUT'], [], [], [], 'returns!');

            $this->assertSame(['PUT'], $action->methods);
        } finally {
            Action::setHTTPMethod(['GET', 'POST']);
        }
    }

    public function test_constructorRejectsDisallowedMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Action(['PATCH'], [], [], [], 'returns!');
    }
}

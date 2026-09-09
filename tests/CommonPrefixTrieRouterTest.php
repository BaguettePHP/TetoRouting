<?php

namespace Teto\Routing;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CommonPrefixTrieRouter::class)]
final class CommonPrefixTrieRouterTest extends TestCase
{
    public function test_searchMatchesFixedRoute(): void
    {
        $trie = CommonPrefixTrieRouter::trieConstruction([
            ['GET', '/users/list', 'fixed'],
        ]);

        $this->assertSame(
            ['value' => 'fixed', 'params' => []],
            CommonPrefixTrieRouter::search($trie, '/users/list', 'GET'),
        );
    }

    public function test_searchMatchesNumericAndStringParameters(): void
    {
        $trie = CommonPrefixTrieRouter::trieConstruction([
            ['GET', '/users/:id', 'numeric', ['id' => '[']],
            ['GET', '/posts/:slug', 'string', ['slug' => ']']],
        ]);

        $this->assertSame(
            ['value' => 'numeric', 'params' => ['id' => '123']],
            CommonPrefixTrieRouter::search($trie, '/users/123', 'GET'),
        );
        $this->assertSame(
            ['value' => 'numeric', 'params' => ['id' => '0']],
            CommonPrefixTrieRouter::search($trie, '/users/0', 'GET'),
        );
        $this->assertSame(
            ['value' => 'numeric', 'params' => ['id' => '9']],
            CommonPrefixTrieRouter::search($trie, '/users/9', 'GET'),
        );
        $this->assertNull(CommonPrefixTrieRouter::search($trie, '/users/1a2', 'GET'));
        $this->assertNull(CommonPrefixTrieRouter::search($trie, '/users/abc', 'GET'));
        $this->assertSame(
            ['value' => 'string', 'params' => ['slug' => 'hello']],
            CommonPrefixTrieRouter::search($trie, '/posts/hello', 'GET'),
        );
    }

    public function test_searchReturnsNullForInvalidRequests(): void
    {
        $trie = CommonPrefixTrieRouter::trieConstruction([
            ['GET', '/users/list', 'fixed'],
        ]);

        $this->assertNull(CommonPrefixTrieRouter::search($trie, '', 'GET'));
        $this->assertNull(CommonPrefixTrieRouter::search($trie, 'users/list', 'GET'));
        $this->assertNull(CommonPrefixTrieRouter::search($trie, '/posts/list', 'GET'));
    }

    public function test_searchReturnsNullForInvalidRequestsAgainstRootRoute(): void
    {
        $trie = CommonPrefixTrieRouter::trieConstruction([
            ['GET', '/', 'root'],
        ]);

        $this->assertNull(CommonPrefixTrieRouter::search($trie, '', 'GET'));
        $this->assertNull(CommonPrefixTrieRouter::search($trie, 'users', 'GET'));
    }

    public function test_searchReturnsNullForMalformedNodes(): void
    {
        $this->assertNull(CommonPrefixTrieRouter::search(
            ['GET' => ['/broken' => 'value']],
            '/broken',
            'GET',
        ));
        $this->assertNull(CommonPrefixTrieRouter::search(
            ['GET' => ['[' => [], '>' => 'root']],
            '/123',
            'GET',
        ));
        $this->assertNull(CommonPrefixTrieRouter::search(
            ['GET' => [']' => [], '>' => 'root']],
            '/hello',
            'GET',
        ));
    }

    public function test_trieConstructionRejectsInvalidPath(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('不正なパスが設定されています users');

        CommonPrefixTrieRouter::trieConstruction([
            ['GET', 'users', 'value'],
        ]);
    }

    public function test_trieConstructionRequiresParameterMapping(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('URLパラメータ :id に対する設定が足りません');

        CommonPrefixTrieRouter::trieConstruction([
            ['GET', '/users/:id', 'value'],
        ]);
    }

    public function test_trieConstructionRejectsDuplicateRoute(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('重複したルーティングルールがあります /users');

        CommonPrefixTrieRouter::trieConstruction([
            ['GET', '/users', 'first'],
            ['GET', '/users', 'second'],
        ]);
    }

    public function test_trieConstructionRejectsConflictingParameterNames(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('URLパラメータに別名をつけようとしています /users/:name');

        CommonPrefixTrieRouter::trieConstruction([
            ['GET', '/users/:id', 'first', ['id' => '[']],
            ['GET', '/users/:name', 'second', ['name' => '[']],
        ]);
    }

    public function test_trieConstructionRejectsDuplicateRouteWithSameParameterName(): void
    {
        $this->expectExceptionMessage('重複したルーティングルールがあります /users/:id');

        CommonPrefixTrieRouter::trieConstruction([
            ['GET', '/users/:id', 'first', ['id' => '[']],
            ['GET', '/users/:id', 'second', ['id' => '[']],
        ]);
    }
}

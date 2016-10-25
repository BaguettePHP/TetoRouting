Teto Routing - PHP simple router
================================

[![Join the chat at https://gitter.im/BaguettePHP/simple-routing](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/BaguettePHP/simple-routing?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Package version](http://img.shields.io/packagist/v/zonuexe/simple-routing.svg?style=flat)](https://packagist.org/packages/zonuexe/simple-routing)
[![Build Status](https://travis-ci.org/BaguettePHP/TetoRouting.svg?branch=master)](https://travis-ci.org/BaguettePHP/TetoRouting)
[![Packagist](http://img.shields.io/packagist/dt/zonuexe/simple-routing.svg?style=flat)](https://packagist.org/packages/zonuexe/simple-routing)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/BaguettePHP/TetoRouting/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/BaguettePHP/TetoRouting/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/BaguettePHP/TetoRouting/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/BaguettePHP/TetoRouting/?branch=master)

Simple routing for WebApp

No *magic*.  No *reflection*.  No *complex dependency*.

Installation
------------

### Composer

```
composer require zonuexe/simple-routing
```

References
----------

* [API document](http://baguettephp.github.io/TetoRouting/namespace-Teto.Routing.html)
* ja: [シンプルなルーティングがしたかった - Qiita](http://qiita.com/tadsan/items/bcaa14504d0ecdd9e096)

Routing DSL
-----------

```php
//    Method      Path           ReturnValue  Param => RegExp     extension (format)
$routing_map = [
    ['GET',      '/',            'index'  ],
    ['GET|POST', '/search',      'search' ],
    ['GET',      '/article/:id', 'article',  ['id' => '/\A(\d+)\z/'], '?ext' => ['', 'txt']],
    ['GET',      '/info',        'feed' ,                             '?ext' => ['rss', 'rdf', 'xml']],
     '#404'       =>             'not_found' // special
];

$router = new \Teto\Routing\Router($routing_map);
$action = $router->match($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

// Shorthand (but, do not use reverse routing)
$action = \Teto\Routing\Router::dispatch($routing_map, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

### Reverse routing

```php
//   Name         Method      Path            ReturnValue  Param => RegExp     extension (file format)
$routing_map = [
    'root'    => ['GET',      '/',            'index'  ],
    'search'  => ['GET|POST', '/search',      'search' ],
    'article' => ['GET',      '/article/:id', 'article',  ['id' => '/\A(\d+)\z/'], '?ext' => ['', 'txt']],
    'info'    => ['GET',      '/info',        'feed' ,                             '?ext' => ['rss', 'rdf', 'xml']],
    '#404'    =>                              'not_found' // special
];

$router = new \Teto\Routing\Router($routing_map);

$router->makePath('root');    //=> '/'
$router->makePath('search');  //=> '/search'
$router->makePath('article', ['id' => 123]);     //=> '/article/123'
$router->makePath('info',    ['?ext' => 'rss']); //=> '/info.rss'
```

### Tips

```php
$re_num_id    = '/\A(\d+)\z/';
$re_user_name = '/\A@([a-z]+)\z/';

$routing_map = [
    'root'    => ['GET', '/',            'index'  ],
    'search'  => ['GET', '/search',      'search' ],
    'article' => ['GET', '/article/:id', 'article',  ['id' => $re_num_id], '?ext' => ['', 'txt']],
    '#404'    =>                         'not_found' // special
];
```

Related Libraries
-----------------

You can get these libraries from Packagist.

* [HTTP Accept-Language](https://github.com/zonuexe/php-http-accept-language)
  * [zonuexe/http-accept-language - Packagist](https://packagist.org/packages/zonuexe/http-accept-language)
* [Teto Objectsystem](https://github.com/zonuexe/php-objectsystem)
  * [zonuexe/objectsystem - Packagist](https://packagist.org/packages/zonuexe/objectsystem)
* [Baguette PHP](https://github.com/BaguettePHP/baguette)
  * [zonuexe/baguette](https://packagist.org/packages/zonuexe/baguette)

Copyright
---------

**Teto\\Routing** is licensed under [Apache License Version 2.0](https://www.apache.org/licenses/LICENSE-2.0). See `./LICENSE`.

    Teto Routing - PHP simple router for WebApp
    Copyright (c) 2016 Baguette HQ / USAMI Kenta <tadsan@zonu.me>

Teto Kasane
-----------

I love [Teto Kasane](http://utau.wikia.com/wiki/Teto_Kasane). (ja: [Teto Kasane official site](http://kasaneteto.jp/))

```
　　　　　 　r /
　 ＿＿ , --ヽ!-- .､＿
　! 　｀/::::;::::ヽ l
　!二二!::／}::::丿ハﾆ|
　!ﾆニ.|:／　ﾉ／ }::::}ｺ
　L二lイ　　0´　0 ,':ﾉｺ
　lヽﾉ/ﾍ､ ''　▽_ノイ ソ
 　ソ´ ／}｀ｽ /￣￣￣￣/
　　　.(_:;つ/  0401 /　ｶﾀｶﾀ
 ￣￣￣￣￣＼/＿＿＿＿/
```

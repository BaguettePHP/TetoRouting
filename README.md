Teto Routing
============

[![Join the chat at https://gitter.im/BaguettePHP/php-simple-routing](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/BaguettePHP/php-simple-routing?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Package version](http://img.shields.io/packagist/v/zonuexe/simple-routing.svg?style=flat)](https://packagist.org/packages/zonuexe/simple-routing)
[![Build Status](https://travis-ci.org/BaguettePHP/php-simple-routing.svg?branch=master)](https://travis-ci.org/BaguettePHP/php-simple-routing)
[![Packagist](http://img.shields.io/packagist/dt/zonuexe/simple-routing.svg?style=flat)](https://packagist.org/packages/zonuexe/simple-routing)
[![Coverage Status](https://coveralls.io/repos/BaguettePHP/php-simple-routing/badge.svg)](https://coveralls.io/r/BaguettePHP/php-simple-routing)

Simple routing for WebApp

No *magic*.  No *reflection*.  No *complex dependency*.

Installation
------------

### Composer

```
composer require zonuexe/simple-routing
```

Routing DSL
-----------

```php
//    Method      Path           ReturnValue  Param => RegExp     extension (format)
$routing_map = [
    ['GET',      '/',            'index'  ],
    ['GET|POST', '/search',      'search' ],
    ['GET',      '/article/:id', 'article',  ['id' => '/(\d+)/'], '?ext' => ['', 'txt']],
    ['GET',      '/info',        'feed' ,                         '?ext' => ['rss', 'rdf', 'xml']],
     '#404'       =>             'not_found' // special
];

$action = \Teto\Routing\Router::dispatch($routing_map, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

// Another way
$router = new \Teto\Routing\Router($routing_map);
$action = $router->match($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

### Reverse routing


```php
//   Name         Method      Path            ReturnValue  Param => RegExp     extension (file format)
$routing_map = [
    'root'    => ['GET',      '/',            'index'  ],
    'search'  => ['GET|POST', '/search',      'search' ],
    'article' => ['GET',      '/article/:id', 'article',  ['id' => '/(\d+)/'], '?ext' => ['', 'txt']],
    'info'    => ['GET',      '/info',        'feed' ,                         '?ext' => ['rss', 'rdf', 'xml']],
    '#404'    =>                              'not_found' // special
];

$router = new \Teto\Routing\Router($routing_map);

$router->makePath('root');    //=> '/'
$router->makePath('search');  //=> '/search'
$router->makePath('article', ['id' => 123]);     //=> '/article/123'
$router->makePath('info',    ['?ext' => 'rss']); //=> '/info.rss'
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

see `./LICENSE`.

    Simple routing for WebApp
    Copyright (c) 2015 USAMI Kenta <tadsan@zonu.me>

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

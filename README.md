Teto Routing
============

Simple routing for WebApp

Installation
------------

### Composer

```
composer require zonuexe/simple-routing
```

Routing DSL
-----------

```php
//    Method      Path           ReturnValue  Param => RegExp
$routing_map = [
    ['GET',      '/',            'index'  ],
    ['GET|POST', '/search',      'search' ],
    ['GET',      '/article/:id', 'article' , ['id' => '/(\d+)/']],
     '#404'       =>             'not_found' // special
];
$router = new \Teto\Routing\Router($routing_map);

```

Related Libraries
-----------------

You can get these libraries from Packagist.

* [HTTP Accept-Language](https://github.com/zonuexe/php-http-accept-language)
  * [zonuexe/http-accept-language - Packagist](https://packagist.org/packages/zonuexe/http-accept-language)
* [Teto Objectsystem](https://github.com/zonuexe/php-objectsystem)
  * [zonuexe/objectsystem - Packagist](https://packagist.org/packages/zonuexe/objectsystem)

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

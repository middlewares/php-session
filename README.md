# middlewares/php-session

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to initialize a [php session](http://php.net/manual/en/book.session.php) using the request data and close it after return the response.

**Note:** This middleware is intended for server side only

## Requirements

* PHP >= 5.6
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http mesage implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15](https://github.com/http-interop/http-middleware) middleware dispatcher ([Middleman](https://github.com/mindplay-dk/middleman), etc...)

## Installation

This package is installable and autoloadable via Composer as [middlewares/php-session](https://packagist.org/packages/middlewares/php-session).

```sh
composer require middlewares/php-session
```

## Example

```php
$dispatcher = new Dispatcher([
	new Middlewares\PhpSession(),
    
    function () {
        //Use the global $_SESSION variable to get/set data
        $_SESSION['name'] = 'John';
    }
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## Options

#### `name(string $name)`

The session name. If it's not provided, use the php's default

#### `id(string $id)`

The session id. If it's not provided, try to get it from the request's cookies.

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/php-session.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/php-session/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/php-session.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/php-session.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/36786f5a-2a15-4399-8817-8f24fcd8c0b4.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/php-session
[link-travis]: https://travis-ci.org/middlewares/php-session
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/php-session
[link-downloads]: https://packagist.org/packages/middlewares/php-session
[link-sensiolabs]: https://insight.sensiolabs.com/projects/36786f5a-2a15-4399-8817-8f24fcd8c0b4

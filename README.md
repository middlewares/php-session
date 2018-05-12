# middlewares/php-session

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to start a [php session](http://php.net/manual/en/book.session.php) using the request data and close it after return the response.

## Requirements

* PHP >= 7.0
* A [PSR-7 http message implementation](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

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

#### `options(array $options)`

Array of options passed to [`session_start()`](http://php.net/session_start)

### `regenerateId(int $interval, string $key = 'session-id-expires')`

The session id regeneration interval in seconds. If it's 0 or not provided, sesson ID will remain unchanged.

The session id expiry timestamp key name.

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/php-session.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/php-session/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/php-session.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/php-session.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/ddd29a82-48bb-4fdd-a71d-98a3c00abd7c.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/php-session
[link-travis]: https://travis-ci.org/middlewares/php-session
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/php-session
[link-downloads]: https://packagist.org/packages/middlewares/php-session
[link-sensiolabs]: https://insight.sensiolabs.com/projects/ddd29a82-48bb-4fdd-a71d-98a3c00abd7c

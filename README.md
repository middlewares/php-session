# middlewares/php-session

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-ga]
[![Total Downloads][ico-downloads]][link-downloads]

Middleware to start a [php session](http://php.net/manual/en/book.session.php) using the request data and close it after returning the response. Reads and writes session cookies in the PSR-7 request/response.

## Requirements

* PHP >= 7.2
* A [PSR-7 http message implementation](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/php-session](https://packagist.org/packages/middlewares/php-session).

```sh
composer require middlewares/php-session
```

## Example

```php
Dispatcher::run([
	new Middlewares\PhpSession(),

    function () {
        //Use the global $_SESSION variable to get/set data
        $_SESSION['name'] = 'John';
    }
]);
```

## Usage

This is a middleware to start the native PHP session using the cookies of the server request.

### name

The session name. If it's not provided, use the php's default name (PHPSESSID). More info [session_name](https://www.php.net/manual/en/function.session-name.php)

```php
// Start the session with other name
$session = (new Middlewares\PhpSession())->name('user_session');
```

### id

This option set a session id. If it's not provided, use the request's cookies to get it.

```php
// Start the session with a specific session id
$session = (new Middlewares\PhpSession())->id('foo');
```

### options

This allows to set an of options passed to [`session_start()`](http://php.net/session_start)

```php
// Start the session with a specific session id
$session = (new Middlewares\PhpSession())->options([
    'cookie_lifetime' => 86400
]);
```

### regenerateId

This option regenerates the id after a specific time interval. The latest regeneration time is saved in the key `session-id-expires` but you can change it in the second argument:

```php
// Regenerate the session id after 60 seconds
$session = (new Middlewares\PhpSession())->regenerateId(60);

// Regenerate the session id after 60 seconds, storing the expires date in the key 'expiresAt'
$session = (new Middlewares\PhpSession())->regenerateId(60, 'expiresAt');
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/php-session.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ga]: https://github.com/middlewares/php-session/workflows/testing/badge.svg
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/php-session.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/php-session
[link-downloads]: https://packagist.org/packages/middlewares/php-session

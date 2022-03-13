# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [3.1.1] - 2022-03-13
### Fixed
- Get the session name from options if available and not explicity set [#14]

## [3.1.0] - 2021-11-05
### Added
- Support for `SameSite` [#9].

### Fixed
- Code improvements [#7], [#8].

## [3.0.1] - 2021-11-03
### Fixed
- Replace `isset` with `!empty` [#6]

## [3.0.0] - 2021-02-22
### Changed
- This middleware handles the output session id cookie in the Psr7 response, instead send the cookie automaticaly by PHP. This requires the configuration `use_trans_sid`, `use_cookies` must be `false` and `use_only_cookies` as `true` [#4] [#5].

## [2.0.0] - 2020-12-03
### Added
- Support for PHP 8

### Removed
- Support for PHP 7.0 and 7.1

## [1.2.0] - 2018-08-04
### Added
- PSR-17 support

## [1.1.0] - 2018-05-12
### Added
- New function `regenerateId` to regenerate session IDs after a given interval [#2]

## [1.0.0] - 2018-01-27
### Added
- Improved testing and added code coverage reporting
- Added tests for PHP 7.2

### Changed
- Upgraded to the final version of PSR-15 `psr/http-server-middleware`

### Fixed
- Updated license year

## [0.6.0] - 2017-11-13
### Changed
- Replaced `http-interop/http-middleware` with  `http-interop/http-server-middleware`.

### Removed
- Removed support for PHP 5.x.

## [0.5.0] - 2017-09-21
### Changed
- Append `.dist` suffix to phpcs.xml and phpunit.xml files
- Changed the configuration of phpcs and php_cs
- Upgraded phpunit to the latest version and improved its config file
- Updated to `http-interop/http-middleware#0.5`

## [0.4.0] - 2017-04-18
### Added
- New option `options()` to configure `session_start()`

## [0.3.0] - 2016-12-26
### Changed
- Updated tests
- Updated to `http-interop/http-middleware#0.4`
- Updated `friendsofphp/php-cs-fixer#2.0`

## [0.2.0] - 2016-11-27
### Changed
- Updated to `http-interop/http-middleware#0.3`

## [0.1.0] - 2016-10-08
First version

[#2]: https://github.com/middlewares/php-session/issues/2
[#4]: https://github.com/middlewares/php-session/issues/4
[#5]: https://github.com/middlewares/php-session/issues/5
[#6]: https://github.com/middlewares/php-session/issues/6
[#7]: https://github.com/middlewares/php-session/issues/7
[#8]: https://github.com/middlewares/php-session/issues/8
[#9]: https://github.com/middlewares/php-session/issues/9
[#14]: https://github.com/middlewares/php-session/issues/14

[3.1.1]: https://github.com/middlewares/php-session/compare/v3.1.0...v3.1.1
[3.1.0]: https://github.com/middlewares/php-session/compare/v3.0.1...v3.1.0
[3.0.1]: https://github.com/middlewares/php-session/compare/v3.0.0...v3.0.1
[3.0.0]: https://github.com/middlewares/php-session/compare/v2.0.0...v3.0.0
[2.0.0]: https://github.com/middlewares/php-session/compare/v1.2.0...v2.0.0
[1.2.0]: https://github.com/middlewares/php-session/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/middlewares/php-session/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/middlewares/php-session/compare/v0.6.0...v1.0.0
[0.6.0]: https://github.com/middlewares/php-session/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/middlewares/php-session/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/middlewares/php-session/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/middlewares/php-session/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/middlewares/php-session/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/middlewares/php-session/releases/tag/v0.1.0

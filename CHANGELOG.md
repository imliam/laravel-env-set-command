# Changelog
The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security

## [1.2.0] - 2020-09-13
### Added
- Laravel 8 support


## [1.1.1] - 2020-05-27
### Fixed
- Fixed working with nested key names (for example, APP_KEY and PUSHER_APP_KEY).

## [1.1.0] - 2020-05-23
### Added
- Now you can set and update empty env-variables.
- Now you can specify external .env-file as the third optional argument.
- Now you can set the value with equals sign ("=").
- Added a lot of unit-tests.
- Added GitHub Actions integration.
- Added GitHub Actions build status badge.
- Added the command description.
- Added support for using en external `.env` file when using "key=value" syntax.
### Changed
- composer.json now includes needed laravel components.
### Fixed
- Fixed compatibility with Laravel 6+.

## [1.0.0] - 2018-07-13
### Added
- Initial release

[1.1.1]: https://github.com/imliam/laravel-env-set-command/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/imliam/laravel-env-set-command/compare/1.0.0...1.1.0

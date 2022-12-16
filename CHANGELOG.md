# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [0.6.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.5.0) - 2022-12-16
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.5.0...v0.6.0)

### Changed

- Default `api_timeout` is now unlimited instead of 10 seconds

### Added
- Add `createSignal` helper method to create ready-to-use signal

---


## [0.5.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.5.0) - 2022-12-15
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.4.1...v0.5.0)

### Added
- Add `user_agent_version` configuration

---

## [0.4.1](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.4.1) - 2022-12-08
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.4.0...v0.4.1)

### Changed
- Allow older version (v4) of `symfony/config` dependency

---

## [0.4.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.4.0) - 2022-12-01
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.3.0...v0.4.0)

### Changed
- *Breaking change*: Make method `AbstractClient::sendRequest` private instead of public
- *Breaking change*: Make method `AbstractClient::request` protected instead of public

### Added
- Add `api_timeout` configuration
- Add an optional param `$configs` in `Curl` and `FileGetContents` constructors

---

## [0.3.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.3.0) - 2022-11-04
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.2.0...v0.3.0)

### Added
- Add optional logger parameter in client constructor 

---

## [0.2.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.2.0) - 2022-10-28
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.1.0...v0.2.0)

### Changed
- *Breaking change*: Missing `scenarios` key in `configs` will throw an exception

---

## [0.1.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.1.0) - 2022-10-21
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.0.1...v0.1.0)

### Changed
- *Breaking change*: Supported PHP versions starts with `7.2.5` (instead of `5.3`)
- *Breaking change*: `login` and `register` are now private methods
- *Breaking change*: `Watcher` constructor is totally changed : 
  - No more `password` and `machine_id` to pass, there are now automatically handled in background
  - An array of `configs` must be passed as first argument
  - An implementation of a `StorageInterface` must be passed as a second argument
- Change User Agent format: `csphpcapi_custom-suffix/vX.Y.Z`

### Added
- Add `enroll` public method

---

## [0.0.1](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.0.1) - 2022-06-24
### Added
- Initial release

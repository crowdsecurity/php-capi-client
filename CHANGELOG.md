# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [0.1.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.1.0) - 2022-??-??
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

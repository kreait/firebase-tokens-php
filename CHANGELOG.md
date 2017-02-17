# CHANGELOG

## 1.1.0 - 2017-02-18

- Replaced `StaticKeyStore` with `HttpKeyStore`, which fetches frech Google Public Keys
  each time its `get()` method is invoked. Caching can be implemented by injecting
  an HTTP client with a cache middleware, e.g. 
  [kevinrob/guzzle-cache-middleware](https://github.com/Kevinrob/guzzle-cache-middleware).

## 1.0.1 - 2017-02-07

- Removed non-functional debug header
- Added `"php": "^7.0"`requirement to `composer.json`

## 1.0.0 - 2017-02-05

- Initial release

# CHANGELOG

## Unreleased

## 4.3.0 - 2023-11-30

* Added support for PHP 8.3

## 4.2.0 - 2023-03-21

* Added support for the Firebase Auth Emulator when using `lcobucci/jwt` 5.*

Note: The `Kreait\Firebase\JWT\Token` class has been renamed to `\Kreait\Firebase\JWT\SecureToken`. This is technically
a breaking change, but since the `*Verifier` classes type-hint `\Kreait\Firebase\JWT\Contract\Token` as return values,
I consider it unlikely that this should cause trouble for most people. If it does, I'll deal with the consequences. 

## 4.1.0 - 2023-02-28

* Added support for `lcobucci/jwt` 5.*

## 4.0.0 - 2022-11-26

The most notable change is that you need PHP 8.1/8.2 to use the new version. The language migration to
PHP 8.1 introduces potentially breaking changes concerning the strictness of parameter types - 
however, this should not affect your project in most cases (unless you have used internal classes 
directly or by extension).

Please see [UPGRADE-4.0.md](UPGRADE-4.0.md) for detailed information.

## 3.0.3 - 2022-08-22

* Ensured (PHPStan) compatibility with `lcobucci/jwt` ^4.2

## 3.0.2 - 2022-06-22

* Raised minimum version of Guzzle to address [CVE-2022-31090](https://github.com/advisories/GHSA-25mq-v84q-4j7r)
  and [CVE-2022-31091](https://github.com/advisories/GHSA-q559-8m2m-g699)

## 3.0.1 - 2022-06-10

* Raise minimum version of Guzzle to address [CVE-2022-31042](https://github.com/advisories/GHSA-f2wf-25xc-69c9)

## 3.0 - 2022-04-24

Implemented forward compatible Clock-Interface
  
The [stella-maris/clock](https://packagist.org/packages/stella-maris/clock) package provides an interface based on the 
currently proposed status of [PSR-20](https://github.com/php-fig/fig-standards/blob/6666a48cabf651bb0c06e090e028fe100100a45c/proposed/clock.md).
Due to the inactivity of the PSR20 working group this is a way to already provide interoperability while still
maintaining forward compatibility. When the current status of PSR20 will be released at one point in time the 
stella-maris/clock package will extend the PSR-20 interface so that this package becomes immeadiately PSR20 compatible 
without any further work necessary.

## 2.3.0 - 2022-04-16

* Removed `firebase/php-jwt` dev dependency and simplified test token generation.
* Added support for verifying tokens returned from the Auth Emulator.

## 2.2.0 - 2022-01-28

Added tenant support to Session Cookie Verification. It doesn't seem to be supported at the moment
(executing it with a tenant-enabled Firebase project yields an `UNSUPPORTED_TENANT_OPERATION`)
error, but once it _is_ supported, this library will need no or just minimal updates.

The [Firebase Admin SDK for PHP](https://github.com/kreait/firebase-php) has integration tests 
checking for this error so that we know early on when it starts working.

## 2.1.1 - 2022-01-28

Fixed method name `Kreait\Firebase\JWT\SessionCookieVerifier::sessionCookieWithLeeway` to
`Kreait\Firebase\JWT\SessionCookieVerifier::verifySessionCookieWithLeeway` 🤦‍. This is technically
a breaking change, but since 2.1.0 was released just a few minutes ago, it was most certainly not
used yet.

## 2.1.0 - 2022-01-28

Added `Kreait\Firebase\JWT\SessionCookieVerifier` that works similarly as the existing ID Token verifier.
You can find its documentation in the README.

## 2.0.1 - 2022-01-03

Fixed failing ID token verification when the `nbf` claim is not present.

## 2.0.0 - 2022-01-03

After updating, please refer to the [Migration Documentation](MIGRATE-1.x-to-2.0.md) to be ready for the 2.0 release of this library.

* Removed `Firebase\Auth` namespace
* Ensured compatibility with PHP 8.1 by adding it to the test matrix.
* Dropped support for `lcobucci/jwt` <4.1
* Dropped support for `guzzlehttp/guzzle` <7.0
* Dropped direct support for `psr/simple-cache`

## 1.16.1 - 2021-10-03

Update [lcobucci/jwt](https://github.com/lcobucci/jwt) version constraint to `^3.4.6|^4.0.4|^4.1.5` to prevent misuse
of the `LocalFileReference` key.

More info: [GHSA-7322-jrq4-x5hf](https://github.com/lcobucci/jwt/security/advisories/GHSA-7322-jrq4-x5hf)

## 1.16.0 - 2021-07-15

- Un-deprecated `Firebase\Auth\Token\Domain\Generator`, `Firebase\Auth\Token\Domain\Verifier` and
  `\Firebase\Auth\Token\Domain\KeyStore`
- Dropped support for unsupported PHP versions. Starting with this release, supported versions 
  are PHP ^7.4 and PHP ^8.0.
- Allowed usage of `psr/cache` `^2.0|^3.0`

## 1.15.0 - 2021-04-19

- Use fallback cache duration (defaults to 1 hour) when fetching public keys from Google and
  the response doesn't contain cache headers.
- Add additional URL to fetch Google's public keys. 

## 1.14.0 - 2020-12-09

- Drop the `V3` suffix from handlers using `lcobucci/jwt`
- Limit support to PHP 8.0.*

## 1.13.0 - 2020-11-29

- Added support for PHP 8.0

## 1.12.0 - 2020-11-27

- Added Tenant Awareness
- Fixed usage of deprecated functionality from `lcobucci/jwt`

## 1.11.0 - 2020-10-04

- Updated dev-dependency on `symfony/cache` to address [CVE-2019-10912](https://github.com/advisories/GHSA-w2fr-65vp-mxw3)
- The default branch of the GitHub repository has been renamed from `master` to `main` - 
  if you're using `dev-master` as a version constraint in your `composer.json`, please 
  update it to `dev-main`.
- This library can now be used with PHP 8. 

## 1.10.0 - 2020-01-14

- Added support for Guzzle 7
- Improved error handling and error messages

## 1.9.1 - 2019-08-26

- Bumped `kreait/clock` to `^1.0.1` (1.0.0 had PHPUnit required as a non-dev dependency)

## 1.9.0 - 2019-08-26

- Re-implemented the functionality in the `Kreait\Firebase\JWT` namespace.
- Added `Kreait\Firebase\JWT\CustomTokenGenerator` as the recommended replacement for `Firebase\Auth\Token\Generator`
- Added `Kreait\Firebase\JWT\IdTokenVerifier` as the recommended replacement for `Firebase\Auth\Token\Verifier`
- After updating, please refer to the [Migration Documentation](MIGRATE-1.x-to-2.0.md) to be ready for the 2.0 release of this library.

## 1.8.1 - 2019-08-20

- `Firebase\Auth\Token\Exception\InvalidToken` can now have any `Throwable` as the `$previous` parameter.

## 1.8.0 - 2019-06-12

- The "auth_time" and "iat" claims are now verified with a 5 minute leeway, 
  this is the [same behaviour as in the Firebase Admin .NET SDK](https://github.com/firebase/firebase-admin-dotnet/pull/29) 
  (thanks [@navee85](https://github.com/navee85))

## 1.7.2 - 2018-10-27

- ID Tokens must have a valid "auth_time" claim.
- The signature of an ID Token is now verified even if a prior error occured (thanks [@kanoblake](https://github.com/kanoblake) for reporting the issue and providing a test case)
- Tokens with an invalid signature now throw a `Firebase\Auth\Token\Exception\InvalidSignature` exception.
  It extends the previously thrown `Firebase\Auth\Token\Exception\InvalidToken`,
  so existing behaviour doesn't change.

## 1.7.1 - 2018-01-07

- Fix bug that not more than one custom token could be created at a time.

## 1.7.0 - 2018-01-03

- Cache results from the HTTP Key Store in a PSR-16 cache (default: in memory)
- Deprecated `Firebase\Auth\Token\Handler`.

## 1.6.1 - 2017-07-12

- Add missing `$expiresAt` parameter when creating a custom token with the Handler.

## 1.6.0 - 2017-07-12

- Allow a custom expiration time for custom tokens. 

## 1.5.0 - 2017-04-03

- Allow the usage of a custom key store when using the Handler.

## 1.4.0 - 2017-03-15

- Token verification now includes existence checks for claims (follow up to [kreait/firebase-php#70](https://github.com/kreait/firebase-php/issues/70))

## 1.3.0 - 2017-03-02

- Tokens that seem to be issued in the future now cause a `Firebase\Auth\Token\Exception\IssuedInTheFuture`
  exception. It includes the hint that the system time might not be correct.

## 1.2.1 - 2017-03-01

- Fixed message on UnknownKey exceptions.

## 1.2.0 - 2017-02-28

- Expired tokens now throw a `Firebase\Auth\Token\Exception\ExpiredToken` exception. It
  extends the previously thrown `Firebase\Auth\Token\Exception\InvalidToken`, so
  existing behaviour doesn't change.

## 1.1.1 - 2017-02-19

- Fixed [https://github.com/kreait/firebase-php/issues/65](kreait/firebase-php#65):
  invalid custom token when no claims are given. 

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

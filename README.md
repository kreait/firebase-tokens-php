# Firebase Tokens

A library to work with [Google Firebase](https://firebase.google.com) tokens. You can use it to 
[create custom tokens](https://firebase.google.com/docs/auth/admin/create-custom-tokens) and 
[verify ID Tokens](https://firebase.google.com/docs/auth/admin/verify-id-tokens).

Achieve more with the [Firebase Admin SDK](https://github.com/kreait/firebase-php) for PHP (which uses this library).

[![Current version](https://img.shields.io/packagist/v/kreait/firebase-tokens.svg)](https://packagist.org/packages/kreait/firebase-tokens)
[![Supported PHP version](https://img.shields.io/packagist/php-v/kreait/firebase-tokens.svg)]()
[![Monthly Downloads](https://img.shields.io/packagist/dm/kreait/firebase-tokens.svg)](https://packagist.org/packages/kreait/firebase-tokens/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/kreait/firebase-tokens.svg)](https://packagist.org/packages/kreait/firebase-tokens/stats)
[![Discord](https://img.shields.io/discord/523866370778333184.svg?color=7289da&logo=discord)](https://discord.gg/nbgVfty)

[![Build Status](https://travis-ci.org/kreait/firebase-tokens-php.svg?branch=master)](https://travis-ci.org/kreait/firebase-tokens-php)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=kreait_firebase-tokens-php&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=kreait_firebase-tokens-php)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=kreait_firebase-tokens-php&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=kreait_firebase-tokens-php)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=kreait_firebase-tokens-php&metric=coverage)](https://sonarcloud.io/dashboard?id=kreait_firebase-tokens-php) 

- [Installation](#installation)
- [Simple Usage](#simple-usage)
  - [Create a custom token](#create-a-custom-token)
  - [Verify an ID token](#verify-an-id-token)
  - [Tokens](#tokens)
- [Advanced Usage](#advanced-usage)
  - [Cache results from the Google Secure Token Store](#cache-results-from-the-google-secure-token-store)

## Installation

```bash
composer require kreait/firebase-tokens
```

## Simple usage

### Create a custom token

```php
<?php

use Kreait\Firebase\JWT\CustomTokenGenerator;

$clientEmail = '...';
$privateKey = '...';

$generator = CustomTokenGenerator::withClientEmailAndPrivateKey($clientEmail, $privateKey);
$token = $generator->createCustomToken('uid', ['first_claim' => 'first_value' /* ... */]);

echo $token;
// Output: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.e...
```

### Verify an ID token

```php
<?php

use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;

$projectId = '...';
$idToken = '...';

$verifier = IdTokenVerifier::createWithProjectId($projectId);

try {
    $token = $verifier->verifyIdToken($idToken);
} catch (IdTokenVerificationFailed $e) {
    echo $e->getMessage();
    // Example Output:
    // The value 'eyJhbGciOiJSUzI...' is not a verified ID token:
    // - The token is expired.
    exit;
}

try {
    $token = $verifier->verifyIdTokenWithLeeway($idToken, $leewayInSeconds = 10000000);
} catch (IdTokenVerificationFailed $e) {
    print $e->getMessage();
    exit;
}
```

### Tokens

Tokens returned from the Generator and Verifier are instances of `Kreait\Firebase\JWT\Token`.

```php
$tokenHeaders = $token->getHeaders(); // array
$tokenPayload = token->getPayload(); // array
$tokenString = $token->toString();
$tokenString = (string) $token;
```

## Advanced usage

### Cache results from the Google Secure Token Store

In order to verify ID tokens, the verifier makes a call to fetch Firebase's currently available public
keys. The keys are cached in memory by default.

If you want to cache the public keys more effectively, you can use an implementation of 
[psr/simple-cache](https://packagist.org/providers/psr/simple-cache-implementation) or
[psr/cache](https://packagist.org/providers/psr/cache-implementation) to wrap the 

Example using the [Symfony Cache Component](https://symfony.com/doc/current/components/cache.html)

```php
use Kreait\Firebase\JWT\IdTokenVerifier;
use Symfony\Component\Cache\Simple\FilesystemCache;

$cache = new FilesystemCache();

$verifier = IdTokenVerifier::createWithProjectIdAndCache($projectId, $cache);
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

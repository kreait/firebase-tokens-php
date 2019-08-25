# Firebase Tokens

[![Latest Stable Version](https://poser.pugx.org/kreait/firebase-tokens/v/stable)](https://packagist.org/packages/kreait/firebase-tokens)
[![Total Downloads](https://poser.pugx.org/kreait/firebase-tokens/downloads)](https://packagist.org/packages/kreait/firebase-tokens)
[![Build Status](https://travis-ci.org/kreait/firebase-tokens-php.svg?branch=master)](https://travis-ci.org/kreait/firebase-tokens-php)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kreait/firebase-tokens-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kreait/firebase-tokens-php/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/kreait/firebase-tokens-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kreait/firebase-tokens-php/?branch=master)
[![Discord](https://img.shields.io/discord/523866370778333184.svg?color=7289da&logo=discord)](https://discord.gg/nbgVfty)

A library to work with [Google Firebase](https://firebase.google.com) tokens. You can use it to 
[create custom tokens](https://firebase.google.com/docs/auth/admin/create-custom-tokens) and 
[verify ID Tokens](https://firebase.google.com/docs/auth/admin/verify-id-tokens).

Achieve more with the [Firebase Admin SDK](https://github.com/kreait/firebase-php) for PHP (which uses this library). 

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
$customToken = $generator->createCustomToken('uid', ['first_claim' => 'first_value' /* ... */]);

echo $customToken;
// Output: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.e...
```

### Verify an ID token

```php
<?php

use Kreait\Firebase\JWT\Action\VerifyIdToken\Error\IdTokenVerificationFailed;
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

print_r($token->headers());
print_r($token->payload());

```

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

# Firebase Tokens

[![Latest Stable Version](https://poser.pugx.org/kreait/firebase-tokens/v/stable)](https://packagist.org/packages/kreait/firebase-tokens)
[![Total Downloads](https://poser.pugx.org/kreait/firebase-tokens/downloads)](https://packagist.org/packages/kreait/firebase-tokens)
[![Build Status](https://travis-ci.org/kreait/firebase-tokens-php.svg?branch=master)](https://travis-ci.org/kreait/firebase-tokens-php)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kreait/firebase-tokens-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kreait/firebase-tokens-php/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/kreait/firebase-tokens-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kreait/firebase-tokens-php/?branch=master)

A library to work with [Google Firebase](https://firebase.google.com) tokens. You can use it to 
[create custom tokens](https://firebase.google.com/docs/auth/admin/create-custom-tokens) and 
[verify ID Tokens](https://firebase.google.com/docs/auth/admin/verify-id-tokens). 

## Installation

```
composer require kreait/firebase-tokens
```

## Create a custom token

```php
use Firebase\Auth\Token\Generator;

$generator = new Generator($clientEmail, $privateKey);

$uid = 'a-uid';
$claims = ['foo' => 'bar'];

$token = $generator->createCustomToken($uid, $claims); // Returns a Lcobucci\JWT\Token instance

echo $token; // "eyJ0eXAiOiJKV1..."
```

## Verify an ID token

```php
use Firebase\Auth\Token\Verifier;

$verifier = new Verifier($projectId);

$idTokenString = 'eyJhbGciOiJSUzI1...';

try {
    $verifiedIdToken = $verifier->verifyIdToken($idTokenString);
    
    echo $verifiedIdToken->getClaim('sub'); // "a-uid"
} catch (\Firebase\Auth\Token\Exception\ExpiredToken $e) {
    echo $e->getMessage();
} catch (\Firebase\Auth\Token\Exception\IssuedInTheFuture $e) {
    echo $e->getMessage();
} catch (\Firebase\Auth\Token\Exception\InvalidToken $e) {
    echo $e->getMessage();
}
```

### Cache results from the Google Secure Token Store

In order to verify ID tokens, the verifier makes a call to fetch Firebase's currently available public
keys. The keys are cached in memory by default.

If you want to cache the public keys more effectively, you can use any [implementation of 
psr/simple-cache](https://packagist.org/providers/psr/simple-cache-implementation).

Example using the [Symfony Cache Component](https://symfony.com/doc/current/components/cache.html)

```php
use Firebase\Auth\Token\HttpKeyStore;
use Firebase\Auth\Token\Verifier;
use Symfony\Component\Cache\Simple\FilesystemCache;

$cache = new FilesystemCache();
$keyStore = new HttpKeyStore(null, $cache);

$verifier = new Verifier($projectId, $keyStore); 
```

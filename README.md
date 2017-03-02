# Firebase Tokens

[![Latest Stable Version](https://poser.pugx.org/kreait/firebase-tokens/v/stable)](https://packagist.org/packages/kreait/firebase-tokens)
[![Total Downloads](https://poser.pugx.org/kreait/firebase-tokens/downloads)](https://packagist.org/packages/kreait/firebase-tokens)

A library to work with [Google Firebase](https://firebase.google.com>) tokens. You can use it to 
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

## Firebase Token Handler

`Firebase\Auth\Token\Handler` combines the Generator and the Verifier:

```php
use Firebase\Auth\Token\Handler;

$handler = new Handler($projectId, $clientEmail, $privateKey);

$customToken = $handler->createCustomToken($uid, $claims);
$verifiedIdToken = $handler->verifyIdToken($idTokenString);
```

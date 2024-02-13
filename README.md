# Firebase Tokens

A library to work with [Google Firebase](https://firebase.google.com) tokens. You can use it to 
[create custom tokens](https://firebase.google.com/docs/auth/admin/create-custom-tokens) and 
[verify ID Tokens](https://firebase.google.com/docs/auth/admin/verify-id-tokens).

Achieve more with the [Firebase Admin SDK](https://github.com/kreait/firebase-php) for PHP (which uses this library).

[![Current version](https://img.shields.io/packagist/v/kreait/firebase-tokens.svg)](https://packagist.org/packages/kreait/firebase-tokens)
[![Supported PHP version](https://img.shields.io/packagist/php-v/kreait/firebase-tokens.svg)]()
[![Monthly Downloads](https://img.shields.io/packagist/dm/kreait/firebase-tokens.svg)](https://packagist.org/packages/kreait/firebase-tokens/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/kreait/firebase-tokens.svg)](https://packagist.org/packages/kreait/firebase-tokens/stats)
[![Tests](https://github.com/kreait/firebase-tokens-php/workflows/Tests/badge.svg)](https://github.com/kreait/firebase-tokens-php/actions)
[![Sponsor](https://img.shields.io/static/v1?logo=GitHub&label=Sponsor&message=%E2%9D%A4&color=ff69b4)](https://github.com/sponsors/jeromegamez)

---

## The future of the Firebase Admin PHP SDK

Please read about the future of the Firebase Admin PHP SDK on the
[SDK's GitHub Repository](https://github.com/kreait/firebase-php).

---

- [Installation](#installation)
- [Simple Usage](#simple-usage)
  - [Create a custom token](#create-a-custom-token)
  - [Verify an ID token](#verify-an-id-token)
  - [Verify a Session Cookie](#verify-a-session-cookie)
  - [Tokens](#tokens)
  - [Tenant Awareness](#tenant-awareness) 
- [Advanced Usage](#advanced-usage)
  - [Cache results from the Google Secure Token Store](#cache-results-from-the-google-secure-token-store)
- [Supported Versions](#supported-versions)

## Installation

```bash
composer require kreait/firebase-tokens
```

## Simple usage

### Create a custom token

More information on what a custom token is and how it can be used can be found in 
[Google's official documentation](https://firebase.google.com/docs/auth/admin/create-custom-tokens).

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

The ID token verification methods included in the Firebase Admin SDKs are meant to verify 
ID tokens that come from the client SDKs, not the custom tokens that you create with the Admin SDKs. 
See [Auth tokens](https://firebase.google.com/docs/auth/users/#auth_tokens) for more information.

```php
<?php

use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;

$projectId = '...';
$idToken = 'eyJhb...'; // An ID token given to your backend by a Client application

$verifier = IdTokenVerifier::createWithProjectId($projectId);

try {
    $token = $verifier->verifyIdToken($idToken);
} catch (IdTokenVerificationFailed $e) {
    echo $e->getMessage();
    // Example Output:
    // The value 'eyJhb...' is not a verified ID token:
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

### Verify a Session Cookie

Session cookie verification is similar to ID Token verification.

See [Manage Session Cookies](https://firebase.google.com/docs/auth/admin/manage-cookies) for more information.

```php
<?php

use Kreait\Firebase\JWT\Error\SessionCookieVerificationFailed;
use Kreait\Firebase\JWT\SessionCookieVerifier;

$projectId = '...';
$sessionCookie = 'eyJhb...'; // A session cookie given to your backend by a Client application

$verifier = SessionCookieVerifier::createWithProjectId($projectId);

try {
    $token = $verifier->verifySessionCookie($sessionCookie);
} catch (SessionCookieVerificationFailed $e) {
    echo $e->getMessage();
    // Example Output:
    // The value 'eyJhb...' is not a verified ID token:
    // - The token is expired.
    exit;
}

try {
    $token = $verifier->verifySessionCookieWithLeeway($sessionCookie, $leewayInSeconds = 10000000);
} catch (SessionCookieVerificationFailed $e) {
    print $e->getMessage();
    exit;
}
```

### Tokens

Tokens returned from the Generator and Verifier are instances of `\Kreait\Firebase\JWT\Contract\Token` and
represent a [JWT](https://jwt.io/). The displayed outputs are examples and vary depending on
the information associated with the given user in your project's auth database.

According to the JWT specification, you can expect the following payload fields to be always 
available: `iss`, `aud`, `auth_time`, `sub`, `iat`, `exp`. Other fields depend on the
authentication method of the given account and the information stored in your project's
Auth database.

```php
$token = $verifier->verifyIdToken('eyJhb...'); // An ID token given to your backend by a Client application

echo json_encode($token->headers(), JSON_PRETTY_PRINT);
// {
//     "alg": "RS256",
//     "kid": "e5a91d9f39fa4de254a1e89df00f05b7e248b985",
//     "typ": "JWT"
// }                                                   

echo json_encode($token->payload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
// {
//     "name": "Jane Doe",
//     "picture": "https://domain.tld/picture.jpg",
//     "iss": "https://securetoken.google.com/your-project-id",
//     "aud": "your-project-id",
//     "auth_time": 1580063945,
//     "user_id": "W0IturDwy4TYTmX6ilkd2ZbAXRp2",
//     "sub": "W0IturDwy4TYTmX6ilkd2ZbAXRp2",
//     "iat": 1580063945,
//     "exp": 1580067545,
//     "email": "jane@doe.tld",
//     "email_verified": true,
//     "phone_number": "+1234567890",
//     "firebase": {
//         "identities": {
//             "phone": [
//                 "+1234567890"
//             ],
//             "email": [
//                 "jane@doe.tld"
//             ]
//         },
//         "sign_in_provider": "custom"
//     }
// }

echo $token->toString();
// eyJhb...

$tokenString = (string) $token; // string
// eyJhb...
```

### Tenant Awareness

You can create custom tokens that are scoped to a given tenant:

```php
<?php

use Kreait\Firebase\JWT\CustomTokenGenerator;

$generator = CustomTokenGenerator::withClientEmailAndPrivateKey('...', '...');

$tenantAwareGenerator = $generator->withTenantId('my-tenant-id');
```

Similarly, you can verify that ID tokens were issued in the scope of a given tenant:

```php
<?php

use Kreait\Firebase\JWT\IdTokenVerifier;

$verifier = IdTokenVerifier::createWithProjectId('my-project-id');

$tenantAwareVerifier = $verifier->withExpectedTenantId('my-tenant-id');
```

Session cookies currently don't support tenants.

## Advanced usage

### Cache results from the Google Secure Token Store

In order to verify ID tokens, the verifier makes a call to fetch Firebase's currently available public
keys. The keys are cached in memory by default.

If you want to cache the public keys more effectively, you can initialize the verifier with an 
implementation of [psr/simple-cache](https://packagist.org/providers/psr/simple-cache-implementation)
or [psr/cache](https://packagist.org/providers/psr/cache-implementation) to reduce the amount
of HTTP requests to Google's servers. 

Here's an example using the [Symfony Cache Component](https://symfony.com/doc/current/components/cache.html):

```php
use Kreait\Firebase\JWT\IdTokenVerifier;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$cache = new FilesystemAdapter();

$verifier = IdTokenVerifier::createWithProjectIdAndCache($projectId, $cache);
```

## Supported Versions

**Only the latest version is actively supported.**

Earlier versions will receive security fixes as long as their **lowest** PHP requirement receives security fixes. For
example, when a version supports PHP 7.4 and PHP 8.0, security support will end when security support for PHP 7.4 ends.

| Version | Initial Release | Supported PHP Versions   | Status           |
|---------|-----------------|--------------------------|------------------|
| `5.x`   | 25 Nov 2023     | `~8.1.0, ~8.2.0, ~8.3.0` | Active           |
| `4.x`   | 26 Nov 2022     | `~8.1.0, ~8.2.0`         | Security Support |
| `3.x`   | 25 Apr 2022     | `^7.4, ^8.0`             | End of life      |
| `2.x`   | 03 Jan 2022     | `^7.4, ^8.0`             | End of life      |
| `1.x`   | 06 Feb 2017     | `>=5.5`                  | End of life      |

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

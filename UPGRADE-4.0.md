# Upgrade from 3.x to 4.0

## Introduction

The list of breaking changes below should not be an issue if you've been using the library as intended and documented.

## Complete list of breaking changes

The following list has been generated with [roave/backward-compatibility-check](https://github.com/Roave/BackwardCompatibilityCheck).

```
[BC] CHANGED: The parameter $value of Kreait\Firebase\JWT\Value\Duration::make() changed from no type to a non-contravariant self|DateInterval|int|string
[BC] CHANGED: The parameter $other of Kreait\Firebase\JWT\Value\Duration#isLargerThan() changed from no type to a non-contravariant self|DateInterval|int|string
[BC] CHANGED: The parameter $other of Kreait\Firebase\JWT\Value\Duration#equals() changed from no type to a non-contravariant self|DateInterval|int|string
[BC] CHANGED: The parameter $other of Kreait\Firebase\JWT\Value\Duration#isSmallerThan() changed from no type to a non-contravariant self|DateInterval|int|string
[BC] CHANGED: The parameter $other of Kreait\Firebase\JWT\Value\Duration#compareTo() changed from no type to a non-contravariant self|DateInterval|int|string
[BC] CHANGED: The parameter $duration of Kreait\Firebase\JWT\Action\FetchGooglePublicKeys#ifKeysDoNotExpireCacheFor() changed from no type to a non-contravariant Kreait\Firebase\JWT\Value\Duration|DateInterval|string|int
[BC] CHANGED: The parameter $ttl of Kreait\Firebase\JWT\Action\CreateCustomToken#withTimeToLive() changed from no type to a non-contravariant Kreait\Firebase\JWT\Value\Duration|DateInterval|string|int
[BC] CHANGED: The parameter $timeToLive of Kreait\Firebase\JWT\CustomTokenGenerator#createCustomToken() changed from no type to a non-contravariant Kreait\Firebase\JWT\Value\Duration|DateInterval|string|int|null
[BC] REMOVED: Class Kreait\Firebase\JWT\Error\DiscoveryFailed has been deleted
```

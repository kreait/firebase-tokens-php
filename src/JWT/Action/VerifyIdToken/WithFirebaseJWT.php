<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\VerifyIdToken;

use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Kreait\Clock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Throwable;
use UnexpectedValueException;

final class WithFirebaseJWT implements Handler
{
    /** @var string */
    private $projectId;

    /** @var Keys */
    private $keys;

    /** @var Clock */
    private $clock;

    public function __construct(string $projectId, Keys $keys, Clock $clock)
    {
        $this->projectId = $projectId;
        $this->keys = $keys;
        $this->clock = $clock;
    }

    public function handle(VerifyIdToken $action): Token
    {
        $tokenString = $action->token();

        if (empty($this->keys->all())) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, ["No keys are available to verify the token's signature."]);
        }

        $timestampBackup = JWT::$timestamp;
        $leewayBackup = JWT::$leeway;

        $now = $this->clock->now();

        JWT::$timestamp = $now->getTimestamp();
        JWT::$leeway = $leeway = $action->leewayInSeconds();

        try {
            // This will check kid, nbf, iat, exp and the signature
            $token = JWT::decode($tokenString, $this->keys->all(), ['RS256']);
        } catch (DomainException $e) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, [
                'The token could not be decoded.',
                $e->getMessage(),
            ]);
        } catch (BeforeValidException $e) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, [
                'The token has been issued for future use.',
                $e->getMessage(),
            ]);
        } catch (ExpiredException $e) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, [
                'The token is expired.',
            ]);
        } catch (UnexpectedValueException $e) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, [
                'The token is invalid.',
                $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, [
                'The token is invalid.',
                $e->getMessage(),
            ]);
        } finally {
            $this->restoreJWTStaticVariables($timestampBackup, $leewayBackup);
        }

        $errors = [];

        $audience = $token->aud ?? null;
        if (!$audience || $audience !== $this->projectId) {
            $errors[] = "The token's audience doesn't match the current Firebase project. Expected '{$this->projectId}', got '{$audience}'.";
        }

        $issuer = $token->iss ?? null;
        $expectedIssuer = 'https://securetoken.google.com/'.$this->projectId;
        if (!$issuer || $issuer !== $expectedIssuer) {
            $errors[] = "The token was issued by the wrong principal. Expected '{$expectedIssuer}', got '{$issuer}'";
        }

        $subject = $token->sub ?? null;
        if (!$subject || !is_string($subject) || trim($subject) === '') {
            $errors[] = "The token's 'sub' claim must be a non-empty string. Got: '{$subject}' (".gettype($subject).')';
        }

        $authTime = $token->auth_time ?? null;
        if (!$authTime || ($authTime > ($now->getTimestamp() + $leeway))) {
            $errors[] = "The token's 'auth_time' claim (the time when the user authenticated) must be present and be in the past.";
        }

        $this->restoreJWTStaticVariables($timestampBackup, $leewayBackup);

        if (!empty($errors)) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, $errors);
        }

        // We replicate what's done in JWT::decode(), but have to re-encode/decode it
        // to get an array instead of an object
        list($headb64, $bodyb64) = explode('.', $tokenString);
        $headers = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64));
        $headers = json_decode(json_encode($headers), true, 512, JSON_BIGINT_AS_STRING);
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
        $payload = json_decode(json_encode($payload), true, 512, JSON_BIGINT_AS_STRING);

        return TokenInstance::withValues($tokenString, $headers, $payload);
    }

    private function restoreJWTStaticVariables($timestamp, $leeway)
    {
        JWT::$timestamp = $timestamp;
        JWT::$leeway = $leeway;
    }
}

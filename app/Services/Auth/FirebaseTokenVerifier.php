<?php

namespace App\Services\Auth;

use App\DataTransferObjects\FirebaseIdentity;
use App\Services\Auth\Contracts\FirebaseTokenVerifierInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirebaseTokenVerifier implements FirebaseTokenVerifierInterface
{
    public const CERTS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    public function verify(string $idToken): FirebaseIdentity
    {
        $projectId = (string) config('services.firebase.project_id', '');

        if ($projectId === '') {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $header = $this->decodeJson($headerB64);
        $payload = $this->decodeJson($payloadB64);

        if (! is_array($header) || ! is_array($payload)) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        $kid = $header['kid'] ?? null;
        $alg = $header['alg'] ?? null;

        if (! is_string($kid) || $kid === '' || $alg !== 'RS256') {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        $cert = $this->certificateForKid($kid);

        $signature = $this->base64UrlDecode($signatureB64);
        $verified = openssl_verify(
            $headerB64.'.'.$payloadB64,
            $signature,
            $cert,
            OPENSSL_ALGO_SHA256,
        );

        if ($verified !== 1) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        $this->assertClaims($payload, $projectId);

        $uid = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;
        $provider = data_get($payload, 'firebase.sign_in_provider');

        if (! is_string($uid) || $uid === '' || ! is_string($provider) || $provider === '') {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        return new FirebaseIdentity(
            uid: $uid,
            email: is_string($email) && $email !== '' ? $email : null,
            name: is_string($name) && trim($name) !== '' ? trim($name) : null,
            emailVerified: (bool) ($payload['email_verified'] ?? false),
            signInProvider: $provider,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function assertClaims(array $payload, string $projectId): void
    {
        $now = time();
        $exp = $payload['exp'] ?? null;
        $iat = $payload['iat'] ?? null;
        $aud = $payload['aud'] ?? null;
        $iss = $payload['iss'] ?? null;

        if (! is_numeric($exp) || (int) $exp < $now) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        if (! is_numeric($iat) || (int) $iat > $now + 60) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        if ($aud !== $projectId) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        if ($iss !== 'https://securetoken.google.com/'.$projectId) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }
    }

    protected function certificateForKid(string $kid): string
    {
        $certs = Cache::remember('firebase.securetoken.certs', 3600, function () {
            $response = Http::timeout(10)->acceptJson()->get(self::CERTS_URL);

            if (! $response->successful() || ! is_array($response->json())) {
                throw new AuthenticationException(__('api.user.social_invalid'));
            }

            return $response->json();
        });

        $cert = $certs[$kid] ?? null;

        if (! is_string($cert) || $cert === '') {
            Cache::forget('firebase.securetoken.certs');

            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        return $cert;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeJson(string $encoded): ?array
    {
        $json = $this->base64UrlDecode($encoded);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        return $decoded;
    }
}

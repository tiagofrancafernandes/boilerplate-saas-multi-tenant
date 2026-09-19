<?php

declare(strict_types=1);

namespace App\Services\Infra;

use Throwable;

if (!class_exists(QStashSignatureVerifier::class)) {
    final class QStashSignatureVerifier
    {
        public static function verify(
            string $rawBody,
            string $signatureJwt,
            ?string $currentKey,
            ?string $nextKey = null,
            ?string $expectedUrl = null
        ): bool {
            try {
                return static::doVerify($rawBody, $signatureJwt, $currentKey, $nextKey, $expectedUrl);
            } catch (Throwable) {
                return false;
            }
        }

        private static function doVerify(
            string $rawBody,
            string $signatureJwt,
            ?string $currentKey,
            ?string $nextKey,
            ?string $expectedUrl
        ): bool {
            $parts = explode('.', trim($signatureJwt));

            if (count($parts) !== 3) {
                return false;
            }

            [$rawHeader, $rawClaims, $rawSig] = $parts;
            $decodedSig = static::base64UrlDecode($rawSig);

            if ($decodedSig === false) {
                return false;
            }

            $keys = array_filter([$currentKey, $nextKey]);

            if (empty($keys)) {
                return false;
            }

            $validKeyFound = false;

            foreach ($keys as $key) {
                $expectedSig = hash_hmac('sha256', "{$rawHeader}.{$rawClaims}", (string) $key, true);

                if (hash_equals($expectedSig, $decodedSig)) {
                    $validKeyFound = true;

                    break;
                }
            }

            if (!$validKeyFound) {
                return false;
            }

            $claimsJson = static::base64UrlDecode($rawClaims);

            if ($claimsJson === false) {
                return false;
            }

            $claims = json_decode($claimsJson, true);

            if (!is_array($claims)) {
                return false;
            }

            if (($claims['iss'] ?? null) !== 'Upstash') {
                return false;
            }

            if (isset($claims['exp']) && (int) $claims['exp'] < time()) {
                return false;
            }

            if (isset($claims['nbf']) && (int) $claims['nbf'] > (time() + 60)) {
                return false;
            }

            if (isset($claims['body'])) {
                $bodyHash = hash('sha256', $rawBody);

                if (!hash_equals((string) $claims['body'], $bodyHash)) {
                    return false;
                }
            }

            if ($expectedUrl !== null && isset($claims['sub'])) {
                if (!hash_equals((string) $claims['sub'], $expectedUrl)) {
                    return false;
                }
            }

            return true;
        }

        public static function base64UrlDecode(string $input): string|false
        {
            $remainder = strlen($input) % 4;

            if ($remainder > 0) {
                $input .= str_repeat('=', 4 - $remainder);
            }

            return base64_decode(strtr($input, '-_', '+/'), true);
        }

        public static function base64UrlEncode(string $input): string
        {
            return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
        }

        public static function signPayload(string $rawBody, string $signingKey, array $claims = []): string
        {
            $header = ['alg' => 'HS256', 'typ' => 'JWT'];
            $defaultClaims = [
                'iss' => 'Upstash',
                'exp' => time() + 300,
                'nbf' => time() - 10,
                'body' => hash('sha256', $rawBody),
            ];

            $mergedClaims = array_merge($defaultClaims, $claims);

            $encodedHeader = static::base64UrlEncode((string) json_encode($header));
            $encodedClaims = static::base64UrlEncode((string) json_encode($mergedClaims));

            $signature = hash_hmac('sha256', "{$encodedHeader}.{$encodedClaims}", $signingKey, true);
            $encodedSig = static::base64UrlEncode($signature);

            return "{$encodedHeader}.{$encodedClaims}.{$encodedSig}";
        }
    }
}

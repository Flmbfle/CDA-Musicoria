<?php

namespace App\Service;

use DateTimeImmutable;

class JWTService
{
    public function generate(array $header, array $payload, string $secret, int $validity = 10800): string
    {
        if ($validity > 0)
        {
            $now = new DateTimeImmutable();
            $expiration = $now->getTimestamp() + $validity;

            $payload["iat"] = $now->getTimestamp();
            $payload["exp"] = $expiration;
        }
        // On encode en base64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        // On 'nettoie' les valeurs encodé
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

        //On genere la signature
        $secret = base64_encode($secret);
        $secret = str_replace(['+', '/', '='], ['-', '_', ''], $secret);

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);

        $base64Signature = base64_encode($signature);
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

        // On crée le Token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $signature;

        return $jwt;
    }
    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }

    public function getPayload(string $token): array
    {
        // Démontage Token
        $array = explode('.', $token);

        // On décode le payload
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    public function getHeader(string $token)
    {
        // Démontage Token
        $array = explode('.', $token);

        // On décode le header
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);
        $now = new DateTimeImmutable();

        return $payload["exp"] < $now->getTimestamp();
    }

    // On vérifie la signature du Token
    public function check(string $token, string $secret): bool
    {
        // On recupere le header et le payload
        $header = $this->getHeader($token);
        $payload = $this->getPayload($token);

        // On genere un token de vérification
        $verifToken = $this->generate($header, $payload, $secret, 0);

        return $token === $verifToken;
    }
}
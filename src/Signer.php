<?php

declare(strict_types=1);

namespace EdgeCron;

final class Signer
{
    /**
     * @param array<string, string>|null $query
     */
    public static function sign(string $secret, string $timestamp, ?array $query, string $body): string
    {
        $payload = '';
        if ($query !== null && $query !== []) {
            ksort($query);
            $parts = [];
            foreach ($query as $key => $value) {
                $parts[] = $key . '=' . $value;
            }
            $payload = implode('&', $parts);
        }
        if ($body !== '') {
            $payload = $payload === '' ? $body : $payload . '&' . $body;
        }
        return hash_hmac('sha256', $timestamp . "\n" . $payload, $secret);
    }
}

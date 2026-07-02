<?php

declare(strict_types=1);

namespace EdgeCron;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use Psr\Http\Message\ResponseInterface;

final class Transport
{
    private const MAX_RESPONSE_BYTES = 10485760;

    public function __construct(
        private readonly string $keyId,
        private readonly string $secret,
        private readonly string $baseUrl = 'https://api.edgecron.com',
        private readonly int $timeout = 30,
        private readonly ?Client $client = null
    ) {
    }

    /**
     * @param array<string, string>|null $query
     * @param array<string, mixed>|null $body
     */
    public function requestJson(string $method, string $path, ?array $query, ?array $body): array
    {
        $bodyJson = $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return $this->send($method, $path, $query, $bodyJson, 'application/json');
    }

    /**
     * @param array<string, string> $fields
     * @param list<array{name:string, contents:string, filename:string, headers?:array<string,string>}> $files
     */
    public function requestMultipart(string $path, array $fields, array $files): array
    {
        $parts = [];
        foreach ($fields as $name => $value) {
            $parts[] = ['name' => $name, 'contents' => $value];
        }
        foreach ($files as $file) {
            $parts[] = $file;
        }
        $stream = new MultipartStream($parts);
        return $this->send('POST', $path, null, (string) $stream, 'multipart/form-data; boundary=' . $stream->getBoundary());
    }

    /**
     * @param array<string, string>|null $query
     */
    private function send(string $method, string $path, ?array $query, string $body, string $contentType): array
    {
        $timestamp = (string) time();
        $signature = Signer::sign($this->secret, $timestamp, $query, $body);
        $url = rtrim($this->baseUrl, '/') . $path;
        if ($query !== null && $query !== []) {
            ksort($query);
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $client = $this->client ?? new Client();
        try {
            $response = $client->request($method, $url, [
                'timeout' => $this->timeout,
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => $contentType,
                    'X-Key-ID' => $this->keyId,
                    'X-Timestamp' => $timestamp,
                    'X-Signature' => $signature,
                    'User-Agent' => 'edgecron-php/1.0.0',
                ],
                'body' => $body,
            ]);
        } catch (GuzzleException $exception) {
            throw new \RuntimeException('edgecron: http: ' . $exception->getMessage(), 0, $exception);
        }

        return $this->parseResponse($response);
    }

    private function parseResponse(ResponseInterface $response): array
    {
        $raw = (string) $response->getBody();
        if (strlen($raw) > self::MAX_RESPONSE_BYTES) {
            throw new \RuntimeException('edgecron: response exceeds 10 MB limit');
        }
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $apiError = $this->tryParseAPIError($raw);
            if ($apiError !== null) {
                throw $apiError;
            }
            throw new \RuntimeException('edgecron: http status ' . $response->getStatusCode());
        }

        $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (($payload['code'] ?? 0) !== 0) {
            throw new APIError((int) $payload['code'], (string) ($payload['message'] ?? ''), (string) ($payload['request_id'] ?? ''));
        }
        return (array) ($payload['data'] ?? []);
    }

    private function tryParseAPIError(string $raw): ?APIError
    {
        try {
            $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        if (!isset($payload['code']) || (int) $payload['code'] === 0) {
            return null;
        }
        return new APIError((int) $payload['code'], (string) ($payload['message'] ?? ''), (string) ($payload['request_id'] ?? ''));
    }
}

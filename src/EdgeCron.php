<?php

declare(strict_types=1);

namespace EdgeCron;

use GuzzleHttp\Client;

final class EdgeCron
{
    public readonly ScheduleService $schedules;
    public readonly TaskService $tasks;
    public readonly EventService $events;
    public readonly EndpointService $endpoints;
    public readonly DeliveryService $deliveries;
    public readonly RetryService $retries;
    public readonly SubscriptionService $subscription;

    public function __construct(string $keyId, string $secret, array $options = [])
    {
        if (!preg_match('/^ak_[0-9a-zA-Z_]+$/', $keyId)) {
            throw new \InvalidArgumentException('edgecron: keyId must match ak_<hex>, got: ' . $keyId);
        }
        if ($secret === '') {
            throw new \InvalidArgumentException('edgecron: secret must not be empty');
        }
        $transport = new Transport(
            $keyId,
            $secret,
            $options['base_url'] ?? 'https://api.edgecron.com',
            $options['timeout'] ?? 30,
            $options['http_client'] ?? null
        );
        $this->schedules = new ScheduleService($transport);
        $this->tasks = new TaskService($transport);
        $this->events = new EventService($transport);
        $this->endpoints = new EndpointService($transport);
        $this->deliveries = new DeliveryService($transport);
        $this->retries = new RetryService($transport);
        $this->subscription = new SubscriptionService($transport);
    }
}

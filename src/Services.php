<?php

declare(strict_types=1);

namespace EdgeCron;

// --- Schedules ---

final class ScheduleService
{
    public function __construct(private readonly Transport $transport) {}

    public function create(string $name, string $cronExpr, ?string $timezone = null, ?string $payload = null, ?array $endpointIds = null): Schedule
    {
        $body = ['name' => $name, 'cron_expr' => $cronExpr];
        if ($timezone !== null) $body['timezone'] = $timezone;
        if ($payload !== null) $body['payload'] = $payload;
        if ($endpointIds !== null) $body['endpoint_ids'] = $endpointIds;
        return hydrate(Schedule::class, $this->transport->requestJson('POST', '/v1/schedules', null, $body));
    }

    public function update(int $id, ?string $name = null, ?string $cronExpr = null, ?string $timezone = null, ?string $payload = null, ?array $endpointIds = null): Schedule
    {
        $body = [];
        if ($name !== null) $body['name'] = $name;
        if ($cronExpr !== null) $body['cron_expr'] = $cronExpr;
        if ($timezone !== null) $body['timezone'] = $timezone;
        if ($payload !== null) $body['payload'] = $payload;
        if ($endpointIds !== null) $body['endpoint_ids'] = $endpointIds;
        return hydrate(Schedule::class, $this->transport->requestJson('PATCH', '/v1/schedules/' . $id, null, $body));
    }

    public function get(int $id): Schedule
    {
        return hydrate(Schedule::class, $this->transport->requestJson('GET', '/v1/schedules/' . $id, null, null));
    }

    public function list(int $page = 1, int $pageSize = 20, string $status = ''): ScheduleList
    {
        $query = ['page' => (string) $page, 'page_size' => (string) $pageSize];
        if ($status !== '') $query['status'] = $status;
        return hydrate(ScheduleList::class, $this->transport->requestJson('GET', '/v1/schedules', $query, null));
    }

    public function delete(int $id): void
    {
        $this->transport->requestJson('DELETE', '/v1/schedules/' . $id, null, null);
    }

    public function pause(int $id): void
    {
        $this->transport->requestJson('POST', '/v1/schedules/' . $id . '/pause', null, null);
    }

    public function resume(int $id): void
    {
        $this->transport->requestJson('POST', '/v1/schedules/' . $id . '/resume', null, null);
    }
}

// --- Tasks ---

final class TaskService
{
    public function __construct(private readonly Transport $transport) {}

    public function create(int $endpointId, ?string $payload = null, ?int $runAt = null): Task
    {
        $body = ['endpoint_id' => $endpointId];
        if ($payload !== null) $body['payload'] = $payload;
        if ($runAt !== null) $body['run_at'] = $runAt;
        return hydrate(Task::class, $this->transport->requestJson('POST', '/v1/tasks', null, $body));
    }

    public function get(int $id): Task
    {
        return hydrate(Task::class, $this->transport->requestJson('GET', '/v1/tasks/' . $id, null, null));
    }

    public function list(int $page = 1, int $pageSize = 20, string $status = '', int $scheduleId = 0, int $eventId = 0): TaskList
    {
        $query = ['page' => (string) $page, 'page_size' => (string) $pageSize];
        if ($status !== '') $query['status'] = $status;
        if ($scheduleId > 0) $query['schedule_id'] = (string) $scheduleId;
        if ($eventId > 0) $query['event_id'] = (string) $eventId;
        return hydrate(TaskList::class, $this->transport->requestJson('GET', '/v1/tasks', $query, null));
    }

    public function cancel(int $id): void
    {
        $this->transport->requestJson('POST', '/v1/tasks/' . $id . '/cancel', null, null);
    }
}

// --- Events ---

final class EventService
{
    public function __construct(private readonly Transport $transport) {}

    public function publish(string $eventName, string $eventKey, ?string $payload = null): PublishEventResult
    {
        $body = ['event_name' => $eventName, 'event_key' => $eventKey];
        if ($payload !== null) $body['payload'] = $payload;
        return hydrate(PublishEventResult::class, $this->transport->requestJson('POST', '/v1/events', null, $body));
    }

    public function get(int $id): Event
    {
        return hydrate(Event::class, $this->transport->requestJson('GET', '/v1/events/' . $id, null, null));
    }

    public function list(int $page = 1, int $pageSize = 20, string $eventName = '', string $status = ''): EventList
    {
        $query = ['page' => (string) $page, 'page_size' => (string) $pageSize];
        if ($eventName !== '') $query['event_name'] = $eventName;
        if ($status !== '') $query['status'] = $status;
        return hydrate(EventList::class, $this->transport->requestJson('GET', '/v1/events', $query, null));
    }

    public function enable(int $id): void
    {
        $this->transport->requestJson('POST', '/v1/events/' . $id . '/enable', null, null);
    }

    public function disable(int $id): void
    {
        $this->transport->requestJson('POST', '/v1/events/' . $id . '/disable', null, null);
    }

    public function delete(int $id): void
    {
        $this->transport->requestJson('DELETE', '/v1/events/' . $id, null, null);
    }
}

// --- Endpoints ---

final class EndpointService
{
    public function __construct(private readonly Transport $transport) {}

    public function create(string $name, string $url, string $method = 'POST', ?string $headers = null, ?string $secret = null, ?int $timeoutMs = null, ?int $retryPolicyId = null, ?string $filterEvents = null): WebhookEndpoint
    {
        $body = ['name' => $name, 'url' => $url, 'method' => $method];
        if ($headers !== null) $body['headers'] = $headers;
        if ($secret !== null) $body['secret'] = $secret;
        if ($timeoutMs !== null) $body['timeout_ms'] = $timeoutMs;
        if ($retryPolicyId !== null) $body['retry_policy_id'] = $retryPolicyId;
        if ($filterEvents !== null) $body['filter_events'] = $filterEvents;
        return hydrate(WebhookEndpoint::class, $this->transport->requestJson('POST', '/v1/endpoints', null, $body));
    }

    public function get(int $id): WebhookEndpoint
    {
        return hydrate(WebhookEndpoint::class, $this->transport->requestJson('GET', '/v1/endpoints/' . $id, null, null));
    }

    public function update(int $id, ?string $name = null, ?string $url = null, ?string $method = null, ?string $headers = null, ?string $secret = null, ?int $timeoutMs = null, ?int $retryPolicyId = null, ?string $filterEvents = null): WebhookEndpoint
    {
        $body = [];
        if ($name !== null) $body['name'] = $name;
        if ($url !== null) $body['url'] = $url;
        if ($method !== null) $body['method'] = $method;
        if ($headers !== null) $body['headers'] = $headers;
        if ($secret !== null) $body['secret'] = $secret;
        if ($timeoutMs !== null) $body['timeout_ms'] = $timeoutMs;
        if ($retryPolicyId !== null) $body['retry_policy_id'] = $retryPolicyId;
        if ($filterEvents !== null) $body['filter_events'] = $filterEvents;
        return hydrate(WebhookEndpoint::class, $this->transport->requestJson('PATCH', '/v1/endpoints/' . $id, null, $body));
    }

    public function list(int $page = 1, int $pageSize = 20, string $status = ''): EndpointList
    {
        $query = ['page' => (string) $page, 'page_size' => (string) $pageSize];
        if ($status !== '') $query['status'] = $status;
        return hydrate(EndpointList::class, $this->transport->requestJson('GET', '/v1/endpoints', $query, null));
    }

    public function delete(int $id): void
    {
        $this->transport->requestJson('DELETE', '/v1/endpoints/' . $id, null, null);
    }

    public function enable(int $id): void
    {
        $this->transport->requestJson('POST', '/v1/endpoints/' . $id . '/enable', null, null);
    }

    public function disable(int $id): void
    {
        $this->transport->requestJson('POST', '/v1/endpoints/' . $id . '/disable', null, null);
    }
}

// --- Deliveries ---

final class DeliveryService
{
    public function __construct(private readonly Transport $transport) {}

    public function list(int $page = 1, int $pageSize = 20, string $status = '', int $taskId = 0, int $endpointId = 0): DeliveryList
    {
        $query = ['page' => (string) $page, 'page_size' => (string) $pageSize];
        if ($status !== '') $query['status'] = $status;
        if ($taskId > 0) $query['task_id'] = (string) $taskId;
        if ($endpointId > 0) $query['endpoint_id'] = (string) $endpointId;
        return hydrate(DeliveryList::class, $this->transport->requestJson('GET', '/v1/deliveries', $query, null));
    }

    public function retry(int $id): RetryDeliveryResult
    {
        return hydrate(RetryDeliveryResult::class, $this->transport->requestJson('POST', '/v1/deliveries/' . $id . '/retry', null, null));
    }
}

// --- Retries ---

final class RetryService
{
    public function __construct(private readonly Transport $transport) {}

    public function createPolicy(string $name, int $maxAttempts = 3, string $backoffType = 'exponential', int $initialDelaySec = 10, int $maxDelaySec = 3600): RetryPolicy
    {
        return hydrate(RetryPolicy::class, $this->transport->requestJson('POST', '/v1/retries/policies', null, [
            'name' => $name,
            'max_attempts' => $maxAttempts,
            'backoff_type' => $backoffType,
            'initial_delay_sec' => $initialDelaySec,
            'max_delay_sec' => $maxDelaySec,
        ]));
    }

    public function getPolicy(int $id): RetryPolicy
    {
        return hydrate(RetryPolicy::class, $this->transport->requestJson('GET', '/v1/retries/policies/' . $id, null, null));
    }

    public function updatePolicy(int $id, ?string $name = null, ?int $maxAttempts = null, ?string $backoffType = null, ?int $initialDelaySec = null, ?int $maxDelaySec = null, ?string $status = null): RetryPolicy
    {
        $body = [];
        if ($name !== null) $body['name'] = $name;
        if ($maxAttempts !== null) $body['max_attempts'] = $maxAttempts;
        if ($backoffType !== null) $body['backoff_type'] = $backoffType;
        if ($initialDelaySec !== null) $body['initial_delay_sec'] = $initialDelaySec;
        if ($maxDelaySec !== null) $body['max_delay_sec'] = $maxDelaySec;
        if ($status !== null) $body['status'] = $status;
        return hydrate(RetryPolicy::class, $this->transport->requestJson('PATCH', '/v1/retries/policies/' . $id, null, $body));
    }

    public function deletePolicy(int $id): void
    {
        $this->transport->requestJson('DELETE', '/v1/retries/policies/' . $id, null, null);
    }

    public function listPolicies(): RetryPolicyList
    {
        return hydrate(RetryPolicyList::class, $this->transport->requestJson('GET', '/v1/retries/policies', null, null));
    }

    public function listJobs(int $page = 1, int $pageSize = 20, string $status = '', int $deliveryId = 0): RetryJobList
    {
        $query = ['page' => (string) $page, 'page_size' => (string) $pageSize];
        if ($status !== '') $query['status'] = $status;
        if ($deliveryId > 0) $query['delivery_id'] = (string) $deliveryId;
        return hydrate(RetryJobList::class, $this->transport->requestJson('GET', '/v1/retries/jobs', $query, null));
    }

    public function cancelJob(int $id): void
    {
        $this->transport->requestJson('POST', '/v1/retries/jobs/' . $id . '/cancel', null, null);
    }
}

// --- Subscription ---

final class SubscriptionService
{
    public function __construct(private readonly Transport $transport) {}

    public function quota(): SubscriptionQuota
    {
        return hydrate(SubscriptionQuota::class, $this->transport->requestJson('GET', '/v1/subscription/quota', null, null));
    }

    public function usage(string $period = ''): UsageRecords
    {
        $query = [];
        if ($period !== '') $query['period'] = $period;
        return hydrate(UsageRecords::class, $this->transport->requestJson('GET', '/v1/subscription/usage', $query !== [] ? $query : null, null));
    }

    public function resourceLimits(): ResourceLimits
    {
        return hydrate(ResourceLimits::class, $this->transport->requestJson('GET', '/v1/subscription/resource-limits', null, null));
    }
}

/**
 * @template T of object
 * @param class-string<T> $className
 * @param array<string,mixed> $data
 * @return T
 */
function hydrate(string $className, array $data): object
{
    $reflection = new \ReflectionClass($className);
    $arguments = [];
    foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
        if (array_key_exists($parameter->getName(), $data)) {
            $arguments[] = $data[$parameter->getName()];
            continue;
        }
        if ($parameter->isDefaultValueAvailable()) {
            $arguments[] = $parameter->getDefaultValue();
            continue;
        }
        $arguments[] = null;
    }
    return $reflection->newInstanceArgs($arguments);
}

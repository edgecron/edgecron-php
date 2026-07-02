<?php

declare(strict_types=1);

namespace EdgeCron;

// --- Schedules ---

final readonly class Schedule
{
    public function __construct(
        public int $id,
        public string $app_id,
        public string $name,
        public string $cron_expr,
        public string $timezone,
        public string $payload,
        public string $status,
        public int $next_run_at,
        public ?array $endpoint_ids,
        public ?array $endpoint_names,
        public int $created_at,
        public int $updated_at,
    ) {}
}

final readonly class ScheduleList
{
    public function __construct(
        public int $total,
        public array $list,
    ) {}
}

// --- Tasks ---

final readonly class Task
{
    public function __construct(
        public int $id,
        public string $app_id,
        public int $schedule_id,
        public int $event_id,
        public int $endpoint_id,
        public string $task_type,
        public string $payload,
        public string $status,
        public int $run_at,
        public int $created_at,
        public int $updated_at,
    ) {}
}

final readonly class TaskList
{
    public function __construct(
        public int $total,
        public array $list,
    ) {}
}

// --- Events ---

final readonly class Event
{
    public function __construct(
        public int $id,
        public string $app_id,
        public string $event_name,
        public string $event_key,
        public string $payload,
        public string $status,
        public int $created_at,
    ) {}
}

final readonly class PublishEventResult
{
    public function __construct(
        public int $id,
        public string $app_id,
        public string $event_name,
        public string $event_key,
        public string $payload,
        public string $status,
        public int $fanout_count,
        public int $created_at,
    ) {}
}

final readonly class EventList
{
    public function __construct(
        public int $total,
        public array $list,
    ) {}
}

// --- Endpoints ---

final readonly class WebhookEndpoint
{
    public function __construct(
        public int $id,
        public string $app_id,
        public string $name,
        public string $url,
        public string $method,
        public string $headers,
        public string $secret,
        public int $timeout_ms,
        public int $retry_policy_id,
        public string $filter_events,
        public string $status,
        public int $created_at,
        public int $updated_at,
    ) {}
}

final readonly class EndpointList
{
    public function __construct(
        public int $total,
        public array $list,
    ) {}
}

// --- Deliveries ---

final readonly class Delivery
{
    public function __construct(
        public int $id,
        public string $app_id,
        public int $task_id,
        public int $endpoint_id,
        public int $attempt,
        public string $status,
        public int $http_status,
        public int $latency_ms,
        public string $request_body_hash,
        public string $error_message,
        public int $next_retry_at,
        public int $created_at,
        public int $updated_at,
    ) {}
}

final readonly class DeliveryList
{
    public function __construct(
        public int $total,
        public array $list,
    ) {}
}

final readonly class RetryDeliveryResult
{
    public function __construct(
        public int $delivery_id,
        public int $retry_job_id,
        public string $status,
    ) {}
}

// --- Retries ---

final readonly class RetryPolicy
{
    public function __construct(
        public int $id,
        public string $app_id,
        public string $name,
        public int $max_attempts,
        public string $backoff_type,
        public int $initial_delay_sec,
        public int $max_delay_sec,
        public string $status,
        public int $created_at,
        public int $updated_at,
    ) {}
}

final readonly class RetryPolicyList
{
    public function __construct(
        public int $total,
        public array $list,
    ) {}
}

final readonly class RetryJob
{
    public function __construct(
        public int $id,
        public string $app_id,
        public int $delivery_id,
        public int $attempt,
        public string $status,
        public int $run_at,
        public int $locked_until,
        public string $last_error,
        public int $created_at,
        public int $updated_at,
    ) {}
}

final readonly class RetryJobList
{
    public function __construct(
        public int $total,
        public array $list,
    ) {}
}

// --- Subscription ---

final readonly class SubscriptionQuota
{
    public function __construct(
        public string $plan_code,
        public string $billing_cycle,
        public int $quota,
        public int $used,
        public int $remaining,
        public bool $exceeded,
        public int $current_period_start,
        public int $current_period_end,
        public float $usage_percent,
    ) {}
}

final readonly class UsageRecordItem
{
    public function __construct(
        public string $event_type,
        public string $period,
        public int $count,
    ) {}
}

final readonly class UsageRecords
{
    public function __construct(
        public string $period,
        public int $total_events,
        public array $items,
    ) {}
}

final readonly class ResourceLimits
{
    public function __construct(
        public int $max_cron_jobs,
        public int $current_cron_jobs,
        public int $max_endpoints,
        public int $current_endpoints,
        public int $log_retention_days,
    ) {}
}

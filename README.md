# EdgeCron PHP SDK

Official PHP SDK for the EdgeCron webhook scheduling and callback delivery platform.

Schedule delayed HTTP requests, deliver webhooks reliably, and automatically retry failed calls — with full execution history so nothing gets lost.

中文文档：[README.zh-CN.md](README.zh-CN.md)

## Install

```bash
composer require edgecron/edgecron-php
```

## Quick Start

```php
<?php

use EdgeCron\EdgeCron;
use EdgeCron\APIError;

$client = new EdgeCron('ak_xxx', 'sk_xxx');

try {
    $schedule = $client->schedules->create('my-schedule', '*/5 * * * *');
    var_dump($schedule->id);
} catch (APIError $error) {
    var_dump($error->codeValue, $error->getMessage(), $error->requestId);
}
```

## Modules

| Client method                    | Description                        |
|----------------------------------|------------------------------------|
| `$client->schedules->*`         | Cron schedule CRUD, pause, resume  |
| `$client->tasks->*`             | Task execution instances, cancel   |
| `$client->events->*`            | Event publishing and management    |
| `$client->endpoints->*`         | Webhook endpoint configuration     |
| `$client->deliveries->*`        | Delivery attempt records and retry |
| `$client->retries->*`           | Retry policies and jobs            |
| `$client->subscription->*`      | Quota, usage, and resource limits  |

## Configuration

- `base_url` — override API base URL
- `timeout` — HTTP client timeout in seconds
- `http_client` — custom Guzzle Client

## Error Handling

Service-side business errors throw `APIError`.

```php
<?php

use EdgeCron\APIError;

try {
    $client->schedules->get(123);
} catch (APIError $error) {
    var_dump($error->codeValue, $error->getMessage(), $error->requestId);
}
```

## Security Notice

This is a server-side SDK. Do not expose `secret` in browsers or mobile apps.

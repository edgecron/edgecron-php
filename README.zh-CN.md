# EdgeCron PHP SDK

EdgeCron PHP SDK 是 EdgeCron Webhook 调度与回调投递平台的官方 PHP 客户端。

调度延迟 HTTP 请求，可靠投递 Webhook，自动重试失败调用 — 完整执行历史，确保不遗漏。

English README: [README.md](README.md)

## 安装

```bash
composer require edgecron/edgecron-php
```

要求：

- PHP `>= 8.2`

## 快速开始

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

## 模块说明

| 客户端方法                      | 说明                         |
|--------------------------------|-----------------------------|
| `$client->schedules->*`        | Cron 调度器 CRUD、暂停、恢复   |
| `$client->tasks->*`            | 任务执行实例、取消             |
| `$client->events->*`           | 事件发布与管理                |
| `$client->endpoints->*`        | Webhook 端点配置              |
| `$client->deliveries->*`       | 投递记录与手动重试             |
| `$client->retries->*`          | 重试策略与任务                |
| `$client->subscription->*`     | 配额、用量与资源限制           |

## 配置项

- `base_url`：自定义 API 地址
- `timeout`：超时时间，单位秒
- `http_client`：自定义 Guzzle Client

## 错误处理

服务端业务错误会抛出 `APIError`。

```php
<?php

use EdgeCron\APIError;

try {
    $client->schedules->get(123);
} catch (APIError $error) {
    var_dump($error->codeValue, $error->getMessage(), $error->requestId);
}
```

## 安全说明

这是服务端 SDK，不要在浏览器、前端页面或移动端暴露 `secret`。

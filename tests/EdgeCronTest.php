<?php

declare(strict_types=1);

namespace EdgeCron\Tests;

use EdgeCron\APIError;
use EdgeCron\EdgeCron;
use EdgeCron\Signer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class EdgeCronTest extends TestCase
{
    private const KEY_ID = 'ak_3f9a2b1c7d4e8f0a';
    private const SECRET = 'sk_test';

    // --- Helper response builders ---

    /** @param array<string,mixed>|null $data */
    private static function ok(?array $data): string
    {
        return json_encode(['code' => 0, 'message' => 'success', 'request_id' => 'test-rid', 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private static function s(): array
    {
        return ['id' => 1, 'app_id' => 'app_1', 'name' => 'test', 'cron_expr' => '*/5 * * * *', 'timezone' => 'UTC', 'payload' => '{}', 'status' => 'active', 'next_run_at' => 1712000000, 'endpoint_ids' => [1], 'endpoint_names' => [1 => 'ep1'], 'created_at' => 1711900000, 'updated_at' => 1711900000];
    }

    private static function t(): array
    {
        return ['id' => 1, 'app_id' => 'app_1', 'schedule_id' => 0, 'event_id' => 0, 'endpoint_id' => 1, 'task_type' => 'manual', 'payload' => '{}', 'status' => 'pending', 'run_at' => 1712000000, 'created_at' => 1711900000, 'updated_at' => 1711900000];
    }

    private static function e(): array
    {
        return ['id' => 1, 'app_id' => 'app_1', 'event_name' => 'order.created', 'event_key' => 'ord_001', 'payload' => '{}', 'status' => 'active', 'created_at' => 1711900000];
    }

    private static function ep(): array
    {
        return ['id' => 1, 'app_id' => 'app_1', 'name' => 'webhook', 'url' => 'https://example.com/hook', 'method' => 'POST', 'headers' => '{}', 'secret' => '', 'timeout_ms' => 5000, 'retry_policy_id' => 0, 'filter_events' => '', 'status' => 'active', 'created_at' => 1711900000, 'updated_at' => 1711900000];
    }

    private static function d(): array
    {
        return ['id' => 1, 'app_id' => 'app_1', 'task_id' => 1, 'endpoint_id' => 1, 'attempt' => 1, 'status' => 'success', 'http_status' => 200, 'latency_ms' => 150, 'request_body_hash' => 'abc', 'error_message' => '', 'next_retry_at' => 0, 'created_at' => 1711900000, 'updated_at' => 1711900000];
    }

    private static function rp(): array
    {
        return ['id' => 1, 'app_id' => 'app_1', 'name' => 'default', 'max_attempts' => 3, 'backoff_type' => 'exponential', 'initial_delay_sec' => 10, 'max_delay_sec' => 3600, 'status' => 'active', 'created_at' => 1711900000, 'updated_at' => 1711900000];
    }

    private static function rj(): array
    {
        return ['id' => 1, 'app_id' => 'app_1', 'delivery_id' => 1, 'attempt' => 1, 'status' => 'pending', 'run_at' => 1712000000, 'locked_until' => 0, 'last_error' => '', 'created_at' => 1711900000, 'updated_at' => 1711900000];
    }

    // --- Tests ---

    public function testSchedules(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(200, [], self::ok(self::s())),
            new Response(200, [], self::ok(self::s())),
            new Response(200, [], self::ok(self::s())),
            new Response(200, [], self::ok(['total' => 1, 'list' => [self::s()]])),
            new Response(200, [], self::ok(null)),
            new Response(200, [], self::ok(null)),
            new Response(200, [], self::ok(null)),
        ], $history);

        self::assertSame('test', $client->schedules->create('test', '*/5 * * * *')->name);
        self::assertSame('test', $client->schedules->update(1, name: 'updated')->name);
        self::assertSame('test', $client->schedules->get(1)->name);
        self::assertSame(1, $client->schedules->list()->total);
        $client->schedules->delete(1);
        $client->schedules->pause(1);
        $client->schedules->resume(1);
        self::assertCount(7, $history);
    }

    public function testTasks(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(200, [], self::ok(self::t())),
            new Response(200, [], self::ok(self::t())),
            new Response(200, [], self::ok(['total' => 1, 'list' => [self::t()]])),
            new Response(200, [], self::ok(null)),
        ], $history);

        self::assertSame('manual', $client->tasks->create(1)->task_type);
        self::assertSame('manual', $client->tasks->get(1)->task_type);
        self::assertSame(1, $client->tasks->list()->total);
        $client->tasks->cancel(1);
        self::assertCount(4, $history);
    }

    public function testEvents(): void
    {
        $history = [];
        $pubResult = self::e() + ['fanout_count' => 2];
        $client = $this->makeClient([
            new Response(200, [], self::ok($pubResult)),
            new Response(200, [], self::ok(self::e())),
            new Response(200, [], self::ok(['total' => 1, 'list' => [self::e()]])),
            new Response(200, [], self::ok(null)),
            new Response(200, [], self::ok(null)),
            new Response(200, [], self::ok(null)),
        ], $history);

        self::assertSame(2, $client->events->publish('order.created', 'ord_001')->fanout_count);
        self::assertSame('order.created', $client->events->get(1)->event_name);
        self::assertSame(1, $client->events->list()->total);
        $client->events->enable(1);
        $client->events->disable(1);
        $client->events->delete(1);
        self::assertCount(6, $history);
    }

    public function testEndpoints(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(200, [], self::ok(self::ep())),
            new Response(200, [], self::ok(self::ep())),
            new Response(200, [], self::ok(self::ep())),
            new Response(200, [], self::ok(['total' => 1, 'list' => [self::ep()]])),
            new Response(200, [], self::ok(null)),
            new Response(200, [], self::ok(null)),
            new Response(200, [], self::ok(null)),
        ], $history);

        self::assertSame('webhook', $client->endpoints->create('webhook', 'https://example.com/hook')->name);
        self::assertSame('webhook', $client->endpoints->get(1)->name);
        self::assertSame('webhook', $client->endpoints->update(1, name: 'updated')->name);
        self::assertSame(1, $client->endpoints->list()->total);
        $client->endpoints->delete(1);
        $client->endpoints->enable(1);
        $client->endpoints->disable(1);
        self::assertCount(7, $history);
    }

    public function testDeliveries(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(200, [], self::ok(['total' => 1, 'list' => [self::d()]])),
            new Response(200, [], self::ok(['delivery_id' => 1, 'retry_job_id' => 1, 'status' => 'queued'])),
        ], $history);

        $list = $client->deliveries->list();
        self::assertSame(1, $list->total);
        self::assertSame('success', $list->list[0]['status']);
        self::assertSame('queued', $client->deliveries->retry(1)->status);
        self::assertCount(2, $history);
    }

    public function testRetries(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(200, [], self::ok(self::rp())),
            new Response(200, [], self::ok(self::rp())),
            new Response(200, [], self::ok(self::rp())),
            new Response(200, [], self::ok(null)),
            new Response(200, [], self::ok(['total' => 1, 'list' => [self::rp()]])),
            new Response(200, [], self::ok(['total' => 1, 'list' => [self::rj()]])),
            new Response(200, [], self::ok(null)),
        ], $history);

        self::assertSame('default', $client->retries->createPolicy('default')->name);
        self::assertSame('default', $client->retries->getPolicy(1)->name);
        self::assertSame('default', $client->retries->updatePolicy(1, maxAttempts: 5)->name);
        $client->retries->deletePolicy(1);
        self::assertSame(1, $client->retries->listPolicies()->total);
        self::assertSame(1, $client->retries->listJobs()->total);
        $client->retries->cancelJob(1);
        self::assertCount(7, $history);
    }

    public function testSubscription(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(200, [], self::ok(['plan_code' => 'pro', 'billing_cycle' => 'monthly', 'quota' => 10000, 'used' => 500, 'remaining' => 9500, 'exceeded' => false, 'current_period_start' => 1711900000, 'current_period_end' => 1714499999, 'usage_percent' => 5.0])),
            new Response(200, [], self::ok(['period' => '2026-06', 'total_events' => 500, 'items' => [['event_type' => 'api_call', 'period' => '2026-06', 'count' => 500]]])),
            new Response(200, [], self::ok(['max_cron_jobs' => 100, 'current_cron_jobs' => 5, 'max_endpoints' => 50, 'current_endpoints' => 3, 'log_retention_days' => 90])),
        ], $history);

        $q = $client->subscription->quota();
        self::assertSame('pro', $q->plan_code);
        self::assertFalse($q->exceeded);
        $u = $client->subscription->usage('2026-06');
        self::assertSame('2026-06', $u->period);
        self::assertCount(1, $u->items);
        self::assertSame(100, $client->subscription->resourceLimits()->max_cron_jobs);
        self::assertCount(3, $history);
    }

    public function testHeadersSent(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(200, [], self::ok(self::s())),
            new Response(200, [], self::ok(self::t())),
            new Response(200, [], self::ok(self::e() + ['fanout_count' => 2])),
            new Response(200, [], self::ok(self::ep())),
            new Response(200, [], self::ok(['total' => 1, 'list' => [self::d()]])),
            new Response(200, [], self::ok(self::rp())),
            new Response(200, [], self::ok(['plan_code' => 'pro', 'billing_cycle' => 'monthly', 'quota' => 10000, 'used' => 500, 'remaining' => 9500, 'exceeded' => false, 'current_period_start' => 1711900000, 'current_period_end' => 1714499999, 'usage_percent' => 5.0])),
        ], $history);

        $client->schedules->create('test', '*/5 * * * *');
        $client->tasks->create(1);
        $client->events->publish('order.created', 'ord_001');
        $client->endpoints->create('webhook', 'https://example.com/hook');
        $client->deliveries->list();
        $client->retries->createPolicy('default');
        $client->subscription->quota();

        foreach ($history as $transaction) {
            $request = $transaction['request'];
            self::assertSame(self::KEY_ID, $request->getHeaderLine('X-Key-ID'));
            self::assertNotSame('', $request->getHeaderLine('X-Timestamp'));
            self::assertNotSame('', $request->getHeaderLine('X-Signature'));
            self::assertStringStartsWith('edgecron-php/', $request->getHeaderLine('User-Agent'));
        }
    }

    public function testQuerySigningAndErrors(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(200, [], self::ok(['total' => 0, 'list' => []])),
            new Response(200, [], json_encode(['code' => 1001, 'message' => 'invalid key_id', 'request_id' => 'err-rid', 'data' => null], JSON_THROW_ON_ERROR)),
        ], $history);

        $client->schedules->list();
        $request = $history[0]['request'];
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame(Signer::sign(self::SECRET, $request->getHeaderLine('X-Timestamp'), $query, ''), $request->getHeaderLine('X-Signature'));

        try {
            $client->schedules->list();
            self::fail('Expected APIError');
        } catch (APIError $error) {
            self::assertSame(1001, $error->codeValue);
        }
    }

    public function testHttpAndValidationErrors(): void
    {
        $history = [];
        $client = $this->makeClient([
            new Response(503, ['Content-Type' => 'text/html'], '<html>503</html>'),
            new Response(500, ['Content-Type' => 'application/json'], json_encode(['code' => 5000, 'message' => 'internal server error', 'request_id' => 'err-500', 'data' => null], JSON_THROW_ON_ERROR)),
        ], $history);

        try {
            $client->schedules->list();
            self::fail('Expected RuntimeException');
        } catch (\RuntimeException $error) {
            self::assertStringContainsString('http status 503', $error->getMessage());
        } finally {
            self::assertCount(1, $history);
        }

        try {
            $client->schedules->list();
            self::fail('Expected APIError');
        } catch (APIError $error) {
            self::assertSame(5000, $error->codeValue);
        }

        try {
            new EdgeCron('sk_abc', self::SECRET);
            self::fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException) {
            self::assertTrue(true);
        }
    }

    public function testEmptySecretValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new EdgeCron(self::KEY_ID, '');
    }

    /** @param list<Response> $responses @param array<int, array{request: RequestInterface}> $history */
    private function makeClient(array $responses, array &$history): EdgeCron
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        return new EdgeCron(self::KEY_ID, self::SECRET, ['http_client' => new Client(['handler' => $stack])]);
    }
}

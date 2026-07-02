<?php

declare(strict_types=1);

namespace EdgeCron\Tests;

use EdgeCron\EdgeCron;
use EdgeCron\APIError;
use PHPUnit\Framework\TestCase;

final class IntegrationTest extends TestCase
{
    private const BASE_URL = 'http://127.0.0.1:8888';

    private string $keyId;
    private string $secret;

    protected function setUp(): void
    {
        $this->keyId = (string) getenv('EDGECRON_KEY_ID');
        $this->secret = (string) getenv('EDGECRON_SECRET');
        if ($this->keyId === '') {
            $this->markTestSkipped('EDGECRON_KEY_ID not set');
        }
    }

    private function client(): EdgeCron
    {
        return new EdgeCron($this->keyId, $this->secret, [
            'base_url' => getenv('EDGECRON_BASE_URL') ?: self::BASE_URL,
            'timeout'  => 10,
        ]);
    }

    private function assertApiError(callable $fn): APIError
    {
        try {
            $fn();
            self::fail('Expected APIError');
        } catch (APIError $e) {
            return $e;
        }
    }

    // --- Schedules ---

    public function testSchedulesFullLifecycle(): void
    {
        $c = $this->client();

        $s = $c->schedules->create('int-test-sched', '*/5 * * * *', 'Asia/Shanghai', '{"a":1}');
        self::assertGreaterThan(0, $s->id);
        self::assertSame('int-test-sched', $s->name);
        self::assertSame('*/5 * * * *', $s->cron_expr);

        $fetched = $c->schedules->get($s->id);
        self::assertSame($s->id, $fetched->id);

        $list = $c->schedules->list();
        self::assertGreaterThanOrEqual(1, $list->total);

        $updated = $c->schedules->update($s->id, name: 'int-test-sched-updated');
        self::assertSame('int-test-sched-updated', $updated->name);

        $c->schedules->pause($s->id);
        $paused = $c->schedules->get($s->id);
        self::assertSame('paused', $paused->status);

        $c->schedules->resume($s->id);
        $resumed = $c->schedules->get($s->id);
        self::assertSame('active', $resumed->status);

        $c->schedules->delete($s->id);

        $e = $this->assertApiError(fn() => $c->schedules->get($s->id));
        self::assertSame(5000, $e->codeValue);
    }

    // --- Endpoints ---

    public function testEndpointsFullLifecycle(): void
    {
        $c = $this->client();

        $ep = $c->endpoints->create(
            name: 'int-test-ep',
            url: 'https://httpbin.org/post',
            method: 'POST',
            headers: '{"Authorization": "Bearer test"}',
            timeoutMs: 5000,
        );
        self::assertGreaterThan(0, $ep->id);
        self::assertSame('int-test-ep', $ep->name);

        $fetched = $c->endpoints->get($ep->id);
        self::assertSame($ep->id, $fetched->id);

        $list = $c->endpoints->list();
        self::assertGreaterThanOrEqual(1, $list->total);

        $updated = $c->endpoints->update($ep->id, name: 'int-test-ep-updated');
        self::assertSame('int-test-ep-updated', $updated->name);

        $c->endpoints->disable($ep->id);
        $disabled = $c->endpoints->get($ep->id);
        self::assertSame('disabled', $disabled->status);

        $c->endpoints->enable($ep->id);
        $enabled = $c->endpoints->get($ep->id);
        self::assertSame('active', $enabled->status);

        $c->endpoints->delete($ep->id);

        $e = $this->assertApiError(fn() => $c->endpoints->get($ep->id));
        self::assertSame(5000, $e->codeValue);
    }

    // --- Tasks ---

    public function testTasksFullLifecycle(): void
    {
        $c = $this->client();

        $ep = $c->endpoints->create('int-test-task-ep', 'https://httpbin.org/post');

        $task = $c->tasks->create($ep->id, '{"msg": "hello"}');
        self::assertGreaterThan(0, $task->id);
        self::assertSame('pending', $task->status);

        $fetched = $c->tasks->get($task->id);
        self::assertSame($task->id, $fetched->id);

        $list = $c->tasks->list();
        self::assertGreaterThanOrEqual(1, $list->total);

        $c->tasks->cancel($task->id);
        $cancelled = $c->tasks->get($task->id);
        // cancel is async; accept running/pending as intermediate states
        self::assertContains($cancelled->status, ['cancelled', 'running', 'pending']);

        $c->endpoints->delete($ep->id);
    }

    // --- Events ---

    public function testEventsFullLifecycle(): void
    {
        $c = $this->client();

        $result = $c->events->publish('test.event', 'key_' . time(), '{"data": 1}');
        self::assertGreaterThan(0, $result->id);
        self::assertSame('test.event', $result->event_name);

        $fetched = $c->events->get($result->id);
        self::assertSame($result->id, $fetched->id);

        $list = $c->events->list();
        self::assertGreaterThanOrEqual(1, $list->total);

        $c->events->disable($result->id);
        $disabled = $c->events->get($result->id);
        self::assertSame('disabled', $disabled->status);

        $c->events->enable($result->id);
        $enabled = $c->events->get($result->id);
        self::assertSame('active', $enabled->status);

        $c->events->delete($result->id);

        $e = $this->assertApiError(fn() => $c->events->get($result->id));
        self::assertSame(5000, $e->codeValue);
    }

    // --- Deliveries ---

    public function testDeliveries(): void
    {
        $c = $this->client();

        $list = $c->deliveries->list();
        self::assertIsInt($list->total);
    }

    // --- Retries ---

    public function testRetriesFullLifecycle(): void
    {
        $c = $this->client();

        $rp = $c->retries->createPolicy('int-test-rp', maxAttempts: 5, backoffType: 'linear', initialDelaySec: 5);
        self::assertGreaterThan(0, $rp->id);
        self::assertSame('int-test-rp', $rp->name);
        self::assertSame(5, $rp->max_attempts);

        $fetched = $c->retries->getPolicy($rp->id);
        self::assertSame($rp->id, $fetched->id);

        $updated = $c->retries->updatePolicy($rp->id, name: 'int-test-rp-renamed');
        self::assertSame('int-test-rp-renamed', $updated->name);

        $allPolicies = $c->retries->listPolicies();
        self::assertGreaterThanOrEqual(1, $allPolicies->total);

        $jobs = $c->retries->listJobs();
        self::assertIsInt($jobs->total);

        $c->retries->deletePolicy($rp->id);

        $e = $this->assertApiError(fn() => $c->retries->getPolicy($rp->id));
        self::assertSame(5000, $e->codeValue);
    }

    // --- Subscription ---

    public function testSubscription(): void
    {
        $c = $this->client();

        $q = $c->subscription->quota();
        self::assertNotEmpty($q->plan_code);
        self::assertIsInt($q->quota);
        self::assertIsInt($q->used);
        self::assertIsInt($q->remaining);
        self::assertIsFloat($q->usage_percent);

        $u = $c->subscription->usage();
        self::assertIsInt($u->total_events);

        $r = $c->subscription->resourceLimits();
        self::assertGreaterThan(0, $r->max_cron_jobs);
        // -1 means unlimited
        self::assertTrue($r->max_endpoints === -1 || $r->max_endpoints > 0);
        self::assertGreaterThan(0, $r->log_retention_days);
    }

    // --- Business Error ---

    public function testBusinessErrorOnInvalidId(): void
    {
        $c = $this->client();
        $e = $this->assertApiError(fn() => $c->schedules->get(999999999));
        // non-existent record returns 5000
        self::assertSame(5000, $e->codeValue);
        self::assertNotEmpty($e->requestId);
    }

    // --- Pagination ---

    public function testListPagination(): void
    {
        $c = $this->client();

        $schedules = $c->schedules->list(page: 1, pageSize: 5);
        self::assertIsInt($schedules->total);
        self::assertIsArray($schedules->list);

        $tasks = $c->tasks->list(page: 1, pageSize: 5);
        self::assertIsInt($tasks->total);

        $events = $c->events->list(page: 1, pageSize: 5);
        self::assertIsInt($events->total);

        $endpoints = $c->endpoints->list(page: 1, pageSize: 5);
        self::assertIsInt($endpoints->total);

        $deliveries = $c->deliveries->list(page: 1, pageSize: 5);
        self::assertIsInt($deliveries->total);
    }
}

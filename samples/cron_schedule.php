<?php

require_once __DIR__ . '/../vendor/autoload.php';

use EdgeCron\EdgeCron;
use EdgeCron\APIError;

$keyId = getenv('EDGECRON_KEY_ID');
$secret = getenv('EDGECRON_SECRET');
$client = new EdgeCron($keyId, $secret, ['base_url' => 'http://localhost:8888']);

try {
    // 1. Create schedule
    $schedule = $client->schedules->create(
        name: 'my-cron',
        cronExpr: '*/10 * * * *',
        timezone: 'Asia/Shanghai',
        payload: '{"task": "sync"}',
    );
    echo "Created schedule: {$schedule->id}\n";

    // 2. Get schedule by ID
    $fetched = $client->schedules->get($schedule->id);
    echo "Fetched schedule: {$fetched->name} ({$fetched->cron_expr})\n";

    // 3. List schedules
    $list = $client->schedules->list();
    echo "Total schedules: {$list->total}\n";

    // 4. Update schedule
    $updated = $client->schedules->update($schedule->id, name: 'my-cron-renamed');
    echo "Updated schedule name: {$updated->name}\n";

    // 5. Pause schedule
    $client->schedules->pause($schedule->id);
    echo "Paused schedule {$schedule->id}\n";

    // 6. Resume schedule
    $client->schedules->resume($schedule->id);
    echo "Resumed schedule {$schedule->id}\n";

    // 7. Delete schedule
    $client->schedules->delete($schedule->id);
    echo "Deleted schedule {$schedule->id}\n";
} catch (APIError $e) {
    echo "Error: {$e->codeValue} {$e->getMessage()}\n";
}

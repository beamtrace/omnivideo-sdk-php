<?php
declare(strict_types=1);

// Minimal smoke-test runner for environments without PHPUnit installed.
// Real test suite lives in tests/ClientTest.php and runs under `composer test` (PHPUnit).

require __DIR__ . '/../src/Error.php';
require __DIR__ . '/../src/Task.php';
require __DIR__ . '/../src/Client.php';

use OmniVideo\Client;
use OmniVideo\Error;

function expect(bool $cond, string $name): void
{
    if ($cond) {
        echo "ok  $name\n";
    } else {
        echo "FAIL $name\n";
        exit(1);
    }
}

function transport(array $responses): callable
{
    $i = 0;
    return function ($method, $url, $headers, $body, $timeout) use (&$i, $responses) {
        if ($i >= count($responses)) {
            throw new RuntimeException("unexpected extra request: $method $url");
        }
        $r = $responses[$i++];
        return ['status' => $r['status'] ?? 200, 'body' => json_encode($r['body'])];
    };
}

$c = new Client([
    'api_key'   => 'sk-test',
    'transport' => transport([['body' => ['code' => 200, 'task_id' => 'abc', 'task_status' => 1]]]),
]);
$t = $c->createTask(['model_id' => 'gpt-image-2', 'prompt' => 'x']);
expect($t->taskId === 'abc' && $t->taskStatus === Client::STATUS_QUEUED, 'createTask returns task_id');

putenv('OMNIVIDEO_API_KEY');
$threw = false;
try { new Client(); } catch (Error $e) { $threw = true; }
expect($threw, 'missing api key throws');

$c = new Client([
    'api_key'   => 'sk-test',
    'transport' => transport([
        ['body' => ['code' => 200, 'task_id' => 't1', 'task_status' => 1]],
        ['body' => ['code' => 200, 'task_id' => 't1', 'task_status' => 2]],
        ['body' => ['code' => 200, 'task_id' => 't1', 'task_status' => 3, 'image_url' => 'https://x/y.png']],
    ]),
]);
$t = $c->run(['model_id' => 'gpt-image-2', 'prompt' => 'x'], ['poll_interval' => 0, 'sleeper' => static fn($s) => null]);
expect($t->taskStatus === Client::STATUS_SUCCESS && $t->outputUrl() === 'https://x/y.png', 'run polls to success');

$c = new Client([
    'api_key'   => 'sk-test',
    'transport' => transport([['body' => ['code' => 0, 'msg' => 'insufficient credits']]]),
]);
$threw = false;
try { $c->createTask(['model_id' => 'gpt-image-2', 'prompt' => 'x']); }
catch (Error $e) { $threw = strpos($e->getMessage(), 'insufficient credits') !== false; }
expect($threw, 'business error raised');

echo "all 4 tests passed\n";

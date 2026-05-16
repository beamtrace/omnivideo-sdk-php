<?php

declare(strict_types=1);

namespace OmniVideo\Tests;

use OmniVideo\Client;
use OmniVideo\Error;
use OmniVideo\Task;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private function transport(array $responses): callable
    {
        $i = 0;
        return function ($method, $url, $headers, $body, $timeout) use (&$i, $responses) {
            if ($i >= count($responses)) {
                throw new \RuntimeException("unexpected extra request: $method $url");
            }
            $r = $responses[$i++];
            return ['status' => $r['status'] ?? 200, 'body' => json_encode($r['body'])];
        };
    }

    public function testCreateTaskReturnsTaskId(): void
    {
        $client = new Client([
            'api_key' => 'sk-test',
            'transport' => $this->transport([
                ['body' => ['code' => 200, 'task_id' => 'abc', 'task_status' => 1]],
            ]),
        ]);
        $task = $client->createTask(['model_id' => 'gpt-image-2', 'prompt' => 'x']);
        $this->assertSame('abc', $task->taskId);
        $this->assertSame(Client::STATUS_QUEUED, $task->taskStatus);
    }

    public function testMissingApiKey(): void
    {
        putenv('OMNIVIDEO_API_KEY');
        $this->expectException(Error::class);
        new Client();
    }

    public function testRunPollsToSuccess(): void
    {
        $client = new Client([
            'api_key' => 'sk-test',
            'transport' => $this->transport([
                ['body' => ['code' => 200, 'task_id' => 't1', 'task_status' => 1]],
                ['body' => ['code' => 200, 'task_id' => 't1', 'task_status' => 2]],
                ['body' => ['code' => 200, 'task_id' => 't1', 'task_status' => 3, 'image_url' => 'https://x/y.png']],
            ]),
        ]);
        $task = $client->run(
            ['model_id' => 'gpt-image-2', 'prompt' => 'x'],
            ['poll_interval' => 0, 'sleeper' => static fn($s) => null]
        );
        $this->assertSame(Client::STATUS_SUCCESS, $task->taskStatus);
        $this->assertSame('https://x/y.png', $task->outputUrl());
    }

    public function testBusinessErrorRaised(): void
    {
        $client = new Client([
            'api_key' => 'sk-test',
            'transport' => $this->transport([
                ['body' => ['code' => 0, 'msg' => 'insufficient credits']],
            ]),
        ]);
        $this->expectException(Error::class);
        $this->expectExceptionMessage('insufficient credits');
        $client->createTask(['model_id' => 'gpt-image-2', 'prompt' => 'x']);
    }
}

<?php

declare(strict_types=1);

namespace OmniVideo;

/**
 * Omni Video PHP SDK — generate video and image content with the Gemini Omni Video series of models.
 *
 * Sign in at https://omnivideo.net/ and create an API key on the account page.
 */
final class Client
{
    public const DEFAULT_BASE_URL = 'https://omnivideo.net/api/v1';

    public const STATUS_QUEUED  = 1;
    public const STATUS_RUNNING = 2;
    public const STATUS_SUCCESS = 3;
    public const STATUS_FAILED  = 4;

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    /** @var callable|null */
    private $transport;

    /**
     * @param array{api_key?:string,base_url?:string,timeout?:int,transport?:callable} $options
     */
    public function __construct(array $options = [])
    {
        $key = $options['api_key'] ?? getenv('OMNIVIDEO_API_KEY');
        if (!is_string($key) || $key === '') {
            throw new Error('Missing API key. Pass api_key or set OMNIVIDEO_API_KEY. Get one at https://omnivideo.net/');
        }
        $this->apiKey = $key;
        $this->baseUrl = rtrim($options['base_url'] ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = (int) ($options['timeout'] ?? 60);
        $this->transport = $options['transport'] ?? null;
    }

    /**
     * @param array{model_id:string,prompt:string,image_urls?:array<int,string>,aspect_ratio?:string} $input
     */
    public function createTask(array $input): Task
    {
        $payload = array_filter(
            $input,
            static fn($v) => $v !== null && $v !== []
        );
        $data = $this->request('POST', '/tasks/create', $payload);
        return Task::fromPayload($data);
    }

    public function getTask(string $taskId): Task
    {
        $data = $this->request('GET', '/tasks/' . rawurlencode($taskId));
        return Task::fromPayload($data);
    }

    /**
     * Create + poll until terminal state.
     *
     * @param array{model_id:string,prompt:string,image_urls?:array<int,string>,aspect_ratio?:string} $input
     * @param array{poll_interval?:float,max_wait?:float,sleeper?:callable} $opts
     */
    public function run(array $input, array $opts = []): Task
    {
        $pollInterval = (float) ($opts['poll_interval'] ?? 3.0);
        $maxWait = (float) ($opts['max_wait'] ?? 600.0);
        $sleeper = $opts['sleeper'] ?? static fn(float $s) => usleep((int) ($s * 1_000_000));

        $task = $this->createTask($input);
        $deadline = microtime(true) + $maxWait;
        while (!$task->isDone()) {
            if (microtime(true) > $deadline) {
                throw new Error("Task {$task->taskId} did not finish within {$maxWait}s", $task->taskStatus);
            }
            $sleeper($pollInterval);
            $task = $this->getTask($task->taskId);
        }
        if ($task->taskStatus === self::STATUS_FAILED) {
            throw new Error("Task {$task->taskId} failed", self::STATUS_FAILED);
        }
        return $task;
    }

    /**
     * @param array<string,mixed>|null $body
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        $bodyJson = $body !== null ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

        $transport = $this->transport ?? [$this, 'defaultTransport'];
        /** @var array{status:int,body:string} $resp */
        $resp = $transport($method, $url, $headers, $bodyJson, $this->timeout);

        if ($resp['status'] === 401) {
            throw new Error('Unauthorized — check your OMNIVIDEO_API_KEY (https://omnivideo.net/).', null, 401);
        }
        $payload = $resp['body'] === '' ? [] : json_decode($resp['body'], true);
        if (!is_array($payload)) {
            throw new Error('Invalid JSON from ' . $url . ': ' . substr($resp['body'], 0, 200), null, $resp['status']);
        }
        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new Error($payload['msg'] ?? "HTTP {$resp['status']}", $payload['code'] ?? null, $resp['status']);
        }
        if (isset($payload['code']) && $payload['code'] !== 200) {
            throw new Error($payload['msg'] ?? 'Business error', (int) $payload['code']);
        }
        return $payload;
    }

    /**
     * @param array<int,string> $headers
     * @return array{status:int,body:string}
     */
    private function defaultTransport(string $method, string $url, array $headers, ?string $body, int $timeout): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Error('HTTP request failed: ' . $err);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => (string) $raw];
    }
}

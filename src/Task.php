<?php

declare(strict_types=1);

namespace OmniVideo;

final class Task
{
    public string $taskId;
    public int $taskStatus;
    public ?string $imageUrl = null;
    public ?string $videoUrl = null;
    public ?int $credits = null;
    /** @var array<string,mixed> */
    public array $raw;

    /** @param array<string,mixed> $raw */
    public function __construct(string $taskId, int $taskStatus, array $raw)
    {
        $this->taskId = $taskId;
        $this->taskStatus = $taskStatus;
        $this->raw = $raw;
    }

    public function isDone(): bool
    {
        return $this->taskStatus === Client::STATUS_SUCCESS
            || $this->taskStatus === Client::STATUS_FAILED;
    }

    public function outputUrl(): ?string
    {
        return $this->videoUrl ?? $this->imageUrl;
    }

    /** @param array<string,mixed> $payload */
    public static function fromPayload(array $payload): self
    {
        $task = new self(
            isset($payload['task_id']) ? (string) $payload['task_id'] : '',
            isset($payload['task_status']) ? (int) $payload['task_status'] : Client::STATUS_QUEUED,
            $payload
        );
        $task->imageUrl = isset($payload['image_url']) ? (string) $payload['image_url'] : null;
        $task->videoUrl = isset($payload['video_url']) ? (string) $payload['video_url'] : null;
        $task->credits  = isset($payload['credits'])   ? (int) $payload['credits']      : null;
        return $task;
    }
}

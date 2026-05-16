<?php

declare(strict_types=1);

namespace OmniVideo;

class Error extends \RuntimeException
{
    /** Business error code returned by the Omni Video API (200=ok, 0=biz fail). */
    public ?int $bizCode;
    /** HTTP status code if applicable. */
    public ?int $status;

    public function __construct(string $message, ?int $bizCode = null, ?int $status = null)
    {
        parent::__construct($message);
        $this->bizCode = $bizCode;
        $this->status = $status;
    }
}

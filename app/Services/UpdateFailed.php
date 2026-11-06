<?php

namespace App\Services;

use RuntimeException;
use Throwable;

/** An update step failed. The code maps to a translated message in the updates language files (errors section). */
class UpdateFailed extends RuntimeException
{
    public function __construct(public readonly string $reason, ?Throwable $previous = null)
    {
        parent::__construct($reason, 0, $previous);
    }

    public function userMessage(): string
    {
        return __('updates.errors.'.$this->reason);
    }
}

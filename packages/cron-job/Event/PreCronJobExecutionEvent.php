<?php

declare(strict_types=1);

namespace Draw\Component\CronJob\Event;

use Draw\Component\CronJob\Entity\CronJobExecution;
use Symfony\Contracts\EventDispatcher\Event;

class PreCronJobExecutionEvent extends Event
{
    public function __construct(
        private CronJobExecution $execution,
        private bool $executionCancelled = false,
    ) {
    }

    public function getExecution(): CronJobExecution
    {
        return $this->execution;
    }

    public function isExecutionCancelled(): bool
    {
        return $this->executionCancelled;
    }
}

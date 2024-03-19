<?php

namespace Draw\Component\Messenger\Counter;

use Fidry\CpuCoreCounter\CpuCoreCounter;
use Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;

class CpuCounter
{
    public function count(): int
    {
        if (!class_exists(CpuCoreCounter::class) || !class_exists(NumberOfCpuCoreNotFound::class)) {
            throw new \RuntimeException('"fidry/cpu-core-counter" must be installed');
        }

        $counter = new CpuCoreCounter();

        try {
            return $counter->getCount();
        } catch (NumberOfCpuCoreNotFound) {
            return 1;
        }
    }
}

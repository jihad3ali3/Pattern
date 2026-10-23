<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Services/SystemClockService.php
 * اسم الصفحة: SystemClockService.php
 * الوظيفة: محوّل (Adapter) ينفّذ واجهة ClockInterface بإرجاع الوقت الفعلي للنظام ككائن DateTimeImmutable.
 */

namespace Admissions\Infrastructure\Services;

use Admissions\Application\Interfaces\ClockInterface;
use DateTimeImmutable;

final readonly class SystemClockService implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}

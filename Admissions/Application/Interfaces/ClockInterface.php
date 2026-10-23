<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Interfaces/ClockInterface.php
 * اسم الصفحة: ClockInterface.php
 * الوظيفة: منفذ (Port / Interface) للحصول على الوقت الحالي، يسمح بتثبيت الوقت أثناء الاختبارات بدلاً من الاعتماد المباشر على ساعة النظام.
 */

namespace Admissions\Application\Interfaces;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}

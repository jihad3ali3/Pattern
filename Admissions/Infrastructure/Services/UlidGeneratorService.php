<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Services/UlidGeneratorService.php
 * اسم الصفحة: UlidGeneratorService.php
 * الوظيفة: محوّل (Adapter) ينفّذ واجهة IdGeneratorInterface لتوليد معرّفات ULID مرتبة زمنياً باستخدام مساعد Laravel Str.
 */

namespace Admissions\Infrastructure\Services;

use Admissions\Application\Interfaces\IdGeneratorInterface;
use Illuminate\Support\Str;

final readonly class UlidGeneratorService implements IdGeneratorInterface
{
    public function nextUlid(): string
    {
        return (string) Str::ulid();
    }
}

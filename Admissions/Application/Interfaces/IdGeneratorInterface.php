<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Interfaces/IdGeneratorInterface.php
 * اسم الصفحة: IdGeneratorInterface.php
 * الوظيفة: منفذ (Port / Interface) لتوليد معرّفات فريدة بصيغة ULID للكيانات الجديدة دون ربط طبقة التطبيق بمكتبة Laravel Str مباشرة.
 */

namespace Admissions\Application\Interfaces;

interface IdGeneratorInterface
{
    public function nextUlid(): string;
}

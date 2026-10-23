<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/InvalidGpaException.php
 * اسم الصفحة: InvalidGpaException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة إنشاء كائن معدل تراكمي (Gpa) بقيمة غير رقمية أو خارج النطاق المسموح (0.0 إلى 5.0).
 */

namespace Admissions\Domain\Exceptions;

final class InvalidGpaException extends AdmissionsDomainException
{
    public static function outsideRange(float $value): self
    {
        return new self("GPA must be between 0.0 and 5.0, got [{$value}].");
    }

    public static function notNumeric(mixed $value): self
    {
        return new self('GPA must be numeric, got [' . get_debug_type($value) . '].');
    }

    public function errorCode(): string
    {
        return 'invalid_gpa';
    }
}

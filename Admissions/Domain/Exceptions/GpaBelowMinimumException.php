<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/GpaBelowMinimumException.php
 * اسم الصفحة: GpaBelowMinimumException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة تقديم طلب قبول بمعدل تراكمي أقل من الحد الأدنى المشترط للبرنامج الدراسي.
 */

namespace Admissions\Domain\Exceptions;

final class GpaBelowMinimumException extends AdmissionsDomainException
{
    public static function forGpa(float $actual, float $minimum): self
    {
        return new self("Student GPA [{$actual}] is below the program minimum [{$minimum}].");
    }

    public function errorCode(): string
    {
        return 'gpa_below_minimum';
    }
}

<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/NoSeatsAvailableException.php
 * اسم الصفحة: NoSeatsAvailableException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة حجز مقعد لطالب في برنامج دراسي استنفد كامل سعته الاستيعابية.
 */

namespace Admissions\Domain\Exceptions;

final class NoSeatsAvailableException extends AdmissionsDomainException
{
    public static function forProgram(string $programName): self
    {
        return new self("No seats available in program [{$programName}].");
    }

    public function errorCode(): string
    {
        return 'no_seats_available';
    }
}

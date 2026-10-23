<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/InvalidTransitionException.php
 * اسم الصفحة: InvalidTransitionException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة نقل طلب القبول من حالة إلى حالة أخرى غير مسموح بها في دورة حياة الطلب.
 */

namespace Admissions\Domain\Exceptions;

use Admissions\Domain\Enums\ApplicationStatus;

final class InvalidTransitionException extends AdmissionsDomainException
{
    public static function between(ApplicationStatus $from, ApplicationStatus $to): self
    {
        return new self("Cannot transition application from [{$from->value}] to [{$to->value}].");
    }

    public function errorCode(): string
    {
        return 'invalid_transition';
    }
}

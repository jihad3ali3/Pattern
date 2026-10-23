<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/RejectionReasonRequiredException.php
 * اسم الصفحة: RejectionReasonRequiredException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة رفض طلب قبول دون ذكر سبب واضح للرفض.
 */

namespace Admissions\Domain\Exceptions;

use Admissions\Domain\ValueObjects\ApplicationId;

final class RejectionReasonRequiredException extends AdmissionsDomainException
{
    public static function forApplication(ApplicationId $applicationId): self
    {
        return new self("A non-empty rejection reason is required for application [{$applicationId->value}].");
    }

    public function errorCode(): string
    {
        return 'rejection_reason_required';
    }
}

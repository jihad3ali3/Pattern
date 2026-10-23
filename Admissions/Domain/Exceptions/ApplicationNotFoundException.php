<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/ApplicationNotFoundException.php
 * اسم الصفحة: ApplicationNotFoundException.php
 * الوظيفة: استثناء يُرمى عند البحث عن طلب قبول بمعرّف غير موجود في النظام.
 */

namespace Admissions\Domain\Exceptions;

final class ApplicationNotFoundException extends AdmissionsDomainException
{
    public static function withId(string $applicationId): self
    {
        return new self("Admission application [{$applicationId}] was not found.");
    }

    public function errorCode(): string
    {
        return 'application_not_found';
    }
}

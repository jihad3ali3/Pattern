<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/AlreadyAppliedException.php
 * اسم الصفحة: AlreadyAppliedException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة الطالب التقديم مرتين على نفس البرنامج الدراسي.
 */

namespace Admissions\Domain\Exceptions;

final class AlreadyAppliedException extends AdmissionsDomainException
{
    public static function forStudentAndProgram(string $studentId, string $programId): self
    {
        return new self("Student [{$studentId}] has already applied to program [{$programId}].");
    }

    public function errorCode(): string
    {
        return 'already_applied';
    }
}

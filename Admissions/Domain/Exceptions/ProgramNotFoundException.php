<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/ProgramNotFoundException.php
 * اسم الصفحة: ProgramNotFoundException.php
 * الوظيفة: استثناء يُرمى عند محاولة التقديم أو الاستعلام عن برنامج دراسي غير موجود.
 */

namespace Admissions\Domain\Exceptions;

final class ProgramNotFoundException extends AdmissionsDomainException
{
    public static function withId(string $programId): self
    {
        return new self("Academic program [{$programId}] was not found.");
    }

    public function errorCode(): string
    {
        return 'program_not_found';
    }
}

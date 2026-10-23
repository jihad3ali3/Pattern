<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Events/ApplicationWithdrawn.php
 * اسم الصفحة: ApplicationWithdrawn.php
 * الوظيفة: حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند انسحاب الطالب من طلب القبول (سواء قبل القبول أو بعده).
 */

namespace Admissions\Domain\Events;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final readonly class ApplicationWithdrawn
{
    public function __construct(
        public ApplicationId $applicationId,
        public StudentId $studentId,
        public ProgramId $programId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}

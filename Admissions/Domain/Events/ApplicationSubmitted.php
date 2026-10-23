<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Events/ApplicationSubmitted.php
 * اسم الصفحة: ApplicationSubmitted.php
 * الوظيفة: حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند نجاح الطالب في تقديم طلب القبول وتحقيقه للحد الأدنى للمعدل.
 */

namespace Admissions\Domain\Events;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final readonly class ApplicationSubmitted
{
    public function __construct(
        public ApplicationId $applicationId,
        public StudentId $studentId,
        public ProgramId $programId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}

<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Events/ApplicationRejected.php
 * اسم الصفحة: ApplicationRejected.php
 * الوظيفة: حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند رفض طلب القبول مع الاحتفاظ بسبب الرفض ووقت حدوث القرار.
 */

namespace Admissions\Domain\Events;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final readonly class ApplicationRejected
{
    public function __construct(
        public ApplicationId $applicationId,
        public StudentId $studentId,
        public ProgramId $programId,
        public string $reason,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}

<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Events/ApplicationAccepted.php
 * اسم الصفحة: ApplicationAccepted.php
 * الوظيفة: حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند قبول الطلب وحجز مقعد فعلي له في البرنامج الدراسي، وتستمع إليه الأنظمة الخارجية مثل الإشعارات.
 */

namespace Admissions\Domain\Events;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final readonly class ApplicationAccepted
{
    public function __construct(
        public ApplicationId $applicationId,
        public StudentId $studentId,
        public ProgramId $programId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}

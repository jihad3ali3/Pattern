<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/DTOs/ApplicationReadDTO.php
 * اسم الصفحة: ApplicationReadDTO.php
 * الوظيفة: كائن نقل بيانات للقراءة (Read DTO) يعيد بيانات طلب القبول من طبقة التطبيق إلى طبقة العرض دون تسريب كيان المجال أو نموذج Eloquent.
 */

namespace Admissions\Application\DTOs;

use Admissions\Domain\Entities\AdmissionApplication;
use DateTimeInterface;

final readonly class ApplicationReadDTO
{
    public function __construct(
        public string $id,
        public string $studentId,
        public string $programId,
        public float $gpa,
        public string $status,
        public string $statusLabel,
        public ?string $decisionReason,
        public ?string $submittedAt,
        public ?string $decidedAt,
    ) {
    }

    public static function fromEntity(AdmissionApplication $application): self
    {
        return new self(
            id: $application->id()->value(),
            studentId: $application->studentId()->value(),
            programId: $application->programId()->value(),
            gpa: $application->gpa()->value(),
            status: $application->status()->value,
            statusLabel: $application->status()->label(),
            decisionReason: $application->decisionReason(),
            submittedAt: $application->submittedAt()?->format(DateTimeInterface::ATOM),
            decidedAt: $application->decidedAt()?->format(DateTimeInterface::ATOM),
        );
    }
}

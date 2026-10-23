<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Entities/AdmissionApplication.php
 * اسم الصفحة: AdmissionApplication.php
 * الوظيفة: كيان وجذر تجمّع (Aggregate Root) يمثل طلب القبول الجامعي، يدير دورة حياة الطلب (تقديم، قبول، رفض، انسحاب)، يتحقق من شرط المعدل الأدنى وانتقالات الحالة، ويسجّل أحداث المجال داخلياً.
 */

namespace Admissions\Domain\Entities;

use Admissions\Domain\Enums\ApplicationStatus;
use Admissions\Domain\Events\ApplicationAccepted;
use Admissions\Domain\Events\ApplicationRejected;
use Admissions\Domain\Events\ApplicationSubmitted;
use Admissions\Domain\Events\ApplicationWithdrawn;
use Admissions\Domain\Exceptions\GpaBelowMinimumException;
use Admissions\Domain\Exceptions\InvalidTransitionException;
use Admissions\Domain\Exceptions\RejectionReasonRequiredException;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final class AdmissionApplication
{
    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly ApplicationId $id,
        private readonly StudentId $studentId,
        private readonly ProgramId $programId,
        private readonly Gpa $gpa,
        private ApplicationStatus $status,
        private ?string $decisionReason,
        private ?DateTimeImmutable $submittedAt,
        private ?DateTimeImmutable $decidedAt,
    ) {
    }

    public static function draft(
        ApplicationId $id,
        StudentId $studentId,
        ProgramId $programId,
        Gpa $gpa,
    ): self {
        return new self(
            id: $id,
            studentId: $studentId,
            programId: $programId,
            gpa: $gpa,
            status: ApplicationStatus::Draft,
            decisionReason: null,
            submittedAt: null,
            decidedAt: null,
        );
    }

    public static function reconstitute(
        ApplicationId $id,
        StudentId $studentId,
        ProgramId $programId,
        Gpa $gpa,
        ApplicationStatus $status,
        ?string $decisionReason,
        ?DateTimeImmutable $submittedAt = null,
        ?DateTimeImmutable $decidedAt = null,
    ): self {
        return new self(
            id: $id,
            studentId: $studentId,
            programId: $programId,
            gpa: $gpa,
            status: $status,
            decisionReason: $decisionReason,
            submittedAt: $submittedAt,
            decidedAt: $decidedAt,
        );
    }

    public function id(): ApplicationId
    {
        return $this->id;
    }

    public function studentId(): StudentId
    {
        return $this->studentId;
    }

    public function programId(): ProgramId
    {
        return $this->programId;
    }

    public function gpa(): Gpa
    {
        return $this->gpa;
    }

    public function status(): ApplicationStatus
    {
        return $this->status;
    }

    public function decisionReason(): ?string
    {
        return $this->decisionReason;
    }

    public function submittedAt(): ?DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function decidedAt(): ?DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function submit(Gpa $minimumGpa, DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Submitted);

        if (! $this->gpa->meets($minimumGpa)) {
            throw GpaBelowMinimumException::forGpa($this->gpa->value(), $minimumGpa->value());
        }

        $this->status = ApplicationStatus::Submitted;
        $this->submittedAt = $at;

        $this->domainEvents[] = new ApplicationSubmitted(
            applicationId: $this->id,
            studentId: $this->studentId,
            programId: $this->programId,
            occurredAt: $at,
        );
    }

    public function accept(DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Accepted);

        $this->status = ApplicationStatus::Accepted;
        $this->decisionReason = null;
        $this->decidedAt = $at;

        $this->domainEvents[] = new ApplicationAccepted(
            applicationId: $this->id,
            studentId: $this->studentId,
            programId: $this->programId,
            occurredAt: $at,
        );
    }

    public function reject(string $reason, DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Rejected);

        $trimmedReason = trim($reason);

        if ($trimmedReason === '') {
            throw RejectionReasonRequiredException::forApplication($this->id);
        }

        $this->status = ApplicationStatus::Rejected;
        $this->decisionReason = $trimmedReason;
        $this->decidedAt = $at;

        $this->domainEvents[] = new ApplicationRejected(
            applicationId: $this->id,
            studentId: $this->studentId,
            programId: $this->programId,
            reason: $trimmedReason,
            occurredAt: $at,
        );
    }

    public function withdraw(DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Withdrawn);

        $this->status = ApplicationStatus::Withdrawn;
        $this->decidedAt = $at;

        $this->domainEvents[] = new ApplicationWithdrawn(
            applicationId: $this->id,
            studentId: $this->studentId,
            programId: $this->programId,
            occurredAt: $at,
        );
    }

    public function assertCanBeAccepted(): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Accepted);
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function assertCanTransitionTo(ApplicationStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw InvalidTransitionException::between($this->status, $next);
        }
    }
}

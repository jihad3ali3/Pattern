<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Persistence/Repositories/EloquentAdmissionApplicationRepository.php
 * اسم الصفحة: EloquentAdmissionApplicationRepository.php
 * الوظيفة: التنفيذ الفعلي لمستودع طلبات القبول باستخدام Eloquent؛ يحوّل بين كيان المجال AdmissionApplication وسجل قاعدة البيانات ApplicationEloquentModel، ويترجم أخطاء قيود الفهرس الفريد إلى استثناء مجال.
 */

namespace Admissions\Infrastructure\Persistence\Repositories;

use Admissions\Domain\Entities\AdmissionApplication;
use Admissions\Domain\Enums\ApplicationStatus;
use Admissions\Domain\Exceptions\AlreadyAppliedException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use Admissions\Infrastructure\Persistence\Eloquent\ApplicationEloquentModel;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class EloquentAdmissionApplicationRepository implements AdmissionApplicationRepositoryInterface
{
    public function find(ApplicationId $id): ?AdmissionApplication
    {
        $record = ApplicationEloquentModel::query()->find($id->value());

        return $this->toDomainEntity($record);
    }

    public function findForUpdate(ApplicationId $id): ?AdmissionApplication
    {
        $record = ApplicationEloquentModel::query()
            ->lockForUpdate()
            ->find($id->value());

        return $this->toDomainEntity($record);
    }

    public function existsForStudentAndProgram(StudentId $studentId, ProgramId $programId): bool
    {
        return ApplicationEloquentModel::query()
            ->where('student_id', $studentId->value())
            ->where('program_id', $programId->value())
            ->exists();
    }

    public function save(AdmissionApplication $application): void
    {
        $attributes = [
            'id' => $application->id()->value(),
            'student_id' => $application->studentId()->value(),
            'program_id' => $application->programId()->value(),
            'gpa' => number_format($application->gpa()->value(), 2, '.', ''),
            'status' => $application->status()->value,
            'decision_reason' => $application->decisionReason(),
            'submitted_at' => $application->submittedAt()?->format('Y-m-d H:i:s'),
            'decided_at' => $application->decidedAt()?->format('Y-m-d H:i:s'),
        ];

        $record = ApplicationEloquentModel::query()->find($application->id()->value());

        if ($record === null) {
            try {
                (new ApplicationEloquentModel())->forceFill($attributes)->save();
            } catch (UniqueConstraintViolationException) {
                throw AlreadyAppliedException::forStudentAndProgram(
                    $application->studentId()->value(),
                    $application->programId()->value(),
                );
            }

            return;
        }

        $record->forceFill($attributes)->save();
    }

    public function deleteDraftsOlderThan(DateTimeImmutable $cutoff): int
    {
        return (int) ApplicationEloquentModel::query()
            ->where('status', ApplicationStatus::Draft->value)
            ->where('created_at', '<', $cutoff->format('Y-m-d H:i:s'))
            ->delete();
    }

    private function toDomainEntity(?ApplicationEloquentModel $record): ?AdmissionApplication
    {
        if ($record === null) {
            return null;
        }

        return AdmissionApplication::reconstitute(
            id: ApplicationId::fromString((string) $record->id),
            studentId: StudentId::fromString((string) $record->student_id),
            programId: ProgramId::fromString((string) $record->program_id),
            gpa: Gpa::fromDatabase($record->gpa),
            status: ApplicationStatus::from((string) $record->status),
            decisionReason: $record->decision_reason,
            submittedAt: $this->toImmutableDateTime($record->submitted_at),
            decidedAt: $this->toImmutableDateTime($record->decided_at),
        );
    }

    private function toImmutableDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}

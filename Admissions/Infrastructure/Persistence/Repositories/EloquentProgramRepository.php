<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Persistence/Repositories/EloquentProgramRepository.php
 * اسم الصفحة: EloquentProgramRepository.php
 * الوظيفة: التنفيذ الفعلي لمستودع البرامج الدراسية باستخدام Eloquent؛ يحوّل بين كيان المجال Program وسجل قاعدة البيانات ProgramEloquentModel مع دعم القفل التشاؤمي.
 */

namespace Admissions\Infrastructure\Persistence\Repositories;

use Admissions\Domain\Entities\Program;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Infrastructure\Persistence\Eloquent\ProgramEloquentModel;

final readonly class EloquentProgramRepository implements ProgramRepositoryInterface
{
    public function find(ProgramId $id): ?Program
    {
        $record = ProgramEloquentModel::query()->find($id->value());

        return $this->toDomainEntity($record);
    }

    public function findForUpdate(ProgramId $id): ?Program
    {
        $record = ProgramEloquentModel::query()
            ->lockForUpdate()
            ->find($id->value());

        return $this->toDomainEntity($record);
    }

    public function save(Program $program): void
    {
        ProgramEloquentModel::query()->updateOrCreate(
            ['id' => $program->id()->value()],
            [
                'name' => $program->name(),
                'min_gpa' => number_format($program->minimumGpa()->value(), 2, '.', ''),
                'capacity' => $program->capacity(),
                'reserved_seats' => $program->reservedSeats(),
            ],
        );
    }

    private function toDomainEntity(?ProgramEloquentModel $record): ?Program
    {
        if ($record === null) {
            return null;
        }

        return Program::reconstitute(
            id: ProgramId::fromString((string) $record->id),
            name: (string) $record->name,
            minimumGpa: Gpa::fromDatabase($record->min_gpa),
            capacity: (int) $record->capacity,
            reservedSeats: (int) $record->reserved_seats,
        );
    }
}

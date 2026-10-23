<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Repositories/AdmissionApplicationRepositoryInterface.php
 * اسم الصفحة: AdmissionApplicationRepositoryInterface.php
 * الوظيفة: واجهة مستودع المجال (Domain Repository Interface) تحدد عقد استرجاع وحفظ كيان طلب القبول دون الارتباط بـ Eloquent أو أي قاعدة بيانات معينة.
 */

namespace Admissions\Domain\Repositories;

use Admissions\Domain\Entities\AdmissionApplication;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

interface AdmissionApplicationRepositoryInterface
{
    public function find(ApplicationId $id): ?AdmissionApplication;

    /** قراءة السجل مع قفل التحديث التشاؤمي (SELECT ... FOR UPDATE) لمنع حالات السباق. */
    public function findForUpdate(ApplicationId $id): ?AdmissionApplication;

    public function existsForStudentAndProgram(StudentId $studentId, ProgramId $programId): bool;

    public function save(AdmissionApplication $application): void;

    /** حذف المسودات المهجورة التي لم تُقدَّم قبل التاريخ المحدد وإرجاع عدد السجلات المحذوفة. */
    public function deleteDraftsOlderThan(DateTimeImmutable $cutoff): int;
}

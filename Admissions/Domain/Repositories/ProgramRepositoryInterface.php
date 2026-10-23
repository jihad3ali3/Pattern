<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Repositories/ProgramRepositoryInterface.php
 * اسم الصفحة: ProgramRepositoryInterface.php
 * الوظيفة: واجهة مستودع المجال (Domain Repository Interface) تحدد عقد استرجاع وحفظ تجمّع البرنامج الدراسي مع دعم القفل التشاؤمي عند حجز أو تحرير المقاعد.
 */

namespace Admissions\Domain\Repositories;

use Admissions\Domain\Entities\Program;
use Admissions\Domain\ValueObjects\ProgramId;

interface ProgramRepositoryInterface
{
    public function find(ProgramId $id): ?Program;

    /** قراءة البرنامج مع قفل الصف (SELECT ... FOR UPDATE) لحماية عداد المقاعد من التعديل المتزامن. */
    public function findForUpdate(ProgramId $id): ?Program;

    public function save(Program $program): void;
}

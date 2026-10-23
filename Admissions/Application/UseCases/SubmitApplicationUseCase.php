<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/SubmitApplicationUseCase.php
 * اسم الصفحة: SubmitApplicationUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تنسّق سيناريو تقديم طلب قبول جديد: التحقق من وجود البرنامج وعدم تكرار التقديم، إنشاء الطلب وتقديمه، حفظه، ونشر حدث التقديم.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Application\DTOs\SubmitApplicationDTO;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Interfaces\IdGeneratorInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Entities\AdmissionApplication;
use Admissions\Domain\Exceptions\AlreadyAppliedException;
use Admissions\Domain\Exceptions\ProgramNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;

final readonly class SubmitApplicationUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ProgramRepositoryInterface $programRepository,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
        private UnitOfWorkService $unitOfWork,
    ) {
    }

    public function execute(SubmitApplicationDTO $dto): ApplicationReadDTO
    {
        $studentId = StudentId::fromString($dto->studentId);
        $programId = ProgramId::fromString($dto->programId);
        $gpa = Gpa::fromFloat($dto->gpa);

        return $this->unitOfWork->execute(
            function () use ($studentId, $programId, $gpa): ApplicationReadDTO {
                $program = $this->programRepository->find($programId)
                    ?? throw ProgramNotFoundException::withId($programId->value());

                if ($this->applicationRepository->existsForStudentAndProgram($studentId, $programId)) {
                    throw AlreadyAppliedException::forStudentAndProgram(
                        $studentId->value(),
                        $programId->value(),
                    );
                }

                $application = AdmissionApplication::draft(
                    id: ApplicationId::fromString($this->idGenerator->nextUlid()),
                    studentId: $studentId,
                    programId: $programId,
                    gpa: $gpa,
                );

                $application->submit($program->minimumGpa(), $this->clock->now());

                $this->applicationRepository->save($application);
                $this->unitOfWork->collectEventsFrom($application);

                return ApplicationReadDTO::fromEntity($application);
            }
        );
    }
}

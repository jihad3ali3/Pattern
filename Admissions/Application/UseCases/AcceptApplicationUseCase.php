<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/AcceptApplicationUseCase.php
 * اسم الصفحة: AcceptApplicationUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تنسّق سيناريو قبول طلب الطالب: قفل الطلب والبرنامج بترتيب ثابت، تفويض القرار وحجز المقعد لخدمة المجال، حفظ التغييرات، ونشر حدث القبول.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Exceptions\ApplicationNotFoundException;
use Admissions\Domain\Exceptions\ProgramNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\Services\AdmissionDecisionDomainService;
use Admissions\Domain\ValueObjects\ApplicationId;

final readonly class AcceptApplicationUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ProgramRepositoryInterface $programRepository,
        private AdmissionDecisionDomainService $decisionService,
        private ClockInterface $clock,
        private UnitOfWorkService $unitOfWork,
    ) {
    }

    public function execute(string $applicationId): ApplicationReadDTO
    {
        $id = ApplicationId::fromString($applicationId);

        return $this->unitOfWork->execute(function () use ($id): ApplicationReadDTO {
            // قفل الطلب أولاً ثم البرنامج دائماً لمنع الـ Deadlock
            $application = $this->applicationRepository->findForUpdate($id)
                ?? throw ApplicationNotFoundException::withId($id->value());

            $program = $this->programRepository->findForUpdate($application->programId())
                ?? throw ProgramNotFoundException::withId($application->programId()->value());

            $this->decisionService->accept($application, $program, $this->clock->now());

            $this->applicationRepository->save($application);
            $this->programRepository->save($program);

            $this->unitOfWork->collectEventsFrom($application, $program);

            return ApplicationReadDTO::fromEntity($application);
        });
    }
}

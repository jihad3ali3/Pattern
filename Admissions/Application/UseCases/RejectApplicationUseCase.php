<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/RejectApplicationUseCase.php
 * اسم الصفحة: RejectApplicationUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تنسّق سيناريو رفض طلب القبول مع إلزامية ذكر سبب الرفض وحفظ القرار ونشر حدث الرفض.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Application\DTOs\RejectApplicationDTO;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Exceptions\ApplicationNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\ValueObjects\ApplicationId;

final readonly class RejectApplicationUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ClockInterface $clock,
        private UnitOfWorkService $unitOfWork,
    ) {
    }

    public function execute(RejectApplicationDTO $dto): ApplicationReadDTO
    {
        $id = ApplicationId::fromString($dto->applicationId);

        return $this->unitOfWork->execute(function () use ($id, $dto): ApplicationReadDTO {
            $application = $this->applicationRepository->findForUpdate($id)
                ?? throw ApplicationNotFoundException::withId($id->value());

            $application->reject($dto->reason, $this->clock->now());

            $this->applicationRepository->save($application);
            $this->unitOfWork->collectEventsFrom($application);

            return ApplicationReadDTO::fromEntity($application);
        });
    }
}

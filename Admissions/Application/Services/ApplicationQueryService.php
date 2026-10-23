<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Services/ApplicationQueryService.php
 * اسم الصفحة: ApplicationQueryService.php
 * الوظيفة: خدمة تطبيق مسؤولة عن استعلامات القراءة الخاصة بطلبات القبول وإرجاعها ككائنات ApplicationReadDTO لطبقة العرض.
 */

namespace Admissions\Application\Services;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Domain\Exceptions\ApplicationNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\ValueObjects\ApplicationId;

final readonly class ApplicationQueryService
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
    ) {
    }

    public function getById(string $applicationId): ApplicationReadDTO
    {
        $id = ApplicationId::fromString($applicationId);

        $application = $this->applicationRepository->find($id)
            ?? throw ApplicationNotFoundException::withId($id->value());

        return ApplicationReadDTO::fromEntity($application);
    }
}

<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/ExpireDraftApplicationsUseCase.php
 * اسم الصفحة: ExpireDraftApplicationsUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تحذف مسودات طلبات القبول المتروكة لفترة تتجاوز عدد الأيام المحدد، وتُستدعى من أوامر الـ Console المجدولة.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use InvalidArgumentException;

final readonly class ExpireDraftApplicationsUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ClockInterface $clock,
    ) {
    }

    public function execute(int $olderThanDays = 30): int
    {
        if ($olderThanDays < 1) {
            throw new InvalidArgumentException('Days threshold must be at least 1.');
        }

        $cutoff = $this->clock->now()->modify("-{$olderThanDays} days");

        return $this->applicationRepository->deleteDraftsOlderThan($cutoff);
    }
}

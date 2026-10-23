<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/DTOs/RejectApplicationDTO.php
 * اسم الصفحة: RejectApplicationDTO.php
 * الوظيفة: كائن نقل بيانات (DTO) يحمل معرّف الطلب وسبب الرفض من طبقة العرض إلى حالة الاستخدام RejectApplicationUseCase.
 */

namespace Admissions\Application\DTOs;

final readonly class RejectApplicationDTO
{
    public function __construct(
        public string $applicationId,
        public string $reason,
    ) {
    }
}

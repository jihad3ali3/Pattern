<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/DTOs/SubmitApplicationDTO.php
 * اسم الصفحة: SubmitApplicationDTO.php
 * الوظيفة: كائن نقل بيانات (DTO) يحمل مدخلات تقديم طلب قبول جديد من طبقة العرض إلى حالة الاستخدام SubmitApplicationUseCase دون تمرير كائن Request.
 */

namespace Admissions\Application\DTOs;

final readonly class SubmitApplicationDTO
{
    public function __construct(
        public string $studentId,
        public string $programId,
        public float $gpa,
    ) {
    }
}

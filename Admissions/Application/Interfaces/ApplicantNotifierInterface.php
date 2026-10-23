<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Interfaces/ApplicantNotifierInterface.php
 * اسم الصفحة: ApplicantNotifierInterface.php
 * الوظيفة: منفذ (Port / Interface) لإرسال إشعارات القبول للطالب دون ربط طبقة التطبيق بتفاصيل البريد الإلكتروني أو خدمة الإشعارات في البنية التحتية.
 */

namespace Admissions\Application\Interfaces;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;

interface ApplicantNotifierInterface
{
    public function sendAcceptanceNotification(
        StudentId $studentId,
        ProgramId $programId,
        ApplicationId $applicationId,
    ): void;
}

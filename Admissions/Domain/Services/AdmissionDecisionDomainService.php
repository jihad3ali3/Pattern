<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Services/AdmissionDecisionDomainService.php
 * اسم الصفحة: AdmissionDecisionDomainService.php
 * الوظيفة: خدمة مجال (Domain Service) تنسّق قواعد العمل التي تعبر تجمّعين معاً (AdmissionApplication و Program) مثل اشتراط توفر مقعد قبل القبول، وتحرير المقعد المحجوز عند انسحاب طالب مقبول.
 */

namespace Admissions\Domain\Services;

use Admissions\Domain\Entities\AdmissionApplication;
use Admissions\Domain\Entities\Program;
use Admissions\Domain\Enums\ApplicationStatus;
use DateTimeImmutable;
use LogicException;

final class AdmissionDecisionDomainService
{
    public function accept(
        AdmissionApplication $application,
        Program $program,
        DateTimeImmutable $at,
    ): void {
        if (! $application->programId()->equals($program->id())) {
            throw new LogicException('Application programId does not match the target Program aggregate.');
        }

        // التحقق السريع من قابلية قبول الطلب قبل لمس مقاعد البرنامج
        $application->assertCanBeAccepted();

        // حجز المقعد أولاً (يرمي NoSeatsAvailableException إن امتلأ البرنامج)
        $program->reserveSeat();

        // تغيير حالة الطلب وتسجيل حدث ApplicationAccepted
        $application->accept($at);
    }

    public function withdraw(
        AdmissionApplication $application,
        Program $program,
        DateTimeImmutable $at,
    ): void {
        if (! $application->programId()->equals($program->id())) {
            throw new LogicException('Application programId does not match the target Program aggregate.');
        }

        $wasAccepted = $application->status() === ApplicationStatus::Accepted;

        $application->withdraw($at);

        if ($wasAccepted) {
            $program->releaseSeat();
        }
    }
}

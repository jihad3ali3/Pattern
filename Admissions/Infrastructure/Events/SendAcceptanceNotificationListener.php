<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Events/SendAcceptanceNotificationListener.php
 * اسم الصفحة: SendAcceptanceNotificationListener.php
 * الوظيفة: مستمع أحداث غير متزامن (Queued Listener) يلتقط حدث ApplicationAccepted عبر الطابور ويفوّض إرسال إشعار القبول إلى ApplicantNotifierInterface.
 */

namespace Admissions\Infrastructure\Events;

use Admissions\Application\Interfaces\ApplicantNotifierInterface;
use Admissions\Domain\Events\ApplicationAccepted;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class SendAcceptanceNotificationListener implements ShouldQueue
{
    public function __construct(
        private ApplicantNotifierInterface $notifier,
    ) {
    }

    public function handle(ApplicationAccepted $event): void
    {
        $this->notifier->sendAcceptanceNotification(
            studentId: $event->studentId,
            programId: $event->programId,
            applicationId: $event->applicationId,
        );
    }
}

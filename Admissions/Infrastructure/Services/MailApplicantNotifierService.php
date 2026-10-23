<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Services/MailApplicantNotifierService.php
 * اسم الصفحة: MailApplicantNotifierService.php
 * الوظيفة: محوّل بنية تحتية ينفّذ واجهة ApplicantNotifierInterface لإرسال بريد القبول للطالب مع ضمان عدم التكرار (Idempotency) عبر جدول sent_notifications.
 */

namespace Admissions\Infrastructure\Services;

use Admissions\Application\Interfaces\ApplicantNotifierInterface;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final readonly class MailApplicantNotifierService implements ApplicantNotifierInterface
{
    public function sendAcceptanceNotification(
        StudentId $studentId,
        ProgramId $programId,
        ApplicationId $applicationId,
    ): void {
        $idempotencyKey = 'acceptance_mail:' . $applicationId->value();

        if (DB::table('sent_notifications')->where('notification_key', $idempotencyKey)->exists()) {
            return;
        }

        $student = DB::table('users')->where('id', $studentId->value())->first();
        $programName = DB::table('programs')->where('id', $programId->value())->value('name');

        if ($student === null || $programName === null) {
            return;
        }

        Mail::raw(
            "تهانينا {$student->name}، تم قبول طلبك رقم ({$applicationId->value()}) في برنامج {$programName}.",
            static function ($message) use ($student): void {
                $message->to($student->email)->subject('إشعار القبول الجامعي');
            },
        );

        DB::table('sent_notifications')->insertOrIgnore([
            'notification_key' => $idempotencyKey,
            'sent_at' => now(),
        ]);
    }
}

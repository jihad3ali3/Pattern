<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Enums/ApplicationStatus.php
 * اسم الصفحة: ApplicationStatus.php
 * الوظيفة: تعداد (Enum) يمثل الحالات الممكنة لطلب القبول الجامعي ويحدد جدول الانتقالات المسموحة بينها كآلة حالة (State Machine).
 */

namespace Admissions\Domain\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => $next === self::Submitted,
            self::Submitted => in_array($next, [self::Accepted, self::Rejected, self::Withdrawn], true),
            self::Accepted => $next === self::Withdrawn,
            self::Rejected, self::Withdrawn => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Submitted => 'مُقدَّم',
            self::Accepted => 'مقبول',
            self::Rejected => 'مرفوض',
            self::Withdrawn => 'منسحب',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Rejected, self::Withdrawn], true);
    }
}

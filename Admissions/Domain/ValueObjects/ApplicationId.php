<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/ValueObjects/ApplicationId.php
 * اسم الصفحة: ApplicationId.php
 * الوظيفة: كائن قيمة (Value Object) يمثل المعرّف الفريد لطلب القبول بصيغة ULID ويمنع تمرير معرّفات غير صالحة أو الخلط بين معرّفات الكيانات المختلفة.
 */

namespace Admissions\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ApplicationId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $value) !== 1) {
            throw new InvalidArgumentException("Invalid ApplicationId [{$value}]. Must be a 26-character ULID.");
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

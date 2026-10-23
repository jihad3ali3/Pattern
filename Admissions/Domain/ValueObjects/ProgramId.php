<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/ValueObjects/ProgramId.php
 * اسم الصفحة: ProgramId.php
 * الوظيفة: كائن قيمة (Value Object) يمثل المعرّف الفريد للبرنامج الدراسي بصيغة ULID لضمان سلامة النوع (Type Safety) وعدم الخلط بينه وبين معرّف الطلب أو الطالب.
 */

namespace Admissions\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ProgramId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $value) !== 1) {
            throw new InvalidArgumentException("Invalid ProgramId [{$value}]. Must be a 26-character ULID.");
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

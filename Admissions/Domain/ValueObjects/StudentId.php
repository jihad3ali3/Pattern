<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/ValueObjects/StudentId.php
 * اسم الصفحة: StudentId.php
 * الوظيفة: كائن قيمة (Value Object) يمثل معرّف الطالب المتقدّم ويضمن عدم تمرير قيمة فارغة داخل طبقة المجال.
 */

namespace Admissions\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class StudentId
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('StudentId cannot be empty.');
        }

        $this->value = $trimmed;
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

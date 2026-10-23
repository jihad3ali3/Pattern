<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/ValueObjects/Gpa.php
 * اسم الصفحة: Gpa.php
 * الوظيفة: كائن قيمة (Value Object) غير قابل للتغيير يمثل المعدل التراكمي للطالب، يتحقق من وقوعه بين 0.0 و 5.0، ويوفر سلوك مقارنته بالحد الأدنى للبرنامج.
 */

namespace Admissions\Domain\ValueObjects;

use Admissions\Domain\Exceptions\InvalidGpaException;

final readonly class Gpa
{
    private function __construct(private float $value)
    {
    }

    public static function fromFloat(float $value): self
    {
        if (! is_finite($value) || $value < 0.0 || $value > 5.0) {
            throw InvalidGpaException::outsideRange($value);
        }

        return new self(round($value, 2));
    }

    public static function fromDatabase(mixed $value): self
    {
        if (! is_numeric($value)) {
            throw InvalidGpaException::notNumeric($value);
        }

        return self::fromFloat((float) $value);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function meets(self $minimum): bool
    {
        return $this->value >= $minimum->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

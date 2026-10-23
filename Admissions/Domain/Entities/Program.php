<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Entities/Program.php
 * اسم الصفحة: Program.php
 * الوظيفة: كيان وجذر تجمّع (Aggregate Root) يمثل البرنامج الدراسي، يحمي ثوابت السعة الاستيعابية والمقاعد المحجوزة، ويوفر عمليات حجز المقعد وتحريره عند الانسحاب.
 */

namespace Admissions\Domain\Entities;

use Admissions\Domain\Exceptions\NoSeatsAvailableException;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use LogicException;

final class Program
{
    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly ProgramId $id,
        private readonly string $name,
        private readonly Gpa $minimumGpa,
        private int $capacity,
        private int $reservedSeats,
    ) {
    }

    public static function open(
        ProgramId $id,
        string $name,
        Gpa $minimumGpa,
        int $capacity,
    ): self {
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            throw new LogicException('Program name cannot be empty.');
        }

        if ($capacity < 1) {
            throw new LogicException('Program capacity must be at least 1.');
        }

        return new self($id, $trimmedName, $minimumGpa, $capacity, 0);
    }

    public static function reconstitute(
        ProgramId $id,
        string $name,
        Gpa $minimumGpa,
        int $capacity,
        int $reservedSeats,
    ): self {
        if ($capacity < 1 || $reservedSeats < 0 || $reservedSeats > $capacity) {
            throw new LogicException('Corrupted program state: reserved seats must be between 0 and capacity.');
        }

        return new self($id, $name, $minimumGpa, $capacity, $reservedSeats);
    }

    public function id(): ProgramId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function minimumGpa(): Gpa
    {
        return $this->minimumGpa;
    }

    public function capacity(): int
    {
        return $this->capacity;
    }

    public function reservedSeats(): int
    {
        return $this->reservedSeats;
    }

    public function availableSeats(): int
    {
        return $this->capacity - $this->reservedSeats;
    }

    public function reserveSeat(): void
    {
        if ($this->availableSeats() < 1) {
            throw NoSeatsAvailableException::forProgram($this->name);
        }

        $this->reservedSeats++;
    }

    public function releaseSeat(): void
    {
        if ($this->reservedSeats < 1) {
            throw new LogicException('Cannot release a seat when no seats are reserved.');
        }

        $this->reservedSeats--;
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}

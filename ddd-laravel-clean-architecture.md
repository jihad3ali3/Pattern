# تطبيق خارطة `Admissions/` وفق DDD و Clean Architecture في Laravel 13

> تطبيق كامل ومترابط لموديول **القبول الجامعي (`Admissions/`)** وفق الخارطة المعمارية المطلوبة حرفياً، مع كتابة **المسار** و**اسم الصفحة** و**الوظيفة** فوق كل كود وداخل ترويسة كل ملف.

---

## ١. الخارطة المعمارية الكاملة والملفات المطبّقة

```text
Admissions/
├── Domain/
│   ├── Entities/
│   │   ├── Program.php
│   │   └── AdmissionApplication.php
│   ├── ValueObjects/
│   │   ├── ApplicationId.php
│   │   ├── ProgramId.php
│   │   ├── StudentId.php
│   │   └── Gpa.php
│   ├── Enums/
│   │   └── ApplicationStatus.php
│   ├── Events/
│   │   ├── ApplicationSubmitted.php
│   │   ├── ApplicationAccepted.php
│   │   ├── ApplicationRejected.php
│   │   └── ApplicationWithdrawn.php
│   ├── Exceptions/
│   │   ├── HasErrorCode.php
│   │   ├── AdmissionsDomainException.php
│   │   ├── InvalidGpaException.php
│   │   ├── InvalidTransitionException.php
│   │   ├── GpaBelowMinimumException.php
│   │   ├── NoSeatsAvailableException.php
│   │   ├── AlreadyAppliedException.php
│   │   ├── RejectionReasonRequiredException.php
│   │   ├── ApplicationNotFoundException.php
│   │   └── ProgramNotFoundException.php
│   ├── Repositories/
│   │   ├── AdmissionApplicationRepositoryInterface.php
│   │   └── ProgramRepositoryInterface.php
│   └── Services/
│       └── AdmissionDecisionDomainService.php
├── Application/
│   ├── DTOs/
│   │   ├── SubmitApplicationDTO.php
│   │   ├── RejectApplicationDTO.php
│   │   └── ApplicationReadDTO.php
│   ├── UseCases/
│   │   ├── SubmitApplicationUseCase.php
│   │   ├── AcceptApplicationUseCase.php
│   │   ├── RejectApplicationUseCase.php
│   │   ├── WithdrawApplicationUseCase.php
│   │   └── ExpireDraftApplicationsUseCase.php
│   ├── Interfaces/
│   │   ├── ClockInterface.php
│   │   ├── IdGeneratorInterface.php
│   │   ├── TransactionManagerInterface.php
│   │   ├── DomainEventDispatcherInterface.php
│   │   └── ApplicantNotifierInterface.php
│   └── Services/
│       ├── UnitOfWorkService.php
│       └── ApplicationQueryService.php
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   │   ├── ApplicationEloquentModel.php
│   │   │   └── ProgramEloquentModel.php
│   │   └── Repositories/
│   │       ├── EloquentAdmissionApplicationRepository.php
│   │       └── EloquentProgramRepository.php
│   ├── Events/
│   │   ├── LaravelDomainEventDispatcher.php
│   │   └── SendAcceptanceNotificationListener.php
│   ├── Services/
│   │   ├── SystemClockService.php
│   │   ├── UlidGeneratorService.php
│   │   ├── LaravelTransactionManager.php
│   │   └── MailApplicantNotifierService.php
│   └── Providers/
│       └── AdmissionsServiceProvider.php
└── Presentation/
    ├── Http/
    │   ├── Controllers/
    │   │   └── AdmissionApplicationController.php
    │   ├── Requests/
    │   │   ├── SubmitApplicationRequest.php
    │   │   └── RejectApplicationRequest.php
    │   ├── Resources/
    │   │   └── AdmissionApplicationResource.php
    │   └── Middleware/
    │       ├── EnsureAdmissionsWindowOpenMiddleware.php
    │       └── RenderAdmissionsExceptionMiddleware.php
    └── Console/
        └── ExpireDraftApplicationsCommand.php
```

---

## ٢. قاعدة اتجاه الاعتماديات (Dependency Rule)

```text
Presentation ──► Application ──► Domain ◄── Infrastructure
```

- **`Domain`**: لغة الأعمال وقواعدها فقط؛ لا يستورد أي شيء من `Illuminate` أو الطبقات الأخرى.
- **`Application`**: ينسّق السيناريوهات (`UseCases`) ويتخاطب مع الخارج عبر واجهات (`Interfaces`) ومستودعات المجال (`Domain/Repositories`).
- **`Infrastructure`**: ينفّذ واجهات المجال والتطبيق (`Eloquent`, `Events`, `Services`) ويربطها في `AdmissionsServiceProvider`.
- **`Presentation`**: يستقبل طلبات HTTP وأوامر `Console`، يحوّل المدخلات إلى `DTOs`، ويعيد المخرجات عبر `Resources`.

---

## أولاً: طبقة المجال — `Admissions/Domain/`

طبقة المجال (Domain Layer) هي قلب النظام؛ تحتوي على الكيانات، كائنات القيمة، التعدادات، الأحداث، الاستثناءات، واجهات المستودعات، وخدمات المجال. **لا تعتمد على Laravel ولا على Eloquent ولا على HTTP إطلاقاً.**

### ١. الكيانات والجذور — `Admissions/Domain/Entities/`

#### `Program.php`

- **المسار:** `Admissions/Domain/Entities/Program.php`
- **اسم الصفحة:** `Program.php`
- **الوظيفة:** كيان وجذر تجمّع (Aggregate Root) يمثل البرنامج الدراسي، يحمي ثوابت السعة الاستيعابية والمقاعد المحجوزة، ويوفر عمليات حجز المقعد وتحريره عند الانسحاب.

```php
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
```

#### `AdmissionApplication.php`

- **المسار:** `Admissions/Domain/Entities/AdmissionApplication.php`
- **اسم الصفحة:** `AdmissionApplication.php`
- **الوظيفة:** كيان وجذر تجمّع (Aggregate Root) يمثل طلب القبول الجامعي، يدير دورة حياة الطلب (تقديم، قبول، رفض، انسحاب)، يتحقق من شرط المعدل الأدنى وانتقالات الحالة، ويسجّل أحداث المجال داخلياً.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Entities/AdmissionApplication.php
 * اسم الصفحة: AdmissionApplication.php
 * الوظيفة: كيان وجذر تجمّع (Aggregate Root) يمثل طلب القبول الجامعي، يدير دورة حياة الطلب (تقديم، قبول، رفض، انسحاب)، يتحقق من شرط المعدل الأدنى وانتقالات الحالة، ويسجّل أحداث المجال داخلياً.
 */

namespace Admissions\Domain\Entities;

use Admissions\Domain\Enums\ApplicationStatus;
use Admissions\Domain\Events\ApplicationAccepted;
use Admissions\Domain\Events\ApplicationRejected;
use Admissions\Domain\Events\ApplicationSubmitted;
use Admissions\Domain\Events\ApplicationWithdrawn;
use Admissions\Domain\Exceptions\GpaBelowMinimumException;
use Admissions\Domain\Exceptions\InvalidTransitionException;
use Admissions\Domain\Exceptions\RejectionReasonRequiredException;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final class AdmissionApplication
{
    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly ApplicationId $id,
        private readonly StudentId $studentId,
        private readonly ProgramId $programId,
        private readonly Gpa $gpa,
        private ApplicationStatus $status,
        private ?string $decisionReason,
        private ?DateTimeImmutable $submittedAt,
        private ?DateTimeImmutable $decidedAt,
    ) {
    }

    public static function draft(
        ApplicationId $id,
        StudentId $studentId,
        ProgramId $programId,
        Gpa $gpa,
    ): self {
        return new self(
            id: $id,
            studentId: $studentId,
            programId: $programId,
            gpa: $gpa,
            status: ApplicationStatus::Draft,
            decisionReason: null,
            submittedAt: null,
            decidedAt: null,
        );
    }

    public static function reconstitute(
        ApplicationId $id,
        StudentId $studentId,
        ProgramId $programId,
        Gpa $gpa,
        ApplicationStatus $status,
        ?string $decisionReason,
        ?DateTimeImmutable $submittedAt = null,
        ?DateTimeImmutable $decidedAt = null,
    ): self {
        return new self(
            id: $id,
            studentId: $studentId,
            programId: $programId,
            gpa: $gpa,
            status: $status,
            decisionReason: $decisionReason,
            submittedAt: $submittedAt,
            decidedAt: $decidedAt,
        );
    }

    public function id(): ApplicationId
    {
        return $this->id;
    }

    public function studentId(): StudentId
    {
        return $this->studentId;
    }

    public function programId(): ProgramId
    {
        return $this->programId;
    }

    public function gpa(): Gpa
    {
        return $this->gpa;
    }

    public function status(): ApplicationStatus
    {
        return $this->status;
    }

    public function decisionReason(): ?string
    {
        return $this->decisionReason;
    }

    public function submittedAt(): ?DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function decidedAt(): ?DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function submit(Gpa $minimumGpa, DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Submitted);

        if (! $this->gpa->meets($minimumGpa)) {
            throw GpaBelowMinimumException::forGpa($this->gpa->value(), $minimumGpa->value());
        }

        $this->status = ApplicationStatus::Submitted;
        $this->submittedAt = $at;

        $this->domainEvents[] = new ApplicationSubmitted(
            applicationId: $this->id,
            studentId: $this->studentId,
            programId: $this->programId,
            occurredAt: $at,
        );
    }

    public function accept(DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Accepted);

        $this->status = ApplicationStatus::Accepted;
        $this->decisionReason = null;
        $this->decidedAt = $at;

        $this->domainEvents[] = new ApplicationAccepted(
            applicationId: $this->id,
            studentId: $this->studentId,
            programId: $this->programId,
            occurredAt: $at,
        );
    }

    public function reject(string $reason, DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Rejected);

        $trimmedReason = trim($reason);

        if ($trimmedReason === '') {
            throw RejectionReasonRequiredException::forApplication($this->id);
        }

        $this->status = ApplicationStatus::Rejected;
        $this->decisionReason = $trimmedReason;
        $this->decidedAt = $at;

        $this->domainEvents[] = new ApplicationRejected(
            applicationId: $this->id,
            studentId: $this->studentId,
            programId: $this->programId,
            reason: $trimmedReason,
            occurredAt: $at,
        );
    }

    public function withdraw(DateTimeImmutable $at): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Withdrawn);

        $this->status = ApplicationStatus::Withdrawn;
        $this->decidedAt = $at;

        $this->domainEvents[] = new ApplicationWithdrawn(
            applicationId: $this->id,
            studentId: $this->studentId,
            programId: $this->programId,
            occurredAt: $at,
        );
    }

    public function assertCanBeAccepted(): void
    {
        $this->assertCanTransitionTo(ApplicationStatus::Accepted);
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function assertCanTransitionTo(ApplicationStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw InvalidTransitionException::between($this->status, $next);
        }
    }
}
```

### ٢. كائنات القيمة — `Admissions/Domain/ValueObjects/`

#### `ApplicationId.php`

- **المسار:** `Admissions/Domain/ValueObjects/ApplicationId.php`
- **اسم الصفحة:** `ApplicationId.php`
- **الوظيفة:** كائن قيمة (Value Object) يمثل المعرّف الفريد لطلب القبول بصيغة ULID ويمنع تمرير معرّفات غير صالحة أو الخلط بين معرّفات الكيانات المختلفة.

```php
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
```

#### `ProgramId.php`

- **المسار:** `Admissions/Domain/ValueObjects/ProgramId.php`
- **اسم الصفحة:** `ProgramId.php`
- **الوظيفة:** كائن قيمة (Value Object) يمثل المعرّف الفريد للبرنامج الدراسي بصيغة ULID لضمان سلامة النوع (Type Safety) وعدم الخلط بينه وبين معرّف الطلب أو الطالب.

```php
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
```

#### `StudentId.php`

- **المسار:** `Admissions/Domain/ValueObjects/StudentId.php`
- **اسم الصفحة:** `StudentId.php`
- **الوظيفة:** كائن قيمة (Value Object) يمثل معرّف الطالب المتقدّم ويضمن عدم تمرير قيمة فارغة داخل طبقة المجال.

```php
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
```

#### `Gpa.php`

- **المسار:** `Admissions/Domain/ValueObjects/Gpa.php`
- **اسم الصفحة:** `Gpa.php`
- **الوظيفة:** كائن قيمة (Value Object) غير قابل للتغيير يمثل المعدل التراكمي للطالب، يتحقق من وقوعه بين 0.0 و 5.0، ويوفر سلوك مقارنته بالحد الأدنى للبرنامج.

```php
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
```

### ٣. التعدادات — `Admissions/Domain/Enums/`

#### `ApplicationStatus.php`

- **المسار:** `Admissions/Domain/Enums/ApplicationStatus.php`
- **اسم الصفحة:** `ApplicationStatus.php`
- **الوظيفة:** تعداد (Enum) يمثل الحالات الممكنة لطلب القبول الجامعي ويحدد جدول الانتقالات المسموحة بينها كآلة حالة (State Machine).

```php
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
```

### ٤. أحداث المجال — `Admissions/Domain/Events/`

#### `ApplicationSubmitted.php`

- **المسار:** `Admissions/Domain/Events/ApplicationSubmitted.php`
- **اسم الصفحة:** `ApplicationSubmitted.php`
- **الوظيفة:** حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند نجاح الطالب في تقديم طلب القبول وتحقيقه للحد الأدنى للمعدل.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Events/ApplicationSubmitted.php
 * اسم الصفحة: ApplicationSubmitted.php
 * الوظيفة: حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند نجاح الطالب في تقديم طلب القبول وتحقيقه للحد الأدنى للمعدل.
 */

namespace Admissions\Domain\Events;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final readonly class ApplicationSubmitted
{
    public function __construct(
        public ApplicationId $applicationId,
        public StudentId $studentId,
        public ProgramId $programId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
```

#### `ApplicationAccepted.php`

- **المسار:** `Admissions/Domain/Events/ApplicationAccepted.php`
- **اسم الصفحة:** `ApplicationAccepted.php`
- **الوظيفة:** حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند قبول الطلب وحجز مقعد فعلي له في البرنامج الدراسي، وتستمع إليه الأنظمة الخارجية مثل الإشعارات.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Events/ApplicationAccepted.php
 * اسم الصفحة: ApplicationAccepted.php
 * الوظيفة: حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند قبول الطلب وحجز مقعد فعلي له في البرنامج الدراسي، وتستمع إليه الأنظمة الخارجية مثل الإشعارات.
 */

namespace Admissions\Domain\Events;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final readonly class ApplicationAccepted
{
    public function __construct(
        public ApplicationId $applicationId,
        public StudentId $studentId,
        public ProgramId $programId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
```

#### `ApplicationRejected.php`

- **المسار:** `Admissions/Domain/Events/ApplicationRejected.php`
- **اسم الصفحة:** `ApplicationRejected.php`
- **الوظيفة:** حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند رفض طلب القبول مع الاحتفاظ بسبب الرفض ووقت حدوث القرار.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Events/ApplicationRejected.php
 * اسم الصفحة: ApplicationRejected.php
 * الوظيفة: حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند رفض طلب القبول مع الاحتفاظ بسبب الرفض ووقت حدوث القرار.
 */

namespace Admissions\Domain\Events;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final readonly class ApplicationRejected
{
    public function __construct(
        public ApplicationId $applicationId,
        public StudentId $studentId,
        public ProgramId $programId,
        public string $reason,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
```

#### `ApplicationWithdrawn.php`

- **المسار:** `Admissions/Domain/Events/ApplicationWithdrawn.php`
- **اسم الصفحة:** `ApplicationWithdrawn.php`
- **الوظيفة:** حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند انسحاب الطالب من طلب القبول (سواء قبل القبول أو بعده).

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Events/ApplicationWithdrawn.php
 * اسم الصفحة: ApplicationWithdrawn.php
 * الوظيفة: حدث مجال (Domain Event) غير قابل للتغيير يُسجَّل عند انسحاب الطالب من طلب القبول (سواء قبل القبول أو بعده).
 */

namespace Admissions\Domain\Events;

use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

final readonly class ApplicationWithdrawn
{
    public function __construct(
        public ApplicationId $applicationId,
        public StudentId $studentId,
        public ProgramId $programId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
```

### ٥. استثناءات المجال — `Admissions/Domain/Exceptions/`

#### `HasErrorCode.php`

- **المسار:** `Admissions/Domain/Exceptions/HasErrorCode.php`
- **اسم الصفحة:** `HasErrorCode.php`
- **الوظيفة:** عقد (Interface) يُلزم استثناءات المجال بتوفير كود خطأ نصي ثابت يمكن لطبقة العرض ترجمته إلى حالة HTTP مناسبة دون تلويث المجال بتفاصيل HTTP.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/HasErrorCode.php
 * اسم الصفحة: HasErrorCode.php
 * الوظيفة: عقد (Interface) يُلزم استثناءات المجال بتوفير كود خطأ نصي ثابت يمكن لطبقة العرض ترجمته إلى حالة HTTP مناسبة دون تلويث المجال بتفاصيل HTTP.
 */

namespace Admissions\Domain\Exceptions;

interface HasErrorCode
{
    public function errorCode(): string;
}
```

#### `AdmissionsDomainException.php`

- **المسار:** `Admissions/Domain/Exceptions/AdmissionsDomainException.php`
- **اسم الصفحة:** `AdmissionsDomainException.php`
- **الوظيفة:** الصنف الأب المجرد لجميع استثناءات مجال القبول الجامعي؛ يرث من DomainException ويطبق واجهة HasErrorCode.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/AdmissionsDomainException.php
 * اسم الصفحة: AdmissionsDomainException.php
 * الوظيفة: الصنف الأب المجرد لجميع استثناءات مجال القبول الجامعي؛ يرث من DomainException ويطبق واجهة HasErrorCode.
 */

namespace Admissions\Domain\Exceptions;

use DomainException;

abstract class AdmissionsDomainException extends DomainException implements HasErrorCode
{
}
```

#### `InvalidGpaException.php`

- **المسار:** `Admissions/Domain/Exceptions/InvalidGpaException.php`
- **اسم الصفحة:** `InvalidGpaException.php`
- **الوظيفة:** استثناء مجال يُرمى عند محاولة إنشاء كائن معدل تراكمي (Gpa) بقيمة غير رقمية أو خارج النطاق المسموح (0.0 إلى 5.0).

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/InvalidGpaException.php
 * اسم الصفحة: InvalidGpaException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة إنشاء كائن معدل تراكمي (Gpa) بقيمة غير رقمية أو خارج النطاق المسموح (0.0 إلى 5.0).
 */

namespace Admissions\Domain\Exceptions;

final class InvalidGpaException extends AdmissionsDomainException
{
    public static function outsideRange(float $value): self
    {
        return new self("GPA must be between 0.0 and 5.0, got [{$value}].");
    }

    public static function notNumeric(mixed $value): self
    {
        return new self('GPA must be numeric, got [' . get_debug_type($value) . '].');
    }

    public function errorCode(): string
    {
        return 'invalid_gpa';
    }
}
```

#### `InvalidTransitionException.php`

- **المسار:** `Admissions/Domain/Exceptions/InvalidTransitionException.php`
- **اسم الصفحة:** `InvalidTransitionException.php`
- **الوظيفة:** استثناء مجال يُرمى عند محاولة نقل طلب القبول من حالة إلى حالة أخرى غير مسموح بها في دورة حياة الطلب.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/InvalidTransitionException.php
 * اسم الصفحة: InvalidTransitionException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة نقل طلب القبول من حالة إلى حالة أخرى غير مسموح بها في دورة حياة الطلب.
 */

namespace Admissions\Domain\Exceptions;

use Admissions\Domain\Enums\ApplicationStatus;

final class InvalidTransitionException extends AdmissionsDomainException
{
    public static function between(ApplicationStatus $from, ApplicationStatus $to): self
    {
        return new self("Cannot transition application from [{$from->value}] to [{$to->value}].");
    }

    public function errorCode(): string
    {
        return 'invalid_transition';
    }
}
```

#### `GpaBelowMinimumException.php`

- **المسار:** `Admissions/Domain/Exceptions/GpaBelowMinimumException.php`
- **اسم الصفحة:** `GpaBelowMinimumException.php`
- **الوظيفة:** استثناء مجال يُرمى عند محاولة تقديم طلب قبول بمعدل تراكمي أقل من الحد الأدنى المشترط للبرنامج الدراسي.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/GpaBelowMinimumException.php
 * اسم الصفحة: GpaBelowMinimumException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة تقديم طلب قبول بمعدل تراكمي أقل من الحد الأدنى المشترط للبرنامج الدراسي.
 */

namespace Admissions\Domain\Exceptions;

final class GpaBelowMinimumException extends AdmissionsDomainException
{
    public static function forGpa(float $actual, float $minimum): self
    {
        return new self("Student GPA [{$actual}] is below the program minimum [{$minimum}].");
    }

    public function errorCode(): string
    {
        return 'gpa_below_minimum';
    }
}
```

#### `NoSeatsAvailableException.php`

- **المسار:** `Admissions/Domain/Exceptions/NoSeatsAvailableException.php`
- **اسم الصفحة:** `NoSeatsAvailableException.php`
- **الوظيفة:** استثناء مجال يُرمى عند محاولة حجز مقعد لطالب في برنامج دراسي استنفد كامل سعته الاستيعابية.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/NoSeatsAvailableException.php
 * اسم الصفحة: NoSeatsAvailableException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة حجز مقعد لطالب في برنامج دراسي استنفد كامل سعته الاستيعابية.
 */

namespace Admissions\Domain\Exceptions;

final class NoSeatsAvailableException extends AdmissionsDomainException
{
    public static function forProgram(string $programName): self
    {
        return new self("No seats available in program [{$programName}].");
    }

    public function errorCode(): string
    {
        return 'no_seats_available';
    }
}
```

#### `AlreadyAppliedException.php`

- **المسار:** `Admissions/Domain/Exceptions/AlreadyAppliedException.php`
- **اسم الصفحة:** `AlreadyAppliedException.php`
- **الوظيفة:** استثناء مجال يُرمى عند محاولة الطالب التقديم مرتين على نفس البرنامج الدراسي.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/AlreadyAppliedException.php
 * اسم الصفحة: AlreadyAppliedException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة الطالب التقديم مرتين على نفس البرنامج الدراسي.
 */

namespace Admissions\Domain\Exceptions;

final class AlreadyAppliedException extends AdmissionsDomainException
{
    public static function forStudentAndProgram(string $studentId, string $programId): self
    {
        return new self("Student [{$studentId}] has already applied to program [{$programId}].");
    }

    public function errorCode(): string
    {
        return 'already_applied';
    }
}
```

#### `RejectionReasonRequiredException.php`

- **المسار:** `Admissions/Domain/Exceptions/RejectionReasonRequiredException.php`
- **اسم الصفحة:** `RejectionReasonRequiredException.php`
- **الوظيفة:** استثناء مجال يُرمى عند محاولة رفض طلب قبول دون ذكر سبب واضح للرفض.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/RejectionReasonRequiredException.php
 * اسم الصفحة: RejectionReasonRequiredException.php
 * الوظيفة: استثناء مجال يُرمى عند محاولة رفض طلب قبول دون ذكر سبب واضح للرفض.
 */

namespace Admissions\Domain\Exceptions;

use Admissions\Domain\ValueObjects\ApplicationId;

final class RejectionReasonRequiredException extends AdmissionsDomainException
{
    public static function forApplication(ApplicationId $applicationId): self
    {
        return new self("A non-empty rejection reason is required for application [{$applicationId->value}].");
    }

    public function errorCode(): string
    {
        return 'rejection_reason_required';
    }
}
```

#### `ApplicationNotFoundException.php`

- **المسار:** `Admissions/Domain/Exceptions/ApplicationNotFoundException.php`
- **اسم الصفحة:** `ApplicationNotFoundException.php`
- **الوظيفة:** استثناء يُرمى عند البحث عن طلب قبول بمعرّف غير موجود في النظام.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/ApplicationNotFoundException.php
 * اسم الصفحة: ApplicationNotFoundException.php
 * الوظيفة: استثناء يُرمى عند البحث عن طلب قبول بمعرّف غير موجود في النظام.
 */

namespace Admissions\Domain\Exceptions;

final class ApplicationNotFoundException extends AdmissionsDomainException
{
    public static function withId(string $applicationId): self
    {
        return new self("Admission application [{$applicationId}] was not found.");
    }

    public function errorCode(): string
    {
        return 'application_not_found';
    }
}
```

#### `ProgramNotFoundException.php`

- **المسار:** `Admissions/Domain/Exceptions/ProgramNotFoundException.php`
- **اسم الصفحة:** `ProgramNotFoundException.php`
- **الوظيفة:** استثناء يُرمى عند محاولة التقديم أو الاستعلام عن برنامج دراسي غير موجود.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Exceptions/ProgramNotFoundException.php
 * اسم الصفحة: ProgramNotFoundException.php
 * الوظيفة: استثناء يُرمى عند محاولة التقديم أو الاستعلام عن برنامج دراسي غير موجود.
 */

namespace Admissions\Domain\Exceptions;

final class ProgramNotFoundException extends AdmissionsDomainException
{
    public static function withId(string $programId): self
    {
        return new self("Academic program [{$programId}] was not found.");
    }

    public function errorCode(): string
    {
        return 'program_not_found';
    }
}
```

### ٦. واجهات المستودعات — `Admissions/Domain/Repositories/`

#### `AdmissionApplicationRepositoryInterface.php`

- **المسار:** `Admissions/Domain/Repositories/AdmissionApplicationRepositoryInterface.php`
- **اسم الصفحة:** `AdmissionApplicationRepositoryInterface.php`
- **الوظيفة:** واجهة مستودع المجال (Domain Repository Interface) تحدد عقد استرجاع وحفظ كيان طلب القبول دون الارتباط بـ Eloquent أو أي قاعدة بيانات معينة.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Repositories/AdmissionApplicationRepositoryInterface.php
 * اسم الصفحة: AdmissionApplicationRepositoryInterface.php
 * الوظيفة: واجهة مستودع المجال (Domain Repository Interface) تحدد عقد استرجاع وحفظ كيان طلب القبول دون الارتباط بـ Eloquent أو أي قاعدة بيانات معينة.
 */

namespace Admissions\Domain\Repositories;

use Admissions\Domain\Entities\AdmissionApplication;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use DateTimeImmutable;

interface AdmissionApplicationRepositoryInterface
{
    public function find(ApplicationId $id): ?AdmissionApplication;

    /** قراءة السجل مع قفل التحديث التشاؤمي (SELECT ... FOR UPDATE) لمنع حالات السباق. */
    public function findForUpdate(ApplicationId $id): ?AdmissionApplication;

    public function existsForStudentAndProgram(StudentId $studentId, ProgramId $programId): bool;

    public function save(AdmissionApplication $application): void;

    /** حذف المسودات المهجورة التي لم تُقدَّم قبل التاريخ المحدد وإرجاع عدد السجلات المحذوفة. */
    public function deleteDraftsOlderThan(DateTimeImmutable $cutoff): int;
}
```

#### `ProgramRepositoryInterface.php`

- **المسار:** `Admissions/Domain/Repositories/ProgramRepositoryInterface.php`
- **اسم الصفحة:** `ProgramRepositoryInterface.php`
- **الوظيفة:** واجهة مستودع المجال (Domain Repository Interface) تحدد عقد استرجاع وحفظ تجمّع البرنامج الدراسي مع دعم القفل التشاؤمي عند حجز أو تحرير المقاعد.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Domain/Repositories/ProgramRepositoryInterface.php
 * اسم الصفحة: ProgramRepositoryInterface.php
 * الوظيفة: واجهة مستودع المجال (Domain Repository Interface) تحدد عقد استرجاع وحفظ تجمّع البرنامج الدراسي مع دعم القفل التشاؤمي عند حجز أو تحرير المقاعد.
 */

namespace Admissions\Domain\Repositories;

use Admissions\Domain\Entities\Program;
use Admissions\Domain\ValueObjects\ProgramId;

interface ProgramRepositoryInterface
{
    public function find(ProgramId $id): ?Program;

    /** قراءة البرنامج مع قفل الصف (SELECT ... FOR UPDATE) لحماية عداد المقاعد من التعديل المتزامن. */
    public function findForUpdate(ProgramId $id): ?Program;

    public function save(Program $program): void;
}
```

### ٧. خدمات المجال — `Admissions/Domain/Services/`

#### `AdmissionDecisionDomainService.php`

- **المسار:** `Admissions/Domain/Services/AdmissionDecisionDomainService.php`
- **اسم الصفحة:** `AdmissionDecisionDomainService.php`
- **الوظيفة:** خدمة مجال (Domain Service) تنسّق قواعد العمل التي تعبر تجمّعين معاً (AdmissionApplication و Program) مثل اشتراط توفر مقعد قبل القبول، وتحرير المقعد المحجوز عند انسحاب طالب مقبول.

```php
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
```

---

## ثانياً: طبقة التطبيق — `Admissions/Application/`

طبقة التطبيق (Application Layer) تنسّق حالات الاستخدام (Use Cases) وتدير المعاملات ونشر الأحداث عبر منافذ مجردة (`Interfaces`) وكائنات نقل البيانات (`DTOs`). تعرف `Domain` فقط ولا تعرف `Infrastructure` ولا `Presentation`.

### ١. كائنات نقل البيانات — `Admissions/Application/DTOs/`

#### `SubmitApplicationDTO.php`

- **المسار:** `Admissions/Application/DTOs/SubmitApplicationDTO.php`
- **اسم الصفحة:** `SubmitApplicationDTO.php`
- **الوظيفة:** كائن نقل بيانات (DTO) يحمل مدخلات تقديم طلب قبول جديد من طبقة العرض إلى حالة الاستخدام SubmitApplicationUseCase دون تمرير كائن Request.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/DTOs/SubmitApplicationDTO.php
 * اسم الصفحة: SubmitApplicationDTO.php
 * الوظيفة: كائن نقل بيانات (DTO) يحمل مدخلات تقديم طلب قبول جديد من طبقة العرض إلى حالة الاستخدام SubmitApplicationUseCase دون تمرير كائن Request.
 */

namespace Admissions\Application\DTOs;

final readonly class SubmitApplicationDTO
{
    public function __construct(
        public string $studentId,
        public string $programId,
        public float $gpa,
    ) {
    }
}
```

#### `RejectApplicationDTO.php`

- **المسار:** `Admissions/Application/DTOs/RejectApplicationDTO.php`
- **اسم الصفحة:** `RejectApplicationDTO.php`
- **الوظيفة:** كائن نقل بيانات (DTO) يحمل معرّف الطلب وسبب الرفض من طبقة العرض إلى حالة الاستخدام RejectApplicationUseCase.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/DTOs/RejectApplicationDTO.php
 * اسم الصفحة: RejectApplicationDTO.php
 * الوظيفة: كائن نقل بيانات (DTO) يحمل معرّف الطلب وسبب الرفض من طبقة العرض إلى حالة الاستخدام RejectApplicationUseCase.
 */

namespace Admissions\Application\DTOs;

final readonly class RejectApplicationDTO
{
    public function __construct(
        public string $applicationId,
        public string $reason,
    ) {
    }
}
```

#### `ApplicationReadDTO.php`

- **المسار:** `Admissions/Application/DTOs/ApplicationReadDTO.php`
- **اسم الصفحة:** `ApplicationReadDTO.php`
- **الوظيفة:** كائن نقل بيانات للقراءة (Read DTO) يعيد بيانات طلب القبول من طبقة التطبيق إلى طبقة العرض دون تسريب كيان المجال أو نموذج Eloquent.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/DTOs/ApplicationReadDTO.php
 * اسم الصفحة: ApplicationReadDTO.php
 * الوظيفة: كائن نقل بيانات للقراءة (Read DTO) يعيد بيانات طلب القبول من طبقة التطبيق إلى طبقة العرض دون تسريب كيان المجال أو نموذج Eloquent.
 */

namespace Admissions\Application\DTOs;

use Admissions\Domain\Entities\AdmissionApplication;
use DateTimeInterface;

final readonly class ApplicationReadDTO
{
    public function __construct(
        public string $id,
        public string $studentId,
        public string $programId,
        public float $gpa,
        public string $status,
        public string $statusLabel,
        public ?string $decisionReason,
        public ?string $submittedAt,
        public ?string $decidedAt,
    ) {
    }

    public static function fromEntity(AdmissionApplication $application): self
    {
        return new self(
            id: $application->id()->value(),
            studentId: $application->studentId()->value(),
            programId: $application->programId()->value(),
            gpa: $application->gpa()->value(),
            status: $application->status()->value,
            statusLabel: $application->status()->label(),
            decisionReason: $application->decisionReason(),
            submittedAt: $application->submittedAt()?->format(DateTimeInterface::ATOM),
            decidedAt: $application->decidedAt()?->format(DateTimeInterface::ATOM),
        );
    }
}
```

### ٢. حالات الاستخدام — `Admissions/Application/UseCases/`

#### `SubmitApplicationUseCase.php`

- **المسار:** `Admissions/Application/UseCases/SubmitApplicationUseCase.php`
- **اسم الصفحة:** `SubmitApplicationUseCase.php`
- **الوظيفة:** حالة استخدام (Use Case) تنسّق سيناريو تقديم طلب قبول جديد: التحقق من وجود البرنامج وعدم تكرار التقديم، إنشاء الطلب وتقديمه، حفظه، ونشر حدث التقديم.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/SubmitApplicationUseCase.php
 * اسم الصفحة: SubmitApplicationUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تنسّق سيناريو تقديم طلب قبول جديد: التحقق من وجود البرنامج وعدم تكرار التقديم، إنشاء الطلب وتقديمه، حفظه، ونشر حدث التقديم.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Application\DTOs\SubmitApplicationDTO;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Interfaces\IdGeneratorInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Entities\AdmissionApplication;
use Admissions\Domain\Exceptions\AlreadyAppliedException;
use Admissions\Domain\Exceptions\ProgramNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;

final readonly class SubmitApplicationUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ProgramRepositoryInterface $programRepository,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
        private UnitOfWorkService $unitOfWork,
    ) {
    }

    public function execute(SubmitApplicationDTO $dto): ApplicationReadDTO
    {
        $studentId = StudentId::fromString($dto->studentId);
        $programId = ProgramId::fromString($dto->programId);
        $gpa = Gpa::fromFloat($dto->gpa);

        return $this->unitOfWork->execute(
            function () use ($studentId, $programId, $gpa): ApplicationReadDTO {
                $program = $this->programRepository->find($programId)
                    ?? throw ProgramNotFoundException::withId($programId->value());

                if ($this->applicationRepository->existsForStudentAndProgram($studentId, $programId)) {
                    throw AlreadyAppliedException::forStudentAndProgram(
                        $studentId->value(),
                        $programId->value(),
                    );
                }

                $application = AdmissionApplication::draft(
                    id: ApplicationId::fromString($this->idGenerator->nextUlid()),
                    studentId: $studentId,
                    programId: $programId,
                    gpa: $gpa,
                );

                $application->submit($program->minimumGpa(), $this->clock->now());

                $this->applicationRepository->save($application);
                $this->unitOfWork->collectEventsFrom($application);

                return ApplicationReadDTO::fromEntity($application);
            }
        );
    }
}
```

#### `AcceptApplicationUseCase.php`

- **المسار:** `Admissions/Application/UseCases/AcceptApplicationUseCase.php`
- **اسم الصفحة:** `AcceptApplicationUseCase.php`
- **الوظيفة:** حالة استخدام (Use Case) تنسّق سيناريو قبول طلب الطالب: قفل الطلب والبرنامج بترتيب ثابت، تفويض القرار وحجز المقعد لخدمة المجال، حفظ التغييرات، ونشر حدث القبول.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/AcceptApplicationUseCase.php
 * اسم الصفحة: AcceptApplicationUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تنسّق سيناريو قبول طلب الطالب: قفل الطلب والبرنامج بترتيب ثابت، تفويض القرار وحجز المقعد لخدمة المجال، حفظ التغييرات، ونشر حدث القبول.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Exceptions\ApplicationNotFoundException;
use Admissions\Domain\Exceptions\ProgramNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\Services\AdmissionDecisionDomainService;
use Admissions\Domain\ValueObjects\ApplicationId;

final readonly class AcceptApplicationUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ProgramRepositoryInterface $programRepository,
        private AdmissionDecisionDomainService $decisionService,
        private ClockInterface $clock,
        private UnitOfWorkService $unitOfWork,
    ) {
    }

    public function execute(string $applicationId): ApplicationReadDTO
    {
        $id = ApplicationId::fromString($applicationId);

        return $this->unitOfWork->execute(function () use ($id): ApplicationReadDTO {
            // قفل الطلب أولاً ثم البرنامج دائماً لمنع الـ Deadlock
            $application = $this->applicationRepository->findForUpdate($id)
                ?? throw ApplicationNotFoundException::withId($id->value());

            $program = $this->programRepository->findForUpdate($application->programId())
                ?? throw ProgramNotFoundException::withId($application->programId()->value());

            $this->decisionService->accept($application, $program, $this->clock->now());

            $this->applicationRepository->save($application);
            $this->programRepository->save($program);

            $this->unitOfWork->collectEventsFrom($application, $program);

            return ApplicationReadDTO::fromEntity($application);
        });
    }
}
```

#### `RejectApplicationUseCase.php`

- **المسار:** `Admissions/Application/UseCases/RejectApplicationUseCase.php`
- **اسم الصفحة:** `RejectApplicationUseCase.php`
- **الوظيفة:** حالة استخدام (Use Case) تنسّق سيناريو رفض طلب القبول مع إلزامية ذكر سبب الرفض وحفظ القرار ونشر حدث الرفض.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/RejectApplicationUseCase.php
 * اسم الصفحة: RejectApplicationUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تنسّق سيناريو رفض طلب القبول مع إلزامية ذكر سبب الرفض وحفظ القرار ونشر حدث الرفض.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Application\DTOs\RejectApplicationDTO;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Exceptions\ApplicationNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\ValueObjects\ApplicationId;

final readonly class RejectApplicationUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ClockInterface $clock,
        private UnitOfWorkService $unitOfWork,
    ) {
    }

    public function execute(RejectApplicationDTO $dto): ApplicationReadDTO
    {
        $id = ApplicationId::fromString($dto->applicationId);

        return $this->unitOfWork->execute(function () use ($id, $dto): ApplicationReadDTO {
            $application = $this->applicationRepository->findForUpdate($id)
                ?? throw ApplicationNotFoundException::withId($id->value());

            $application->reject($dto->reason, $this->clock->now());

            $this->applicationRepository->save($application);
            $this->unitOfWork->collectEventsFrom($application);

            return ApplicationReadDTO::fromEntity($application);
        });
    }
}
```

#### `WithdrawApplicationUseCase.php`

- **المسار:** `Admissions/Application/UseCases/WithdrawApplicationUseCase.php`
- **اسم الصفحة:** `WithdrawApplicationUseCase.php`
- **الوظيفة:** حالة استخدام (Use Case) تنسّق سيناريو انسحاب الطالب من الطلب، وتعيد المقعد المحجوز إلى البرنامج الدراسي إذا كان الطلب مقبولاً مسبقاً.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/WithdrawApplicationUseCase.php
 * اسم الصفحة: WithdrawApplicationUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تنسّق سيناريو انسحاب الطالب من الطلب، وتعيد المقعد المحجوز إلى البرنامج الدراسي إذا كان الطلب مقبولاً مسبقاً.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Exceptions\ApplicationNotFoundException;
use Admissions\Domain\Exceptions\ProgramNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\Services\AdmissionDecisionDomainService;
use Admissions\Domain\ValueObjects\ApplicationId;

final readonly class WithdrawApplicationUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ProgramRepositoryInterface $programRepository,
        private AdmissionDecisionDomainService $decisionService,
        private ClockInterface $clock,
        private UnitOfWorkService $unitOfWork,
    ) {
    }

    public function execute(string $applicationId): ApplicationReadDTO
    {
        $id = ApplicationId::fromString($applicationId);

        return $this->unitOfWork->execute(function () use ($id): ApplicationReadDTO {
            $application = $this->applicationRepository->findForUpdate($id)
                ?? throw ApplicationNotFoundException::withId($id->value());

            $program = $this->programRepository->findForUpdate($application->programId())
                ?? throw ProgramNotFoundException::withId($application->programId()->value());

            $this->decisionService->withdraw($application, $program, $this->clock->now());

            $this->applicationRepository->save($application);
            $this->programRepository->save($program);

            $this->unitOfWork->collectEventsFrom($application, $program);

            return ApplicationReadDTO::fromEntity($application);
        });
    }
}
```

#### `ExpireDraftApplicationsUseCase.php`

- **المسار:** `Admissions/Application/UseCases/ExpireDraftApplicationsUseCase.php`
- **اسم الصفحة:** `ExpireDraftApplicationsUseCase.php`
- **الوظيفة:** حالة استخدام (Use Case) تحذف مسودات طلبات القبول المتروكة لفترة تتجاوز عدد الأيام المحدد، وتُستدعى من أوامر الـ Console المجدولة.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/UseCases/ExpireDraftApplicationsUseCase.php
 * اسم الصفحة: ExpireDraftApplicationsUseCase.php
 * الوظيفة: حالة استخدام (Use Case) تحذف مسودات طلبات القبول المتروكة لفترة تتجاوز عدد الأيام المحدد، وتُستدعى من أوامر الـ Console المجدولة.
 */

namespace Admissions\Application\UseCases;

use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use InvalidArgumentException;

final readonly class ExpireDraftApplicationsUseCase
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
        private ClockInterface $clock,
    ) {
    }

    public function execute(int $olderThanDays = 30): int
    {
        if ($olderThanDays < 1) {
            throw new InvalidArgumentException('Days threshold must be at least 1.');
        }

        $cutoff = $this->clock->now()->modify("-{$olderThanDays} days");

        return $this->applicationRepository->deleteDraftsOlderThan($cutoff);
    }
}
```

### ٣. واجهات ومنافذ التطبيق — `Admissions/Application/Interfaces/`

#### `ClockInterface.php`

- **المسار:** `Admissions/Application/Interfaces/ClockInterface.php`
- **اسم الصفحة:** `ClockInterface.php`
- **الوظيفة:** منفذ (Port / Interface) للحصول على الوقت الحالي، يسمح بتثبيت الوقت أثناء الاختبارات بدلاً من الاعتماد المباشر على ساعة النظام.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Interfaces/ClockInterface.php
 * اسم الصفحة: ClockInterface.php
 * الوظيفة: منفذ (Port / Interface) للحصول على الوقت الحالي، يسمح بتثبيت الوقت أثناء الاختبارات بدلاً من الاعتماد المباشر على ساعة النظام.
 */

namespace Admissions\Application\Interfaces;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
```

#### `IdGeneratorInterface.php`

- **المسار:** `Admissions/Application/Interfaces/IdGeneratorInterface.php`
- **اسم الصفحة:** `IdGeneratorInterface.php`
- **الوظيفة:** منفذ (Port / Interface) لتوليد معرّفات فريدة بصيغة ULID للكيانات الجديدة دون ربط طبقة التطبيق بمكتبة Laravel Str مباشرة.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Interfaces/IdGeneratorInterface.php
 * اسم الصفحة: IdGeneratorInterface.php
 * الوظيفة: منفذ (Port / Interface) لتوليد معرّفات فريدة بصيغة ULID للكيانات الجديدة دون ربط طبقة التطبيق بمكتبة Laravel Str مباشرة.
 */

namespace Admissions\Application\Interfaces;

interface IdGeneratorInterface
{
    public function nextUlid(): string;
}
```

#### `TransactionManagerInterface.php`

- **المسار:** `Admissions/Application/Interfaces/TransactionManagerInterface.php`
- **اسم الصفحة:** `TransactionManagerInterface.php`
- **الوظيفة:** منفذ (Port / Interface) لإدارة معاملات قاعدة البيانات (Database Transactions) بشكل مجرد عن إطار العمل.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Interfaces/TransactionManagerInterface.php
 * اسم الصفحة: TransactionManagerInterface.php
 * الوظيفة: منفذ (Port / Interface) لإدارة معاملات قاعدة البيانات (Database Transactions) بشكل مجرد عن إطار العمل.
 */

namespace Admissions\Application\Interfaces;

interface TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public function transactional(callable $callback): mixed;
}
```

#### `DomainEventDispatcherInterface.php`

- **المسار:** `Admissions/Application/Interfaces/DomainEventDispatcherInterface.php`
- **اسم الصفحة:** `DomainEventDispatcherInterface.php`
- **الوظيفة:** منفذ (Port / Interface) لنشر أحداث المجال بعد نجاح المعاملة (Commit) إلى المستمعين الخارجيين.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Interfaces/DomainEventDispatcherInterface.php
 * اسم الصفحة: DomainEventDispatcherInterface.php
 * الوظيفة: منفذ (Port / Interface) لنشر أحداث المجال بعد نجاح المعاملة (Commit) إلى المستمعين الخارجيين.
 */

namespace Admissions\Application\Interfaces;

interface DomainEventDispatcherInterface
{
    public function dispatch(object $event): void;
}
```

#### `ApplicantNotifierInterface.php`

- **المسار:** `Admissions/Application/Interfaces/ApplicantNotifierInterface.php`
- **اسم الصفحة:** `ApplicantNotifierInterface.php`
- **الوظيفة:** منفذ (Port / Interface) لإرسال إشعارات القبول للطالب دون ربط طبقة التطبيق بتفاصيل البريد الإلكتروني أو خدمة الإشعارات في البنية التحتية.

```php
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
```

### ٤. خدمات التطبيق — `Admissions/Application/Services/`

#### `UnitOfWorkService.php`

- **المسار:** `Admissions/Application/Services/UnitOfWorkService.php`
- **اسم الصفحة:** `UnitOfWorkService.php`
- **الوظيفة:** خدمة تطبيق تدير وحدة العمل (Unit of Work): تنفّذ العمليات داخل معاملة قاعدة بيانات واحدة، تجمع أحداث المجال من الجذور (Aggregates)، وتنشرها فقط بعد نجاح الـ Commit.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Services/UnitOfWorkService.php
 * اسم الصفحة: UnitOfWorkService.php
 * الوظيفة: خدمة تطبيق تدير وحدة العمل (Unit of Work): تنفّذ العمليات داخل معاملة قاعدة بيانات واحدة، تجمع أحداث المجال من الجذور (Aggregates)، وتنشرها فقط بعد نجاح الـ Commit.
 */

namespace Admissions\Application\Services;

use Admissions\Application\Interfaces\DomainEventDispatcherInterface;
use Admissions\Application\Interfaces\TransactionManagerInterface;

final class UnitOfWorkService
{
    /** @var list<object> */
    private array $collectedEvents = [];

    public function __construct(
        private readonly TransactionManagerInterface $transactionManager,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function collectEventsFrom(object ...$aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            if (method_exists($aggregate, 'releaseEvents')) {
                /** @var list<object> $events */
                $events = $aggregate->releaseEvents();
                array_push($this->collectedEvents, ...$events);
            }
        }
    }

    /**
     * @template T
     *
     * @param  callable():T  $work
     * @return T
     */
    public function execute(callable $work): mixed
    {
        $this->collectedEvents = [];

        try {
            $result = $this->transactionManager->transactional(function () use ($work) {
                $this->collectedEvents = [];

                return $work();
            });
        } catch (\Throwable $exception) {
            $this->collectedEvents = [];

            throw $exception;
        }

        $eventsToDispatch = $this->collectedEvents;
        $this->collectedEvents = [];

        foreach ($eventsToDispatch as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $result;
    }
}
```

#### `ApplicationQueryService.php`

- **المسار:** `Admissions/Application/Services/ApplicationQueryService.php`
- **اسم الصفحة:** `ApplicationQueryService.php`
- **الوظيفة:** خدمة تطبيق مسؤولة عن استعلامات القراءة الخاصة بطلبات القبول وإرجاعها ككائنات ApplicationReadDTO لطبقة العرض.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Services/ApplicationQueryService.php
 * اسم الصفحة: ApplicationQueryService.php
 * الوظيفة: خدمة تطبيق مسؤولة عن استعلامات القراءة الخاصة بطلبات القبول وإرجاعها ككائنات ApplicationReadDTO لطبقة العرض.
 */

namespace Admissions\Application\Services;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Admissions\Domain\Exceptions\ApplicationNotFoundException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\ValueObjects\ApplicationId;

final readonly class ApplicationQueryService
{
    public function __construct(
        private AdmissionApplicationRepositoryInterface $applicationRepository,
    ) {
    }

    public function getById(string $applicationId): ApplicationReadDTO
    {
        $id = ApplicationId::fromString($applicationId);

        $application = $this->applicationRepository->find($id)
            ?? throw ApplicationNotFoundException::withId($id->value());

        return ApplicationReadDTO::fromEntity($application);
    }
}
```

---

## ثالثاً: طبقة البنية التحتية — `Admissions/Infrastructure/`

طبقة البنية التحتية (Infrastructure Layer) تقدّم التنفيذ الفعلي لمستودعات المجال ومنافذ التطبيق باستخدام Laravel وEloquent وقاعدة البيانات والبريد، وتربطها داخل حاوية الاعتماديات عبر `AdmissionsServiceProvider`.

### ١. نماذج Eloquent للتخزين — `Admissions/Infrastructure/Persistence/Eloquent/`

#### `ApplicationEloquentModel.php`

- **المسار:** `Admissions/Infrastructure/Persistence/Eloquent/ApplicationEloquentModel.php`
- **اسم الصفحة:** `ApplicationEloquentModel.php`
- **الوظيفة:** نموذج Eloquent للتخزين فقط (Anemic Persistence Model) يمثل سجل جدول admission_applications في قاعدة البيانات، بلا قواعد عمل وبلا أحداث مجال.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Persistence/Eloquent/ApplicationEloquentModel.php
 * اسم الصفحة: ApplicationEloquentModel.php
 * الوظيفة: نموذج Eloquent للتخزين فقط (Anemic Persistence Model) يمثل سجل جدول admission_applications في قاعدة البيانات، بلا قواعد عمل وبلا أحداث مجال.
 */

namespace Admissions\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $student_id
 * @property string $program_id
 * @property string|float $gpa
 * @property string $status
 * @property string|null $decision_reason
 * @property \Illuminate\Support\Carbon|string|null $submitted_at
 * @property \Illuminate\Support\Carbon|string|null $decided_at
 */
final class ApplicationEloquentModel extends Model
{
    protected $table = 'admission_applications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
        ];
    }
}
```

#### `ProgramEloquentModel.php`

- **المسار:** `Admissions/Infrastructure/Persistence/Eloquent/ProgramEloquentModel.php`
- **اسم الصفحة:** `ProgramEloquentModel.php`
- **الوظيفة:** نموذج Eloquent للتخزين فقط (Anemic Persistence Model) يمثل سجل جدول programs في قاعدة البيانات، ويُستخدم حصراً داخل المستودع.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Persistence/Eloquent/ProgramEloquentModel.php
 * اسم الصفحة: ProgramEloquentModel.php
 * الوظيفة: نموذج Eloquent للتخزين فقط (Anemic Persistence Model) يمثل سجل جدول programs في قاعدة البيانات، ويُستخدم حصراً داخل المستودع.
 */

namespace Admissions\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string|float $min_gpa
 * @property int $capacity
 * @property int $reserved_seats
 */
final class ProgramEloquentModel extends Model
{
    protected $table = 'programs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'reserved_seats' => 'integer',
        ];
    }
}
```

### ٢. تنفيذ المستودعات — `Admissions/Infrastructure/Persistence/Repositories/`

#### `EloquentAdmissionApplicationRepository.php`

- **المسار:** `Admissions/Infrastructure/Persistence/Repositories/EloquentAdmissionApplicationRepository.php`
- **اسم الصفحة:** `EloquentAdmissionApplicationRepository.php`
- **الوظيفة:** التنفيذ الفعلي لمستودع طلبات القبول باستخدام Eloquent؛ يحوّل بين كيان المجال AdmissionApplication وسجل قاعدة البيانات ApplicationEloquentModel، ويترجم أخطاء قيود الفهرس الفريد إلى استثناء مجال.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Persistence/Repositories/EloquentAdmissionApplicationRepository.php
 * اسم الصفحة: EloquentAdmissionApplicationRepository.php
 * الوظيفة: التنفيذ الفعلي لمستودع طلبات القبول باستخدام Eloquent؛ يحوّل بين كيان المجال AdmissionApplication وسجل قاعدة البيانات ApplicationEloquentModel، ويترجم أخطاء قيود الفهرس الفريد إلى استثناء مجال.
 */

namespace Admissions\Infrastructure\Persistence\Repositories;

use Admissions\Domain\Entities\AdmissionApplication;
use Admissions\Domain\Enums\ApplicationStatus;
use Admissions\Domain\Exceptions\AlreadyAppliedException;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\ValueObjects\ApplicationId;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Domain\ValueObjects\StudentId;
use Admissions\Infrastructure\Persistence\Eloquent\ApplicationEloquentModel;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class EloquentAdmissionApplicationRepository implements AdmissionApplicationRepositoryInterface
{
    public function find(ApplicationId $id): ?AdmissionApplication
    {
        $record = ApplicationEloquentModel::query()->find($id->value());

        return $this->toDomainEntity($record);
    }

    public function findForUpdate(ApplicationId $id): ?AdmissionApplication
    {
        $record = ApplicationEloquentModel::query()
            ->lockForUpdate()
            ->find($id->value());

        return $this->toDomainEntity($record);
    }

    public function existsForStudentAndProgram(StudentId $studentId, ProgramId $programId): bool
    {
        return ApplicationEloquentModel::query()
            ->where('student_id', $studentId->value())
            ->where('program_id', $programId->value())
            ->exists();
    }

    public function save(AdmissionApplication $application): void
    {
        $attributes = [
            'id' => $application->id()->value(),
            'student_id' => $application->studentId()->value(),
            'program_id' => $application->programId()->value(),
            'gpa' => number_format($application->gpa()->value(), 2, '.', ''),
            'status' => $application->status()->value,
            'decision_reason' => $application->decisionReason(),
            'submitted_at' => $application->submittedAt()?->format('Y-m-d H:i:s'),
            'decided_at' => $application->decidedAt()?->format('Y-m-d H:i:s'),
        ];

        $record = ApplicationEloquentModel::query()->find($application->id()->value());

        if ($record === null) {
            try {
                (new ApplicationEloquentModel())->forceFill($attributes)->save();
            } catch (UniqueConstraintViolationException) {
                throw AlreadyAppliedException::forStudentAndProgram(
                    $application->studentId()->value(),
                    $application->programId()->value(),
                );
            }

            return;
        }

        $record->forceFill($attributes)->save();
    }

    public function deleteDraftsOlderThan(DateTimeImmutable $cutoff): int
    {
        return (int) ApplicationEloquentModel::query()
            ->where('status', ApplicationStatus::Draft->value)
            ->where('created_at', '<', $cutoff->format('Y-m-d H:i:s'))
            ->delete();
    }

    private function toDomainEntity(?ApplicationEloquentModel $record): ?AdmissionApplication
    {
        if ($record === null) {
            return null;
        }

        return AdmissionApplication::reconstitute(
            id: ApplicationId::fromString((string) $record->id),
            studentId: StudentId::fromString((string) $record->student_id),
            programId: ProgramId::fromString((string) $record->program_id),
            gpa: Gpa::fromDatabase($record->gpa),
            status: ApplicationStatus::from((string) $record->status),
            decisionReason: $record->decision_reason,
            submittedAt: $this->toImmutableDateTime($record->submitted_at),
            decidedAt: $this->toImmutableDateTime($record->decided_at),
        );
    }

    private function toImmutableDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
```

#### `EloquentProgramRepository.php`

- **المسار:** `Admissions/Infrastructure/Persistence/Repositories/EloquentProgramRepository.php`
- **اسم الصفحة:** `EloquentProgramRepository.php`
- **الوظيفة:** التنفيذ الفعلي لمستودع البرامج الدراسية باستخدام Eloquent؛ يحوّل بين كيان المجال Program وسجل قاعدة البيانات ProgramEloquentModel مع دعم القفل التشاؤمي.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Persistence/Repositories/EloquentProgramRepository.php
 * اسم الصفحة: EloquentProgramRepository.php
 * الوظيفة: التنفيذ الفعلي لمستودع البرامج الدراسية باستخدام Eloquent؛ يحوّل بين كيان المجال Program وسجل قاعدة البيانات ProgramEloquentModel مع دعم القفل التشاؤمي.
 */

namespace Admissions\Infrastructure\Persistence\Repositories;

use Admissions\Domain\Entities\Program;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\ValueObjects\Gpa;
use Admissions\Domain\ValueObjects\ProgramId;
use Admissions\Infrastructure\Persistence\Eloquent\ProgramEloquentModel;

final readonly class EloquentProgramRepository implements ProgramRepositoryInterface
{
    public function find(ProgramId $id): ?Program
    {
        $record = ProgramEloquentModel::query()->find($id->value());

        return $this->toDomainEntity($record);
    }

    public function findForUpdate(ProgramId $id): ?Program
    {
        $record = ProgramEloquentModel::query()
            ->lockForUpdate()
            ->find($id->value());

        return $this->toDomainEntity($record);
    }

    public function save(Program $program): void
    {
        ProgramEloquentModel::query()->updateOrCreate(
            ['id' => $program->id()->value()],
            [
                'name' => $program->name(),
                'min_gpa' => number_format($program->minimumGpa()->value(), 2, '.', ''),
                'capacity' => $program->capacity(),
                'reserved_seats' => $program->reservedSeats(),
            ],
        );
    }

    private function toDomainEntity(?ProgramEloquentModel $record): ?Program
    {
        if ($record === null) {
            return null;
        }

        return Program::reconstitute(
            id: ProgramId::fromString((string) $record->id),
            name: (string) $record->name,
            minimumGpa: Gpa::fromDatabase($record->min_gpa),
            capacity: (int) $record->capacity,
            reservedSeats: (int) $record->reserved_seats,
        );
    }
}
```

### ٣. الأحداث والمستمعون — `Admissions/Infrastructure/Events/`

#### `LaravelDomainEventDispatcher.php`

- **المسار:** `Admissions/Infrastructure/Events/LaravelDomainEventDispatcher.php`
- **اسم الصفحة:** `LaravelDomainEventDispatcher.php`
- **الوظيفة:** محوّل (Adapter) ينفّذ واجهة DomainEventDispatcherInterface باستخدام موزّع أحداث Laravel لنشر أحداث المجال بعد اعتماد المعاملة.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Events/LaravelDomainEventDispatcher.php
 * اسم الصفحة: LaravelDomainEventDispatcher.php
 * الوظيفة: محوّل (Adapter) ينفّذ واجهة DomainEventDispatcherInterface باستخدام موزّع أحداث Laravel لنشر أحداث المجال بعد اعتماد المعاملة.
 */

namespace Admissions\Infrastructure\Events;

use Admissions\Application\Interfaces\DomainEventDispatcherInterface;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class LaravelDomainEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {
    }

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
```

#### `SendAcceptanceNotificationListener.php`

- **المسار:** `Admissions/Infrastructure/Events/SendAcceptanceNotificationListener.php`
- **اسم الصفحة:** `SendAcceptanceNotificationListener.php`
- **الوظيفة:** مستمع أحداث غير متزامن (Queued Listener) يلتقط حدث ApplicationAccepted عبر الطابور ويفوّض إرسال إشعار القبول إلى ApplicantNotifierInterface.

```php
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
```

### ٤. خدمات البنية التحتية — `Admissions/Infrastructure/Services/`

#### `SystemClockService.php`

- **المسار:** `Admissions/Infrastructure/Services/SystemClockService.php`
- **اسم الصفحة:** `SystemClockService.php`
- **الوظيفة:** محوّل (Adapter) ينفّذ واجهة ClockInterface بإرجاع الوقت الفعلي للنظام ككائن DateTimeImmutable.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Services/SystemClockService.php
 * اسم الصفحة: SystemClockService.php
 * الوظيفة: محوّل (Adapter) ينفّذ واجهة ClockInterface بإرجاع الوقت الفعلي للنظام ككائن DateTimeImmutable.
 */

namespace Admissions\Infrastructure\Services;

use Admissions\Application\Interfaces\ClockInterface;
use DateTimeImmutable;

final readonly class SystemClockService implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
```

#### `UlidGeneratorService.php`

- **المسار:** `Admissions/Infrastructure/Services/UlidGeneratorService.php`
- **اسم الصفحة:** `UlidGeneratorService.php`
- **الوظيفة:** محوّل (Adapter) ينفّذ واجهة IdGeneratorInterface لتوليد معرّفات ULID مرتبة زمنياً باستخدام مساعد Laravel Str.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Services/UlidGeneratorService.php
 * اسم الصفحة: UlidGeneratorService.php
 * الوظيفة: محوّل (Adapter) ينفّذ واجهة IdGeneratorInterface لتوليد معرّفات ULID مرتبة زمنياً باستخدام مساعد Laravel Str.
 */

namespace Admissions\Infrastructure\Services;

use Admissions\Application\Interfaces\IdGeneratorInterface;
use Illuminate\Support\Str;

final readonly class UlidGeneratorService implements IdGeneratorInterface
{
    public function nextUlid(): string
    {
        return (string) Str::ulid();
    }
}
```

#### `LaravelTransactionManager.php`

- **المسار:** `Admissions/Infrastructure/Services/LaravelTransactionManager.php`
- **اسم الصفحة:** `LaravelTransactionManager.php`
- **الوظيفة:** محوّل (Adapter) ينفّذ واجهة TransactionManagerInterface لإدارة معاملات قاعدة البيانات عبر DatabaseManager في Laravel مع دعم إعادة المحاولة عند التعارض.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Services/LaravelTransactionManager.php
 * اسم الصفحة: LaravelTransactionManager.php
 * الوظيفة: محوّل (Adapter) ينفّذ واجهة TransactionManagerInterface لإدارة معاملات قاعدة البيانات عبر DatabaseManager في Laravel مع دعم إعادة المحاولة عند التعارض.
 */

namespace Admissions\Infrastructure\Services;

use Admissions\Application\Interfaces\TransactionManagerInterface;
use Illuminate\Database\DatabaseManager;

final readonly class LaravelTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private DatabaseManager $database,
    ) {
    }

    public function transactional(callable $callback): mixed
    {
        return $this->database->connection()->transaction($callback, attempts: 3);
    }
}
```

#### `MailApplicantNotifierService.php`

- **المسار:** `Admissions/Infrastructure/Services/MailApplicantNotifierService.php`
- **اسم الصفحة:** `MailApplicantNotifierService.php`
- **الوظيفة:** محوّل بنية تحتية ينفّذ واجهة ApplicantNotifierInterface لإرسال بريد القبول للطالب مع ضمان عدم التكرار (Idempotency) عبر جدول sent_notifications.

```php
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
```

### ٥. مزوّدات الخدمة — `Admissions/Infrastructure/Providers/`

#### `AdmissionsServiceProvider.php`

- **المسار:** `Admissions/Infrastructure/Providers/AdmissionsServiceProvider.php`
- **اسم الصفحة:** `AdmissionsServiceProvider.php`
- **الوظيفة:** مزوّد خدمة Laravel (Service Provider) يربط واجهات المجال والتطبيق بتنفيذاتها في البنية التحتية داخل حاوية الاعتماديات (DI Container)، ويسجّل مستمعات الأحداث وأوامر الـ Console.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Providers/AdmissionsServiceProvider.php
 * اسم الصفحة: AdmissionsServiceProvider.php
 * الوظيفة: مزوّد خدمة Laravel (Service Provider) يربط واجهات المجال والتطبيق بتنفيذاتها في البنية التحتية داخل حاوية الاعتماديات (DI Container)، ويسجّل مستمعات الأحداث وأوامر الـ Console.
 */

namespace Admissions\Infrastructure\Providers;

use Admissions\Application\Interfaces\ApplicantNotifierInterface;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Interfaces\DomainEventDispatcherInterface;
use Admissions\Application\Interfaces\IdGeneratorInterface;
use Admissions\Application\Interfaces\TransactionManagerInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Events\ApplicationAccepted;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\Services\AdmissionDecisionDomainService;
use Admissions\Infrastructure\Events\LaravelDomainEventDispatcher;
use Admissions\Infrastructure\Events\SendAcceptanceNotificationListener;
use Admissions\Infrastructure\Persistence\Repositories\EloquentAdmissionApplicationRepository;
use Admissions\Infrastructure\Persistence\Repositories\EloquentProgramRepository;
use Admissions\Infrastructure\Services\LaravelTransactionManager;
use Admissions\Infrastructure\Services\MailApplicantNotifierService;
use Admissions\Infrastructure\Services\SystemClockService;
use Admissions\Infrastructure\Services\UlidGeneratorService;
use Admissions\Presentation\Console\ExpireDraftApplicationsCommand;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class AdmissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ربط مستودعات المجال بتنفيذات Eloquent
        $this->app->bind(
            AdmissionApplicationRepositoryInterface::class,
            EloquentAdmissionApplicationRepository::class,
        );

        $this->app->bind(
            ProgramRepositoryInterface::class,
            EloquentProgramRepository::class,
        );

        // ربط منافذ التطبيق (Application Interfaces) بخدمات البنية التحتية
        $this->app->bind(ClockInterface::class, SystemClockService::class);
        $this->app->bind(IdGeneratorInterface::class, UlidGeneratorService::class);
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->bind(DomainEventDispatcherInterface::class, LaravelDomainEventDispatcher::class);
        $this->app->bind(ApplicantNotifierInterface::class, MailApplicantNotifierService::class);

        // تسجيل خدمات المجال والتطبيق
        $this->app->singleton(AdmissionDecisionDomainService::class);
        $this->app->scoped(UnitOfWorkService::class);
    }

    public function boot(Dispatcher $events): void
    {
        // تسجيل صريح لمستمع الحدث لأن المجلد خارج app/Listeners
        $events->listen(
            ApplicationAccepted::class,
            SendAcceptanceNotificationListener::class,
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireDraftApplicationsCommand::class,
            ]);
        }
    }
}
```

---

## رابعاً: طبقة العرض — `Admissions/Presentation/`

طبقة العرض (Presentation Layer) تستقبل طلبات HTTP وأوامر الطرفية (Console)، تتحقق من المدخلات، تفوّض التنفيذ إلى طبقة التطبيق، وتُخرج الاستجابة عبر موارد JSON موحّدة.

### ١. المتحكمات — `Admissions/Presentation/Http/Controllers/`

#### `AdmissionApplicationController.php`

- **المسار:** `Admissions/Presentation/Http/Controllers/AdmissionApplicationController.php`
- **اسم الصفحة:** `AdmissionApplicationController.php`
- **الوظيفة:** متحكم HTTP (Controller) نحيف يستقبل طلبات الويب (عرض، تقديم، قبول، رفض، انسحاب)، يفوّض التنفيذ إلى حالات الاستخدام في طبقة التطبيق، ويعيد الاستجابة عبر AdmissionApplicationResource.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Controllers/AdmissionApplicationController.php
 * اسم الصفحة: AdmissionApplicationController.php
 * الوظيفة: متحكم HTTP (Controller) نحيف يستقبل طلبات الويب (عرض، تقديم، قبول، رفض، انسحاب)، يفوّض التنفيذ إلى حالات الاستخدام في طبقة التطبيق، ويعيد الاستجابة عبر AdmissionApplicationResource.
 */

namespace Admissions\Presentation\Http\Controllers;

use Admissions\Application\Services\ApplicationQueryService;
use Admissions\Application\UseCases\AcceptApplicationUseCase;
use Admissions\Application\UseCases\RejectApplicationUseCase;
use Admissions\Application\UseCases\SubmitApplicationUseCase;
use Admissions\Application\UseCases\WithdrawApplicationUseCase;
use Admissions\Presentation\Http\Requests\RejectApplicationRequest;
use Admissions\Presentation\Http\Requests\SubmitApplicationRequest;
use Admissions\Presentation\Http\Resources\AdmissionApplicationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final readonly class AdmissionApplicationController
{
    public function show(
        string $id,
        ApplicationQueryService $queryService,
    ): AdmissionApplicationResource {
        $dto = $queryService->getById($id);

        return new AdmissionApplicationResource($dto);
    }

    public function store(
        SubmitApplicationRequest $request,
        SubmitApplicationUseCase $useCase,
    ): JsonResponse {
        $dto = $useCase->execute($request->toDTO());

        return (new AdmissionApplicationResource($dto))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function accept(
        string $id,
        AcceptApplicationUseCase $useCase,
    ): AdmissionApplicationResource {
        Gate::authorize('admissions.decide');

        $dto = $useCase->execute($id);

        return new AdmissionApplicationResource($dto);
    }

    public function reject(
        string $id,
        RejectApplicationRequest $request,
        RejectApplicationUseCase $useCase,
    ): AdmissionApplicationResource {
        $dto = $useCase->execute($request->toDTO($id));

        return new AdmissionApplicationResource($dto);
    }

    public function withdraw(
        string $id,
        WithdrawApplicationUseCase $useCase,
    ): AdmissionApplicationResource {
        $dto = $useCase->execute($id);

        return new AdmissionApplicationResource($dto);
    }
}
```

### ٢. طلبات التحقق — `Admissions/Presentation/Http/Requests/`

#### `SubmitApplicationRequest.php`

- **المسار:** `Admissions/Presentation/Http/Requests/SubmitApplicationRequest.php`
- **اسم الصفحة:** `SubmitApplicationRequest.php`
- **الوظيفة:** طلب تحقق HTTP (FormRequest) يفحص شكل وصيغة مدخلات تقديم الطلب (program_id و gpa) ويحوّلها إلى كائن SubmitApplicationDTO.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Requests/SubmitApplicationRequest.php
 * اسم الصفحة: SubmitApplicationRequest.php
 * الوظيفة: طلب تحقق HTTP (FormRequest) يفحص شكل وصيغة مدخلات تقديم الطلب (program_id و gpa) ويحوّلها إلى كائن SubmitApplicationDTO.
 */

namespace Admissions\Presentation\Http\Requests;

use Admissions\Application\DTOs\SubmitApplicationDTO;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['required', 'string', 'size:26'],
            'gpa' => ['required', 'numeric', 'between:0,5'],
        ];
    }

    public function toDTO(): SubmitApplicationDTO
    {
        return new SubmitApplicationDTO(
            studentId: (string) $this->user()->getAuthIdentifier(),
            programId: (string) $this->string('program_id'),
            gpa: $this->float('gpa'),
        );
    }
}
```

#### `RejectApplicationRequest.php`

- **المسار:** `Admissions/Presentation/Http/Requests/RejectApplicationRequest.php`
- **اسم الصفحة:** `RejectApplicationRequest.php`
- **الوظيفة:** طلب تحقق HTTP (FormRequest) يتحقق من صلاحية موظف القبول ووجود سبب الرفض نصياً، ويحوّل البيانات إلى كائن RejectApplicationDTO.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Requests/RejectApplicationRequest.php
 * اسم الصفحة: RejectApplicationRequest.php
 * الوظيفة: طلب تحقق HTTP (FormRequest) يتحقق من صلاحية موظف القبول ووجود سبب الرفض نصياً، ويحوّل البيانات إلى كائن RejectApplicationDTO.
 */

namespace Admissions\Presentation\Http\Requests;

use Admissions\Application\DTOs\RejectApplicationDTO;
use Illuminate\Foundation\Http\FormRequest;

final class RejectApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admissions.decide') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function toDTO(string $applicationId): RejectApplicationDTO
    {
        return new RejectApplicationDTO(
            applicationId: $applicationId,
            reason: (string) $this->string('reason'),
        );
    }
}
```

### ٣. موارد الاستجابة — `Admissions/Presentation/Http/Resources/`

#### `AdmissionApplicationResource.php`

- **المسار:** `Admissions/Presentation/Http/Resources/AdmissionApplicationResource.php`
- **اسم الصفحة:** `AdmissionApplicationResource.php`
- **الوظيفة:** مورد عرض (API JsonResource) يحوّل كائن القراءة ApplicationReadDTO إلى استجابة JSON موحّدة لواجهة البرمجة (API).

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Resources/AdmissionApplicationResource.php
 * اسم الصفحة: AdmissionApplicationResource.php
 * الوظيفة: مورد عرض (API JsonResource) يحوّل كائن القراءة ApplicationReadDTO إلى استجابة JSON موحّدة لواجهة البرمجة (API).
 */

namespace Admissions\Presentation\Http\Resources;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read ApplicationReadDTO $resource
 */
final class AdmissionApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'student_id' => $this->resource->studentId,
            'program_id' => $this->resource->programId,
            'gpa' => $this->resource->gpa,
            'status' => [
                'code' => $this->resource->status,
                'label' => $this->resource->statusLabel,
            ],
            'decision_reason' => $this->resource->decisionReason,
            'submitted_at' => $this->resource->submittedAt,
            'decided_at' => $this->resource->decidedAt,
        ];
    }
}
```

### ٤. الوسطاء — `Admissions/Presentation/Http/Middleware/`

#### `EnsureAdmissionsWindowOpenMiddleware.php`

- **المسار:** `Admissions/Presentation/Http/Middleware/EnsureAdmissionsWindowOpenMiddleware.php`
- **اسم الصفحة:** `EnsureAdmissionsWindowOpenMiddleware.php`
- **الوظيفة:** وسيط HTTP (Middleware) يتحقق من أن فترة التقديم الجامعي مفتوحة حالياً قبل السماح بإنشاء أو تقديم طلبات قبول جديدة.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Middleware/EnsureAdmissionsWindowOpenMiddleware.php
 * اسم الصفحة: EnsureAdmissionsWindowOpenMiddleware.php
 * الوظيفة: وسيط HTTP (Middleware) يتحقق من أن فترة التقديم الجامعي مفتوحة حالياً قبل السماح بإنشاء أو تقديم طلبات قبول جديدة.
 */

namespace Admissions\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdmissionsWindowOpenMiddleware
{
    /**
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isWindowOpen = (bool) config('admissions.window_open', true);

        if (! $isWindowOpen) {
            return response()->json([
                'error' => [
                    'code' => 'admissions_window_closed',
                    'message' => 'فترة التقديم الجامعي مغلقة حالياً.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
```

#### `RenderAdmissionsExceptionMiddleware.php`

- **المسار:** `Admissions/Presentation/Http/Middleware/RenderAdmissionsExceptionMiddleware.php`
- **اسم الصفحة:** `RenderAdmissionsExceptionMiddleware.php`
- **الوظيفة:** وسيط HTTP (Middleware) يلتقط استثناءات المجال الحاملة لكود خطأ (HasErrorCode) ويترجمها إلى رموز حالة HTTP مناسبة (404، 409، 422) دون تلويث المجال بتفاصيل البروتوكول.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Middleware/RenderAdmissionsExceptionMiddleware.php
 * اسم الصفحة: RenderAdmissionsExceptionMiddleware.php
 * الوظيفة: وسيط HTTP (Middleware) يلتقط استثناءات المجال الحاملة لكود خطأ (HasErrorCode) ويترجمها إلى رموز حالة HTTP مناسبة (404، 409، 422) دون تلويث المجال بتفاصيل البروتوكول.
 */

namespace Admissions\Presentation\Http\Middleware;

use Admissions\Domain\Exceptions\HasErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RenderAdmissionsExceptionMiddleware
{
    /** @var array<string, int> */
    private const STATUS_MAP = [
        'invalid_gpa' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'gpa_below_minimum' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'rejection_reason_required' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'invalid_transition' => Response::HTTP_CONFLICT,
        'no_seats_available' => Response::HTTP_CONFLICT,
        'already_applied' => Response::HTTP_CONFLICT,
        'application_not_found' => Response::HTTP_NOT_FOUND,
        'program_not_found' => Response::HTTP_NOT_FOUND,
    ];

    /**
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (HasErrorCode $exception) {
            $status = self::STATUS_MAP[$exception->errorCode()] ?? Response::HTTP_INTERNAL_SERVER_ERROR;

            return response()->json([
                'error' => [
                    'code' => $exception->errorCode(),
                    'message' => $exception->getMessage(),
                ],
            ], $status);
        }
    }
}
```

### ٥. أوامر الطرفية — `Admissions/Presentation/Console/`

#### `ExpireDraftApplicationsCommand.php`

- **المسار:** `Admissions/Presentation/Console/ExpireDraftApplicationsCommand.php`
- **اسم الصفحة:** `ExpireDraftApplicationsCommand.php`
- **الوظيفة:** أمر طرفية (Artisan Console Command) يستدعي حالة الاستخدام ExpireDraftApplicationsUseCase لحذف مسودات طلبات القبول المتروكة منذ أكثر من عدد محدد من الأيام.

```php
<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Console/ExpireDraftApplicationsCommand.php
 * اسم الصفحة: ExpireDraftApplicationsCommand.php
 * الوظيفة: أمر طرفية (Artisan Console Command) يستدعي حالة الاستخدام ExpireDraftApplicationsUseCase لحذف مسودات طلبات القبول المتروكة منذ أكثر من عدد محدد من الأيام.
 */

namespace Admissions\Presentation\Console;

use Admissions\Application\UseCases\ExpireDraftApplicationsUseCase;
use Illuminate\Console\Command;

final class ExpireDraftApplicationsCommand extends Command
{
    protected $signature = 'admissions:expire-drafts {--days=30 : عدد الأيام قبل اعتبار المسودة منتهية}';

    protected $description = 'حذف مسودات طلبات القبول الجامعي المتروكة التي تجاوزت المهلة المحددة';

    public function handle(ExpireDraftApplicationsUseCase $useCase): int
    {
        $days = max(1, (int) $this->option('days'));

        $deletedCount = $useCase->execute($days);

        $this->info("تم حذف [{$deletedCount}] مسودة طلب قبول مضى عليها أكثر من {$days} يوماً.");

        return self::SUCCESS;
    }
}
```

---

## خامساً: ربط الموديول بالمشروع (`composer.json` والمسارات والترحيل)

#### `composer.json`

- **المسار:** `composer.json`
- **اسم الصفحة:** `composer.json`
- **الوظيفة:** تسجيل مساحة الأسماء `Admissions\` لترتبط بمجلد `Admissions/` عبر معيار التحميل التلقائي `PSR-4`.

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Admissions\\": "Admissions/"
        }
    }
}
```

#### `routes/api.php`

- **المسار:** `routes/api.php`
- **اسم الصفحة:** `api.php`
- **الوظيفة:** تعريف مسارات واجهة برمجة التطبيقات (API Routes) الخاصة بموديول القبول الجامعي وربطها بوسطاء التحقق ومعالجة استثناءات المجال ومتحكم الطلبات.

```php
<?php

declare(strict_types=1);

/**
 * المسار: routes/api.php
 * اسم الصفحة: api.php
 * الوظيفة: تعريف مسارات API لموديول القبول الجامعي وربطها بالـ Middleware والـ Controller.
 */

use Admissions\Presentation\Http\Controllers\AdmissionApplicationController;
use Admissions\Presentation\Http\Middleware\EnsureAdmissionsWindowOpenMiddleware;
use Admissions\Presentation\Http\Middleware\RenderAdmissionsExceptionMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admissions')
    ->middleware([
        'auth:sanctum',
        RenderAdmissionsExceptionMiddleware::class,
    ])
    ->group(function (): void {
        Route::get('/applications/{id}', [AdmissionApplicationController::class, 'show']);

        Route::post('/applications', [AdmissionApplicationController::class, 'store'])
            ->middleware([
                EnsureAdmissionsWindowOpenMiddleware::class,
                'throttle:10,1',
            ]);

        Route::post('/applications/{id}/accept', [AdmissionApplicationController::class, 'accept']);
        Route::post('/applications/{id}/reject', [AdmissionApplicationController::class, 'reject']);
        Route::post('/applications/{id}/withdraw', [AdmissionApplicationController::class, 'withdraw']);
    });
```

#### `2026_10_23_000001_create_admissions_tables.php`

- **المسار:** `database/migrations/2026_10_23_000001_create_admissions_tables.php`
- **اسم الصفحة:** `2026_10_23_000001_create_admissions_tables.php`
- **الوظيفة:** ملف ترحيل قاعدة البيانات (Migration) لإنشاء جداول `programs` و`admission_applications` و`sent_notifications` مع قيود التكامل والفهرس الفريد لمنع ازدواجية التقديم.

```php
<?php

declare(strict_types=1);

/**
 * المسار: database/migrations/2026_10_23_000001_create_admissions_tables.php
 * اسم الصفحة: 2026_10_23_000001_create_admissions_tables.php
 * الوظيفة: إنشاء جداول البرامج وطلبات القبول وسجل الإشعارات المرسلة مع قيود قاعدة البيانات.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->char('id', 26)->primary();
            $table->string('name', 120);
            $table->decimal('min_gpa', 4, 2);
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('reserved_seats')->default(0);
            $table->timestamps();
        });

        Schema::create('admission_applications', function (Blueprint $table): void {
            $table->char('id', 26)->primary();
            $table->string('student_id', 64);
            $table->char('program_id', 26);
            $table->decimal('gpa', 4, 2);
            $table->string('status', 20)->default('draft');
            $table->string('decision_reason', 500)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('programs');
            $table->unique(['student_id', 'program_id']);
            $table->index(['program_id', 'status']);
        });

        Schema::create('sent_notifications', function (Blueprint $table): void {
            $table->string('notification_key', 120)->primary();
            $table->timestamp('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
        Schema::dropIfExists('sent_notifications');
        Schema::dropIfExists('programs');
    }
};
```


---

## سادساً: شرح تفصيلي — كيف تنتقل البيانات؟ ورحلة الطلب من الصفر حتى النهاية

حتى لا يكون الكود مجرد ملفات متفرقة، هذا القسم يشرح **كيف تتحرك البيانات من طبقة إلى طبقة**، و**لماذا يتغير شكل البيانات عند كل حدود**، ثم يتتبع **رحلة طلب حقيقية من الصفر** خطوة بخطوة.

---

### ١. لماذا يتغيّر شكل البيانات أثناء رحلتها؟ (خريطة تحوّل البيانات)

في هذه المعمارية، البيانات لا تدخل كـ `Request` إلى قلب النظام ولا تخرج كـ `Eloquent Model` إلى المتصفح، بل تتحول عبر **٦ أشكال محددة**، لكل شكل وظيفة تحمي طبقة معيّنة:

```text
[1] JSON HTTP Request (بيانات خام من المتصفح/الجوال)
       │
       ▼  (Presentation/Http/Requests/SubmitApplicationRequest.php)
[2] SubmitApplicationDTO (بيانات نظيفة بأنواع PHP بسيطة: string, float)
       │
       ▼  (Application/UseCases/SubmitApplicationUseCase.php)
[3] Value Objects: StudentId, ProgramId, Gpa (قيم محمية بقواعد المجال)
       │
       ▼  (Domain/Entities/AdmissionApplication.php + Program.php)
[4] Domain Entity / Aggregate Root (الكيان الذكي الذي يملك القرار والأحداث)
       │
       ▼  (Infrastructure/Persistence/Repositories/EloquentAdmissionApplicationRepository.php)
[5] ApplicationEloquentModel (سجل تخزين خام يكتب في جدول قاعدة البيانات)
       │
       ▼  (بعد الحفظ والعودة عبر UseCase)
[6] ApplicationReadDTO ──► AdmissionApplicationResource ──► JSON Response
```

#### لماذا لا نختصر هذه التحويلات؟
1. **لماذا لا نمرر `$request` إلى الـ `UseCase`؟**
   لأن الـ `UseCase` قد يُستدعى من **HTTP Controller** أو من **أمر Console (`Artisan`)** أو من **Job في الطابور** أو من **اختبار آلي (Test)**. لو اعتمد على `Request`، لن يعمل إلا عبر الويب. لذلك نحوله إلى `DTO`.
2. **لماذا لا نمرر `float $gpa` مباشرة إلى الكيان بدلاً من `Gpa` Value Object؟**
   لأن `float` يقبل `-5` و`99` و`NAN`. أما كائن `Gpa` فمجرد وجوده في الذاكرة يعني أنه **حتماً رقم صحيح بين `0.0` و`5.0`**.
3. **لماذا نفصل `AdmissionApplication` (الكيان) عن `ApplicationEloquentModel` (نموذج Eloquent)؟**
   لأن `Eloquent Model` مربوط بقاعدة البيانات ويسمح لأي كود بتعديل أي عمود مباشرة (`$model->status = 'accepted'`). أما الكيان `AdmissionApplication` فخصائصه `private`، ولا يمكن تغيير حالته إلا عبر دوال تحمي القواعد مثل `submit()` و`accept()` و`reject()`.
4. **لماذا نرجع `ApplicationReadDTO` للـ Controller بدلاً من الكيان نفسه؟**
   لمنع طبقة العرض (`Controller` أو `Resource`) من استدعاء دوال تغيّر حالة الكيان بالخطأ أثناء تجهيز الـ JSON.

---

### ٢. الرحلة الأولى من الصفر: طالب يقدّم طلب قبول جديد (`Submit Application`)

تخيّل طالباً رقمه `"1001"` فتح تطبيق الجامعة وأرسل طلب تقديم على برنامج «علوم الحاسب» بمعدل `4.50`:

```http
POST /api/v1/admissions/applications
Authorization: Bearer <token>
Content-Type: application/json

{
    "program_id": "01J8Z9K2M3N4P5Q6R7S8T9V0W1",
    "gpa": 4.5
}
```

إليك ما يحدث بالترتيب الزمني الدقيق داخل كل ملف في الخارطة:

---

#### المحطة ١: حراس البوابة (`Presentation/Http/Middleware/`)
قبل أن يصل الطلب إلى الـ Controller، يمر عبر وسيطين (Middlewares):

1. **`RenderAdmissionsExceptionMiddleware.php`**:
   يفتح كتلة `try { return $next($request); } catch (HasErrorCode $e) { ... }` ليراقب الطريق كله؛ إذا رمى المجال أي استثناء لاحقاً، سيمسكه هنا ويحوله إلى JSON نظيف.
2. **`EnsureAdmissionsWindowOpenMiddleware.php`**:
   يسأل: هل باب القبول الجامعي مفتوح حالياً في الإعدادات (`config('admissions.window_open')`)؟
   - إذا كان مغلقاً: يوقف الطلب فوراً ويرجع `403 Forbidden` دون إزعاج طبقة التطبيق أو قاعدة البيانات.
   - إذا كان مفتوحاً: يسمح للطلب بالمرور.

---

#### المحطة ٢: فحص الشكل وتجهيز ظرف البيانات (`Presentation/Http/Requests/SubmitApplicationRequest.php`)
Laravel ينشئ كائناً من `SubmitApplicationRequest` تلقائياً قبل دخول دالة `store()` في الـ Controller:
1. ينفذ `authorize()`: يتأكد أن الطالب مسجّل دخول (`$this->user() !== null`).
2. ينفذ `rules()`: يتحقق من **شكل** البيانات فقط (هل `program_id` نص طوله 26 حرفاً؟ هل `gpa` رقم بين 0 و 5؟).
   > **انتبه:** هذا فحص شكلي أولي لمدخلات HTTP، وليس قاعدة العمل الخاصة بالحد الأدنى للبرنامج.
3. عندما ينجح الفحص، يستدعي الـ Controller دالة `$request->toDTO()` التي تصنع كائناً من **`SubmitApplicationDTO`** (`Application/DTOs/SubmitApplicationDTO.php`) يحتوي على ثلاث قيم نظيفة:
   - `studentId = "1001"`
   - `programId = "01J8Z9K2M3N4P5Q6R7S8T9V0W1"`
   - `gpa = 4.5`

---

#### المحطة ٣: الشرطي النحيف (`Presentation/Http/Controllers/AdmissionApplicationController.php`)
الـ Controller في دالة `store()` لا يكتب أي `if` ولا يتحدث مع قاعدة البيانات؛ كل ما يفعله سطران:
```php
$dto = $useCase->execute($request->toDTO());

return (new AdmissionApplicationResource($dto))
    ->response()
    ->setStatusCode(Response::HTTP_CREATED);
```
يسلّم الـ `SubmitApplicationDTO` إلى قائد العملية: **`SubmitApplicationUseCase`**.

---

#### المحطة ٤: تحويل البيانات الخام إلى كائنات قيمة (`Application/UseCases/SubmitApplicationUseCase.php`)
أول ما يفعله `SubmitApplicationUseCase::execute()` قبل فتح أي معاملة قاعدة بيانات هو تحويل نصوص وأرقام الـ DTO إلى **Value Objects** من طبقة المجال (`Domain/ValueObjects/`):
1. `StudentId::fromString($dto->studentId)` ◄── يتأكد أن معرّف الطالب ليس نصاً فارغاً.
2. `ProgramId::fromString($dto->programId)` ◄── يتأكد أن معرّف البرنامج يطابق صيغة ULID.
3. `Gpa::fromFloat($dto->gpa)` ◄── يتأكد أن المعدل رقم منتهٍ (`is_finite`) بين `0.0` و`5.0` ويقرّبه لمنزلتين عشريتين. لو كان خطأ لرمى `InvalidGpaException` فوراً قبل لمس قاعدة البيانات!

---

#### المحطة ٥: فتح وحدة العمل والمعاملة (`Application/Services/UnitOfWorkService.php`)
الـ `UseCase` يستدعي `$this->unitOfWork->execute(function () { ... })`.
ماذا يفعل `UnitOfWorkService`؟
- يطلب من `TransactionManagerInterface` (والذي ينفّذه `LaravelTransactionManager.php` في البنية التحتية) فتح **`DB::transaction()`**.
- أي شيء يحدث داخل هذه الدالة إما أن ينجح كله معاً (`COMMIT`) أو يُلغى كله معاً (`ROLLBACK`).

---

#### المحطة ٦: استرجاع البرنامج وفحص عدم التكرار عبر المستودعات (`Domain/Repositories/` ◄── `Infrastructure/Persistence/Repositories/`)
داخل المعاملة، يقوم `SubmitApplicationUseCase` بخطوتين عبر واجهات المستودعات:

1. **البحث عن البرنامج:**
   ينادي `$this->programRepository->find($programId)`.
   - الذي ينفذ هذا النداء فعلياً هو **`EloquentProgramRepository.php`** في طبقة `Infrastructure`.
   - يقرأ الصف من جدول `programs` عبر **`ProgramEloquentModel.php`**، ثم يحوّله بواسطة `Program::reconstitute(...)` إلى كيان مجال نقي **`Program`** (`Domain/Entities/Program.php`).
   - إذا رجع `null`، يرمي الـ `UseCase` استثناء `ProgramNotFoundException` (الذي يتحول إلى `404 Not Found`).

2. **فحص هل قدّم الطالب على هذا البرنامج من قبل:**
   ينادي `$this->applicationRepository->existsForStudentAndProgram($studentId, $programId)`.
   - إذا كان الطالب قد قدّم مسبقاً، يرمي `AlreadyAppliedException` (يتحول إلى `409 Conflict`).

---

#### المحطة ٧: لحظة الحقيقة داخل الكيان (`Domain/Entities/AdmissionApplication.php`)
الآن يصنع الـ `UseCase` طلب قبول جديداً في حالة مسودة (`Draft`)، ثم يأمره بالتقديم:
```php
$application = AdmissionApplication::draft(
    id: ApplicationId::fromString($this->idGenerator->nextUlid()),
    studentId: $studentId,
    programId: $programId,
    gpa: $gpa,
);

$application->submit($program->minimumGpa(), $this->clock->now());
```
انظر ماذا يحدث داخل `$application->submit($minimumGpa, $at)` في طبقة المجال:
1. **فحص آلة الحالة (`ApplicationStatus` Enum):**
   يسأل `ApplicationStatus::Draft->canTransitionTo(ApplicationStatus::Submitted)` ◄── النتيجة `true`.
2. **فحص قاعدة المعدل الأدنى (`Gpa::meets`):**
   يسأل `$this->gpa->meets($minimumGpa)`: هل معدل الطالب (`4.5`) أكبر من أو يساوي الحد الأدنى للبرنامج (مثلاً `4.0`)؟
   - لو كان معدل الطالب `3.2`، سيرمي الكيان بنفسه **`GpaBelowMinimumException`**!
3. **تغيير الحالة وتسجيل الحدث:**
   - يغيّر حالته الداخلية إلى `ApplicationStatus::Submitted`.
   - يحفظ وقت التقديم `$this->submittedAt = $at`.
   - يضع حدثاً جديداً **`new ApplicationSubmitted(...)`** داخل مصفوفته الخاصة `$this->domainEvents[]`.
   > **ملاحظة جوهرية:** الكيان **لم يرسل** الحدث إلى Laravel بعد! هو فقط سجّله في ذاكرته الداخلية لأن المعاملة لم تُعتمد (`Commit`) بعد.

---

#### المحطة ٨: الحفظ الفعلي في قاعدة البيانات (`Infrastructure/Persistence/Repositories/EloquentAdmissionApplicationRepository.php`)
ينادي الـ `UseCase`:
```php
$this->applicationRepository->save($application);
$this->unitOfWork->collectEventsFrom($application);
```
- يأخذ `EloquentAdmissionApplicationRepository::save()` القيم من كيان `AdmissionApplication` ويفرغها في `ApplicationEloquentModel` وينفذ `INSERT` في جدول `admission_applications`.
- حتى لو حدث سباق متزامن (طالب ضغط زر الإرسال مرتين في نفس الجزء من الثانية)، فإن قيد الفهرس الفريد في قاعدة البيانات `UNIQUE(student_id, program_id)` سيرمي `UniqueConstraintViolationException`، فيلتقطه المستودع ويترجمه إلى `AlreadyAppliedException`!
- ثم يسحب `UnitOfWorkService::collectEventsFrom($application)` حدث `ApplicationSubmitted` من الكيان ويحتفظ به مؤقتاً.

---

#### المحطة ٩: اعتماد المعاملة ونشر الأحداث والرد على الطالب
1. تنتهي دالة المعاملة بنجاح، فيقوم `LaravelTransactionManager` بعمل **`COMMIT`** في قاعدة البيانات.
2. **فقط بعد نجاح الـ Commit**، يدور `UnitOfWorkService` على الأحداث المجمّعة وينشرها عبر `LaravelDomainEventDispatcher`.
3. يُنشئ الـ `UseCase` كائن قراءة **`ApplicationReadDTO::fromEntity($application)`** ويرجعه للـ Controller.
4. يغلّف الـ Controller هذا الـ DTO داخل **`AdmissionApplicationResource`** (`Presentation/Http/Resources/AdmissionApplicationResource.php`) الذي يخرج JSON النهائي بحالة `201 Created`:

```json
{
    "data": {
        "id": "01J8Z9M0A1B2C3D4E5F6G7H8J9",
        "student_id": "1001",
        "program_id": "01J8Z9K2M3N4P5Q6R7S8T9V0W1",
        "gpa": 4.5,
        "status": {
            "code": "submitted",
            "label": "مُقدَّم"
        },
        "decision_reason": null,
        "submitted_at": "2026-10-23T09:30:00+00:00",
        "decided_at": null
    }
}
```

---

### ٣. الرحلة الثانية: موظف القبول يقبل الطلب (`Accept Application`) — كيف تتعاون الطبقات لحماية المقاعد؟

الآن جاء موظف القبول وضغط زر «قبول الطلب»:
```http
POST /api/v1/admissions/applications/01J8Z9M0A1B2C3D4E5F6G7H8J9/accept
```
هنا توجد **قاعدة عمل حرجة تعبر كيانين معاً (`AdmissionApplication` + `Program`)**:
> «لا يجوز قبول طلب إلا إذا كان في حالة `Submitted`، وكان في البرنامج الدراسي مقعد شاغر على الأقل، وعند القبول ينقص عدد المقاعد المتاحة بمقدار ١، ويُرسل إيميل للطالب».

شاهد كيف تتعاون الملفات خطوة بخطوة:

1. **فحص الصلاحية في الـ Controller (`AdmissionApplicationController::accept`):**
   ينفذ `Gate::authorize('admissions.decide')`. لو كان المتصل طالباً عادياً وليس موظف قبول، يرد Laravel فوراً بـ `403 Forbidden`.
2. **بدء المعاملة والقفل التشاؤمي (`AcceptApplicationUseCase.php`):**
   يدخل `UnitOfWorkService::execute()` ويفتح Transaction، ثم يستدعي:
   - `$application = $this->applicationRepository->findForUpdate($id)` ◄── ينفذ `SELECT ... FOR UPDATE` لقفل صف الطلب.
   - `$program = $this->programRepository->findForUpdate($application->programId())` ◄── ينفذ `SELECT ... FOR UPDATE` لقفل صف البرنامج.
   > **لماذا القفل وبنفس الترتيب؟** لو حاول موظفان قبول آخر طالبين في نفس اللحظة ولم يتبقَّ إلا مقعد واحد، سيقف الموظف الثاني عند `findForUpdate` منتظراً انتهاء معاملة الموظف الأول، فلا يمكن أبداً حجز مقعدين فوق السعة!
3. **تنسيق القرار في خدمة المجال (`Domain/Services/AdmissionDecisionDomainService.php`):**
   لماذا لم نضع حجز المقعد داخل `AdmissionApplication`؟ لأن `AdmissionApplication` تجمّع (Aggregate) مستقل عن `Program`. لذلك يستدعي الـ `UseCase` خدمة المجال `AdmissionDecisionDomainService::accept($application, $program, $now)`:
   - تتأكد أولاً أن الطلب يخص هذا البرنامج فعلاً.
   - تسأل الطلب `$application->assertCanBeAccepted()`: هل حالتك الحالية تسمح بالقبول؟ (لو كان `Draft` أو `Rejected` يفشل فوراً قبل لمس البرنامج).
   - تأمر البرنامج بحجز مقعد `$program->reserveSeat()`: البرنامج يفحص `availableSeats() >= 1`؛ فإذا كانت المقاعد ممتلئة يرمي `NoSeatsAvailableException`، وإلا يزيد `reservedSeats` بمقدار `1`.
   - تأمر الطلب بالقبول `$application->accept($at)`: يغير حالته إلى `Accepted` ويسجل حدث **`ApplicationAccepted`** داخلياً.
4. **حفظ التجمّعين معاً:**
   يحفظ الـ `UseCase` كلاً من `$application` و`$program` في قاعدة البيانات، ويجمع الأحداث في `UnitOfWorkService`.
5. **الـ Commit ثم إرسال الإيميل عبر الطابور (`Infrastructure/Events/` + `Infrastructure/Services/`):**
   - بمجرد نجاح الـ Commit في قاعدة البيانات، ينشر `UnitOfWorkService` حدث `ApplicationAccepted`.
   - يلتقطه المستمع **`SendAcceptanceNotificationListener.php`** (المسجّل في `AdmissionsServiceProvider` والذي يطبق `ShouldQueue` ليعمل في الخلفية).
   - يستدعي المستمع خدمة **`MailApplicantNotifierService.php`** التي تفحص جدول `sent_notifications` أولاً (لمنع إرسال الإيميل مرتين لو أُعيد تشغيل الـ Job)، ثم ترسل البريد للطالب وتسجل المفتاح `acceptance_mail:{id}`.

---

### ٤. ماذا يحدث لو فشل شرط في منتصف الطريق؟ (رحلة الخطأ)

لنفترض أن البرنامج سعته `50` مقعداً والمقاعد المحجوزة `50` (أي المتاح `0`)، وحاول الموظف قبول الطلب رقم 51:

1. يفتح `AcceptApplicationUseCase` المعاملة ويقفل الطلب والبرنامج.
2. ينادي `AdmissionDecisionDomainService::accept()`.
3. ينادي `$program->reserveSeat()` فيجد `availableSeats() < 1`.
4. يرمي `Program` استثناء **`NoSeatsAvailableException::forProgram('علوم الحاسب')`** الذي يحمل الكود `'no_seats_available'`.
5. يخرج الاستثناء من الدالة الممررة لـ `UnitOfWorkService`:
   - يقوم `LaravelTransactionManager` بعمل **`ROLLBACK`** تلقائياً لقاعدة البيانات وتُحرَّر الأقفال.
   - يفرّغ `UnitOfWorkService` مصفوفة الأحداث `$this->collectedEvents = []`، فلا يُنشر أي حدث ولا يُرسل أي إيميل كاذب.
6. يصعد الاستثناء حتى يصل إلى **`RenderAdmissionsExceptionMiddleware.php`** في طبقة العرض:
   - يقرأ `$exception->errorCode()` فيجده `'no_seats_available'`.
   - ينظر في جدول `STATUS_MAP` فيجد أنه يقابل `409 Conflict`.
   - يرجع استجابة JSON منظمة:
   ```json
   {
       "error": {
           "code": "no_seats_available",
           "message": "No seats available in program [علوم الحاسب]."
       }
   }
   ```

---

### ٥. جدول ملخص: من يتكلم مع من؟ (المرجع السريع)

| الخطوة | من المتحدث؟ | مع من يتحدث؟ | ماذا يمرر له؟ | ماذا يستلم منه؟ |
|---|---|---|---|---|
| ١ | المتصفح / الجوال | `Middleware` ثم `FormRequest` | JSON خام عبر HTTP | فحص نافذة القبول + فحص شكل الحقول |
| ٢ | `AdmissionApplicationController` | `SubmitApplicationRequest` | يطلب `$request->toDTO()` | كائن `SubmitApplicationDTO` |
| ٣ | `AdmissionApplicationController` | `SubmitApplicationUseCase` | يمرر `SubmitApplicationDTO` | يستلم `ApplicationReadDTO` |
| ٤ | `SubmitApplicationUseCase` | `ValueObjects` (`Gpa`, `ProgramId`, ...) | قيم أولية (`string`, `float`) | كائنات قيمة محمية |
| ٥ | `SubmitApplicationUseCase` | `UnitOfWorkService` | دالة العمل (`Closure`) | تنفيذ داخل Transaction + نشر الأحداث بعد الـ Commit |
| ٦ | `SubmitApplicationUseCase` | `ProgramRepositoryInterface` | `ProgramId` | كيان `Program` (أو `null`) |
| ٧ | `SubmitApplicationUseCase` | كيان `AdmissionApplication` | `submit(minimumGpa, now)` | تغيير الحالة إلى `Submitted` + تسجيل حدث `ApplicationSubmitted` |
| ٨ | `SubmitApplicationUseCase` | `AdmissionApplicationRepositoryInterface` | كيان `AdmissionApplication` | حفظ في قاعدة البيانات عبر `ApplicationEloquentModel` |
| ٩ | `UnitOfWorkService` | `DomainEventDispatcherInterface` | أحداث المجال (`ApplicationAccepted`, ...) | إشعار المستمعين (`SendAcceptanceNotificationListener`) |
| ١٠ | `AdmissionApplicationController` | `AdmissionApplicationResource` | `ApplicationReadDTO` | JSON النهائي للمستخدم |

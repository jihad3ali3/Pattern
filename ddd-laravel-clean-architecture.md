# DDD في Laravel — نسخة نظيفة مختصرة

> إعادة بناء مختصرة لمثال «القبول الجامعي» بهيكلية نظيفة: مجال نقي، طبقات واضحة، منافذ ومحولات، واختبارات.
>
> **بدون شرح مطوّل.** الكود هو الوثيقة. النسخة التفصيلية السابقة (٤٧٠٠ سطر) محفوظة في تاريخ Git عند الالتزام `85d4341` إن أردت الرجوع إليها.
>
> الصفحة الأصلية: [`ddd-laravel-step-by-step.html`](./ddd-laravel-step-by-step.html)

---

## القاعدة الواحدة

```text
Presentation → Application → Domain ← Infrastructure
```

- **Domain**: قواعد العمل فقط. لا Eloquent، لا HTTP، لا Facades.
- **Application**: سيناريو واحد لكل كلاس، يتحدث مع الخارج عبر منافذ.
- **Infrastructure**: تنفيذ المنافذ (Eloquent، ساعة، طابور).
- **Presentation**: استقبال، تفويض، استجابة.

```text
src/Admissions/
├── Domain/
│   ├── Exception/
│   ├── Model/            # AdmissionApplication, Program, Gpa, Status, Events
│   ├── Repository/       # واجهات فقط
│   └── Service/          # AdmissionDecisionService
├── Application/
│   ├── Port/             # Clock, TransactionManager, DomainEventDispatcher, IdentifierGenerator
│   ├── UseCase/          # Handler لكل سيناريو
│   └── UnitOfWork.php    # معاملة + نشر الأحداث
├── Infrastructure/
│   ├── Persistence/      # Records, Repositories
│   └── Provider/
└── Presentation/Http/    # Controller, Request, ExceptionMap
src/Notifications/
└── Infrastructure/Listener/  # OnApplicationAccepted
```

---

## ١. القيمة `Gpa`

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\InvalidGpa;

final readonly class Gpa
{
    private function __construct(private float $value)
    {
    }

    public static function fromFloat(float $value): self
    {
        if (! is_finite($value) || $value < 0.0 || $value > 5.0) {
            throw InvalidGpa::outsideRange($value);
        }

        return new self(round($value, 2));
    }

    public static function fromDatabase(mixed $value): self
    {
        if (! is_numeric($value)) {
            throw InvalidGpa::notNumeric($value);
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
}
```

`is_finite` ضرورية: `NAN < 0` و`NAN > 5` كلتاهما `false`، فتمر من أي تحقق يعتمد على المقارنات وحدها.

---

## ٢. الحالة والاستثناءات

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

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
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

use DomainException;

/** كل خطأ متوقع يحمل كوداً ثابتاً؛ طبقة العرض تحوّل الكود إلى HTTP. */
interface HasErrorCode
{
    public function errorCode(): string;
}

abstract class AdmissionsException extends DomainException implements HasErrorCode
{
}

final class InvalidGpa extends AdmissionsException
{
    public static function outsideRange(float $value): self
    {
        return new self("GPA must be between 0 and 5, got {$value}.");
    }

    public static function notNumeric(mixed $value): self
    {
        return new self('GPA must be numeric, got ' . get_debug_type($value) . '.');
    }

    public function errorCode(): string
    {
        return 'invalid_gpa';
    }
}

final class InvalidTransition extends AdmissionsException
{
    public static function between(ApplicationStatus $from, ApplicationStatus $to): self
    {
        return new self("Cannot move application from [{$from->value}] to [{$to->value}].");
    }

    public function errorCode(): string
    {
        return 'invalid_transition';
    }
}

final class GpaBelowMinimum extends AdmissionsException
{
    public static function for(float $actual, float $minimum): self
    {
        return new self("GPA [{$actual}] is below the minimum [{$minimum}].");
    }

    public function errorCode(): string
    {
        return 'gpa_below_minimum';
    }
}

final class RejectionReasonRequired extends AdmissionsException
{
    public static function for(ApplicationId $applicationId): self
    {
        return new self("A rejection reason is required for application [{$applicationId->value}].");
    }

    public function errorCode(): string
    {
        return 'rejection_reason_required';
    }
}

final class AlreadyApplied extends AdmissionsException
{
    public static function for(string $studentId, string $programId): self
    {
        return new self("Student [{$studentId}] already applied to program [{$programId}].");
    }

    public function errorCode(): string
    {
        return 'already_applied';
    }
}

final class NoSeatsAvailable extends AdmissionsException
{
    public static function for(string $program): self
    {
        return new self("No seats available in [{$program}].");
    }

    public function errorCode(): string
    {
        return 'no_seats_available';
    }
}
```

> كل صنف في ملفه. جمعتها هنا للاختصار فقط.

---

## ٣. المعرّفات

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use InvalidArgumentException;

final readonly class ApplicationId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid ApplicationId [{$value}].");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

`ProgramId` و`StudentId` بنفس الشكل (الأخير بلا فحص ULID لأنه يعكس مفتاح جدول المستخدمين).

---

## ٤. تجمّع `Program`

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\NoSeatsAvailable;
use LogicException;

final class Program
{
    private function __construct(
        private readonly ProgramId $id,
        private readonly string $name,
        private readonly Gpa $minimumGpa,
        private int $capacity,
        private int $reservedSeats,
    ) {
    }

    public static function open(ProgramId $id, string $name, Gpa $minimumGpa, int $capacity): self
    {
        if ($capacity < 1) {
            throw new LogicException('Capacity must be at least 1.');
        }

        return new self($id, $name, $minimumGpa, $capacity, 0);
    }

    public static function reconstitute(
        ProgramId $id,
        string $name,
        Gpa $minimumGpa,
        int $capacity,
        int $reservedSeats,
    ): self {
        if ($capacity < 1 || $reservedSeats < 0 || $reservedSeats > $capacity) {
            throw new LogicException('Corrupted program state.');
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
            throw NoSeatsAvailable::for($this->name);
        }

        $this->reservedSeats++;
    }

    public function releaseSeat(): void
    {
        if ($this->reservedSeats < 1) {
            throw new LogicException('No reserved seat to release.');
        }

        $this->reservedSeats--;
    }
}
```

`capacity` و`reservedSeats` منفصلان، بخلاف `seats` في المثال الأصلي: بدون السعة لا يمكن تحرير مقعد أو منع تجاوزها.

---

## ٥. تجمّع `AdmissionApplication`

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\GpaBelowMinimum;
use Admissions\Domain\Exception\InvalidTransition;
use Admissions\Domain\Exception\RejectionReasonRequired;
use Admissions\Domain\Model\Event\ApplicationAccepted;
use Admissions\Domain\Model\Event\ApplicationSubmitted;
use DateTimeImmutable;

final class AdmissionApplication
{
    /** @var list<object> */
    private array $events = [];

    private function __construct(
        private readonly ApplicationId $id,
        private readonly StudentId $studentId,
        private readonly ProgramId $programId,
        private readonly Gpa $gpa,
        private ApplicationStatus $status,
        private ?string $decisionReason,
    ) {
    }

    public static function draft(
        ApplicationId $id,
        StudentId $studentId,
        ProgramId $programId,
        Gpa $gpa,
    ): self {
        return new self($id, $studentId, $programId, $gpa, ApplicationStatus::Draft, null);
    }

    public static function reconstitute(
        ApplicationId $id,
        StudentId $studentId,
        ProgramId $programId,
        Gpa $gpa,
        ApplicationStatus $status,
        ?string $decisionReason,
    ): self {
        return new self($id, $studentId, $programId, $gpa, $status, $decisionReason);
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

    /** يتحقق من الأهلية والانتقال قبل أي تغيير. */
    public function submit(Gpa $minimumGpa, DateTimeImmutable $at): void
    {
        $this->assertTransition(ApplicationStatus::Submitted);

        if (! $this->gpa->meets($minimumGpa)) {
            throw GpaBelowMinimum::for($this->gpa->value(), $minimumGpa->value());
        }

        $this->status = ApplicationStatus::Submitted;
        $this->events[] = new ApplicationSubmitted($this->id, $this->studentId, $this->programId, $at);
    }

    /** لا يتحقق من المقاعد: خدمة المجال هي المسؤولة عن ذلك. */
    public function accept(DateTimeImmutable $at): void
    {
        $this->assertTransition(ApplicationStatus::Accepted);

        $this->status = ApplicationStatus::Accepted;
        $this->events[] = new ApplicationAccepted($this->id, $this->studentId, $this->programId, $at);
    }

    public function reject(string $reason): void
    {
        $this->assertTransition(ApplicationStatus::Rejected);

        $reason = trim($reason);

        if ($reason === '') {
            throw RejectionReasonRequired::for($this->id);
        }

        $this->status = ApplicationStatus::Rejected;
        $this->decisionReason = $reason;
    }

    public function withdraw(): void
    {
        $this->assertTransition(ApplicationStatus::Withdrawn);

        $this->status = ApplicationStatus::Withdrawn;
    }

    public function assertCanBeAccepted(): void
    {
        $this->assertTransition(ApplicationStatus::Accepted);
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    private function assertTransition(ApplicationStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw InvalidTransition::between($this->status, $next);
        }
    }
}
```

---

## ٦. الأحداث

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model\Event;

use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
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

`ApplicationSubmitted` بنفس الشكل. الأحداث تُسجَّل داخل التجمّع ولا تُرسل منه.

---

## ٧. خدمة المجال

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Service;

use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationStatus;
use Admissions\Domain\Model\Program;
use DateTimeImmutable;
use LogicException;

final class AdmissionDecisionService
{
    public function accept(AdmissionApplication $application, Program $program, DateTimeImmutable $at): void
    {
        if (! $application->programId()->equals($program->id())) {
            throw new LogicException('Application and program do not match.');
        }

        // فشل سريع قبل حجز أي مقعد.
        $application->assertCanBeAccepted();

        $program->reserveSeat();
        $application->accept($at);
    }

    public function withdraw(AdmissionApplication $application, Program $program): void
    {
        $hadSeat = $application->status() === ApplicationStatus::Accepted;

        $application->withdraw();

        if ($hadSeat) {
            $program->releaseSeat();
        }
    }
}
```

هذه هي الطريقة الموثقة للثوابت التي تعبر تجمّعين: **خدمة مجال**. لا يمكن حماية «لا قبول بدون مقعد» داخل كيان واحد.

---

## ٨. مستودعات المجال

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Repository;

use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;

interface AdmissionApplicationRepository
{
    public function find(ApplicationId $id): ?AdmissionApplication;

    /** قراءة بقصد التعديل؛ التنفيذ يقرر القفل. */
    public function findForUpdate(ApplicationId $id): ?AdmissionApplication;

    public function existsFor(StudentId $student, ProgramId $program): bool;

    public function save(AdmissionApplication $application): void;
}
```

```php
interface ProgramRepository
{
    public function find(ProgramId $id): ?Program;

    public function findForUpdate(ProgramId $id): ?Program;

    public function save(Program $program): void;
}
```

---

## ٩. استثناءات التطبيق

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Exception;

use Admissions\Domain\Exception\HasErrorCode;
use RuntimeException;

abstract class ApplicationException extends RuntimeException implements HasErrorCode
{
}

final class ApplicationNotFound extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self("Application [{$id}] was not found.");
    }

    public function errorCode(): string
    {
        return 'application_not_found';
    }
}

final class ProgramNotFound extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self("Program [{$id}] was not found.");
    }

    public function errorCode(): string
    {
        return 'program_not_found';
    }
}
```

`ApplicationException` ليست خطأ مجال: «غير موجود» نتيجة استعلام لا انتهاك قاعدة. لكنها تحمل كوداً ليُوحَّد تحويلها في طبقة العرض.

---

## ١٠. منافذ التطبيق ووحدة العمل

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Port;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}

interface TransactionManager
{
    /**
     * @template T
     *
     * @param  callable():T  $work
     * @return T
     */
    public function transactional(callable $work): mixed;
}

interface DomainEventDispatcher
{
    public function dispatch(object $event): void;
}

interface IdentifierGenerator
{
    public function next(): string;
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application;

use Admissions\Application\Port\DomainEventDispatcher;
use Admissions\Application\Port\TransactionManager;

final class UnitOfWork
{
    /** @var list<object> */
    private array $events = [];

    public function __construct(
        private readonly TransactionManager $transactions,
        private readonly DomainEventDispatcher $dispatcher,
    ) {
    }

    public function collect(object ...$aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            array_push($this->events, ...$aggregate->releaseEvents());
        }
    }

    /**
     * @template T
     *
     * @param  callable():T  $work
     * @return T
     */
    public function run(callable $work): mixed
    {
        $result = $this->transactions->transactional($work);

        // لا نصل إلى هنا إلا بعد نجاح الـ commit.
        foreach ($this->events as $event) {
            $this->dispatcher->dispatch($event);
        }

        $this->events = [];

        return $result;
    }
}
```

سجّلها `scoped` لا `singleton`، وإلا تسرّبت أحداث طلب إلى طلب آخر داخل عامل الطابور.

---

## ١١. المعالجات

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Application\Exception\ProgramNotFound;
use Admissions\Application\Port\Clock;
use Admissions\Application\Port\IdentifierGenerator;
use Admissions\Application\UnitOfWork;
use Admissions\Domain\Exception\AlreadyApplied;
use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\Gpa;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Admissions\Domain\Repository\AdmissionApplicationRepository;
use Admissions\Domain\Repository\ProgramRepository;

final readonly class SubmitApplicationHandler
{
    public function __construct(
        private AdmissionApplicationRepository $applications,
        private ProgramRepository $programs,
        private IdentifierGenerator $ids,
        private Clock $clock,
        private UnitOfWork $unitOfWork,
    ) {
    }

    public function __invoke(StudentId $studentId, ProgramId $programId, float $gpa): string
    {
        return $this->unitOfWork->run(function () use ($studentId, $programId, $gpa): string {
            $program = $this->programs->find($programId)
                ?? throw ProgramNotFound::withId($programId->value);

            if ($this->applications->existsFor($studentId, $programId)) {
                throw AlreadyApplied::for($studentId->value, $programId->value);
            }

            $application = AdmissionApplication::draft(
                new ApplicationId($this->ids->next()),
                $studentId,
                $programId,
                Gpa::fromFloat($gpa),
            );

            $application->submit($program->minimumGpa(), $this->clock->now());

            $this->applications->save($application);
            $this->unitOfWork->collect($application);

            return $application->id()->value;
        });
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Application\Exception\ApplicationNotFound;
use Admissions\Application\Exception\ProgramNotFound;
use Admissions\Application\Port\Clock;
use Admissions\Application\UnitOfWork;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Repository\AdmissionApplicationRepository;
use Admissions\Domain\Repository\ProgramRepository;
use Admissions\Domain\Service\AdmissionDecisionService;

final readonly class AcceptApplicationHandler
{
    public function __construct(
        private AdmissionApplicationRepository $applications,
        private ProgramRepository $programs,
        private AdmissionDecisionService $decisions,
        private Clock $clock,
        private UnitOfWork $unitOfWork,
    ) {
    }

    public function __invoke(ApplicationId $applicationId): void
    {
        $this->unitOfWork->run(function () use ($applicationId): void {
            // ترتيب الأقفال ثابت: الطلب ثم البرنامج (منع الجمود).
            $application = $this->applications->findForUpdate($applicationId)
                ?? throw ApplicationNotFound::withId($applicationId->value);

            $program = $this->programs->findForUpdate($application->programId())
                ?? throw ProgramNotFound::withId($application->programId()->value);

            $this->decisions->accept($application, $program, $this->clock->now());

            $this->applications->save($application);
            $this->programs->save($program);

            $this->unitOfWork->collect($application, $program);
        });
    }
}
```

`RejectApplicationHandler` و`WithdrawApplicationHandler` بنفس النمط: قفل، قرار، حفظ، جمع.

---

## ١٢. البنية التحتية

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/** سجل تخزين فقط: بلا قواعد عمل وبلا أحداث. */
final class ApplicationRecord extends Model
{
    protected $table = 'admission_applications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
```

`ProgramRecord` بنفس الشكل على جدول `programs`.

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Persistence;

use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ApplicationStatus;
use Admissions\Domain\Model\Gpa;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Admissions\Domain\Repository\AdmissionApplicationRepository;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class EloquentApplicationRepository implements AdmissionApplicationRepository
{
    public function find(ApplicationId $id): ?AdmissionApplication
    {
        return $this->map(ApplicationRecord::query()->find($id->value));
    }

    public function findForUpdate(ApplicationId $id): ?AdmissionApplication
    {
        return $this->map(ApplicationRecord::query()->lockForUpdate()->find($id->value));
    }

    public function existsFor(StudentId $student, ProgramId $program): bool
    {
        return ApplicationRecord::query()
            ->where('student_id', $student->value)
            ->where('program_id', $program->value)
            ->exists();
    }

    public function save(AdmissionApplication $application): void
    {
        $attributes = [
            'id' => $application->id()->value,
            'student_id' => $application->studentId()->value,
            'program_id' => $application->programId()->value,
            'gpa' => number_format($application->gpa()->value(), 2, '.', ''),
            'status' => $application->status()->value,
            'decision_reason' => $application->decisionReason(),
        ];

        $record = ApplicationRecord::query()->find($application->id()->value);

        if ($record === null) {
            try {
                (new ApplicationRecord())->forceFill($attributes)->save();
            } catch (UniqueConstraintViolationException) {
                // سباق بين الفحص والإدراج: القيد هو الحكم النهائي.
                throw AlreadyApplied::for(
                    $application->studentId()->value,
                    $application->programId()->value,
                );
            }

            return;
        }

        $record->forceFill($attributes)->save();
    }

    private function map(?ApplicationRecord $record): ?AdmissionApplication
    {
        if ($record === null) {
            return null;
        }

        return AdmissionApplication::reconstitute(
            new ApplicationId($record->id),
            new StudentId($record->student_id),
            new ProgramId($record->program_id),
            Gpa::fromDatabase($record->gpa),
            ApplicationStatus::from($record->status),
            $record->decision_reason,
        );
    }
}
```

المابّرات هنا دوال خاصة؛ إن كبرت، استخرجها إلى `Mapper` مستقل.

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Provider;

use Admissions\Application\Port\Clock;
use Admissions\Application\Port\DomainEventDispatcher;
use Admissions\Application\Port\IdentifierGenerator;
use Admissions\Application\Port\TransactionManager;
use Admissions\Application\UnitOfWork;
use Admissions\Domain\Repository\AdmissionApplicationRepository;
use Admissions\Domain\Repository\ProgramRepository;
use Admissions\Domain\Service\AdmissionDecisionService;
use Illuminate\Support\ServiceProvider;

final class AdmissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Clock::class, \Admissions\Infrastructure\Laravel\SystemClock::class);
        $this->app->bind(IdentifierGenerator::class, \Admissions\Infrastructure\Laravel\UlidGenerator::class);
        $this->app->bind(TransactionManager::class, \Admissions\Infrastructure\Laravel\LaravelTransactionManager::class);
        $this->app->bind(DomainEventDispatcher::class, \Admissions\Infrastructure\Laravel\LaravelEventDispatcher::class);

        $this->app->bind(ProgramRepository::class, \Admissions\Infrastructure\Persistence\EloquentProgramRepository::class);
        $this->app->bind(AdmissionApplicationRepository::class, \Admissions\Infrastructure\Persistence\EloquentApplicationRepository::class);

        $this->app->singleton(AdmissionDecisionService::class);
        $this->app->scoped(UnitOfWork::class);
    }
}
```

```php
// SystemClock
public function now(): DateTimeImmutable { return new DateTimeImmutable('now'); }

// UlidGenerator
public function next(): string { return (string) Str::ulid(); }

// LaravelTransactionManager
public function transactional(callable $work): mixed
{
    return $this->database->connection()->transaction($work, attempts: 3);
}

// LaravelEventDispatcher
public function dispatch(object $event): void { $this->events->dispatch($event); }
```

---

## ١٣. الإشعارات

```php
<?php

declare(strict_types=1);

namespace Notifications\Infrastructure\Listener;

use Admissions\Domain\Model\Event\ApplicationAccepted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final readonly class OnApplicationAccepted implements ShouldQueue
{
    public function handle(ApplicationAccepted $event): void
    {
        $key = 'accepted:' . $event->applicationId->value;

        if (DB::table('sent_notifications')->where('notification_key', $key)->exists()) {
            return;
        }

        $student = DB::table('users')->where('id', $event->studentId->value)->first();
        $program = DB::table('programs')->where('id', $event->programId->value)->value('name');

        if ($student === null || $program === null) {
            return;
        }

        Mail::to($student->email)->send(new AcceptanceMail($student->name, (string) $program));

        DB::table('sent_notifications')->insertOrIgnore([
            'notification_key' => $key,
            'sent_at' => now(),
        ]);
    }
}
```

```php
// NotificationsServiceProvider::boot()
public function boot(Dispatcher $events): void
{
    // تسجيل صريح: الاكتشاف التلقائي لا يعمل خارج app/ ويفشل بصمت.
    $events->listen(ApplicationAccepted::class, OnApplicationAccepted::class);
}
```

`AcceptanceMail` هو `Mailable` عادي (مُنشئ + `envelope()` + `content()`). الحدث يحمل معرّفات فقط، فيُسلسل بحجم صغير ولا يعتمد على Eloquent.

---

## ١٤. طبقة العرض

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http;

use Admissions\Presentation\Http\Controller\ApplicationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/applications', [ApplicationController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::post('/applications/{id}/accept', [ApplicationController::class, 'accept'])
        ->middleware('can:admissions.decide');

    Route::post('/applications/{id}/reject', [ApplicationController::class, 'reject'])
        ->middleware('can:admissions.decide');

    Route::post('/applications/{id}/withdraw', [ApplicationController::class, 'withdraw']);
});
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http\Controller;

use Admissions\Application\UseCase\AcceptApplicationHandler;
use Admissions\Application\UseCase\SubmitApplicationHandler;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApplicationController
{
    public function store(Request $request, SubmitApplicationHandler $handler): Response
    {
        $request->validate([
            'program_id' => ['required', 'string', 'size:26'],
            'gpa' => ['required', 'numeric', 'between:0,5'],
        ]);

        $id = $handler(
            new StudentId((string) $request->user()->getAuthIdentifier()),
            new ProgramId((string) $request->string('program_id')),
            $request->float('gpa'),
        );

        return response()->json(['id' => $id], Response::HTTP_CREATED);
    }

    public function accept(string $id, AcceptApplicationHandler $handler): Response
    {
        $handler(new ApplicationId($id));

        return response()->json(['status' => 'accepted']);
    }
}
```

في مشروع حقيقي: `FormRequest` و`Resource` بدل التحقق والاستجابة المباشرين، و`Gate::authorize` في كل مسار إداري.

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http;

/** جدول واحد: كود الخطأ → رمز HTTP. المجال لا يعرف HTTP. */
final class ExceptionMap
{
    private const MAP = [
        'invalid_gpa' => 422,
        'gpa_below_minimum' => 422,
        'rejection_reason_required' => 422,
        'invalid_transition' => 409,
        'no_seats_available' => 409,
        'already_applied' => 409,
        'application_not_found' => 404,
        'program_not_found' => 404,
    ];

    public static function status(string $code): int
    {
        return self::MAP[$code] ?? 500;
    }
}
```

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (\Admissions\Domain\Exception\HasErrorCode $e, $request) {
        $status = \Admissions\Presentation\Http\ExceptionMap::status($e->errorCode());

        return $request->expectsJson()
            ? response()->json(['error' => ['code' => $e->errorCode(), 'message' => $e->getMessage()]], $status)
            : back()->withErrors(['domain' => $e->getMessage()]);
    });
})
```

---

## ١٥. الترحيل

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->unsignedBigInteger('student_id');
            $table->char('program_id', 26);
            $table->decimal('gpa', 4, 2);
            $table->string('status', 20)->default('draft');
            $table->string('decision_reason', 500)->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('users');
            $table->foreign('program_id')->references('id')->on('programs');
            $table->unique(['student_id', 'program_id']);
            $table->index(['program_id', 'status']);
        });

        Schema::create('sent_notifications', function (Blueprint $table): void {
            $table->string('notification_key', 120)->primary();
            $table->timestamp('sent_at');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement('ALTER TABLE programs ADD CONSTRAINT chk_capacity CHECK (reserved_seats <= capacity)');
            DB::statement("ALTER TABLE admission_applications ADD CONSTRAINT chk_status CHECK (status IN ('draft','submitted','accepted','rejected','withdrawn'))");
        }
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

## ١٦. الاختبارات

```php
<?php

declare(strict_types=1);

use Admissions\Domain\Exception\InvalidGpa;
use Admissions\Domain\Model\Gpa;

it('accepts only values within the scale', function (float $value, bool $valid): void {
    $check = fn () => Gpa::fromFloat($value);

    $valid
        ? expect($check()->value())->toBe($value)
        : expect($check)->toThrow(InvalidGpa::class);
})->with([
    [0.0, true],
    [4.25, true],
    [5.0, true],
    [-0.01, false],
    [5.01, false],
    [NAN, false],
]);
```

```php
<?php

declare(strict_types=1);

use Admissions\Domain\Exception\NoSeatsAvailable;
use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ApplicationStatus;
use Admissions\Domain\Model\Gpa;
use Admissions\Domain\Model\Program;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Admissions\Domain\Service\AdmissionDecisionService;
use Illuminate\Support\Str;

/**
 * مساعد للاختبار: طلب مُقدَّم مربوط ببرنامج معيّن.
 */
function submittedApplicationFor(Program $program): AdmissionApplication
{
    $application = AdmissionApplication::draft(
        new ApplicationId((string) Str::ulid()),
        new StudentId('1001'),
        $program->id(),
        Gpa::fromFloat(4.5),
    );

    $application->submit($program->minimumGpa(), new DateTimeImmutable());

    return $application;
}

it('reserves a seat on acceptance', function (): void {
    $program = Program::open(new ProgramId((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 2);
    $application = submittedApplicationFor($program);

    (new AdmissionDecisionService())->accept($application, $program, new DateTimeImmutable());

    expect($application->status())->toBe(ApplicationStatus::Accepted)
        ->and($program->reservedSeats())->toBe(1);
});

it('does not touch the application when the program is full', function (): void {
    $program = Program::open(new ProgramId((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 1);

    $first = submittedApplicationFor($program);
    $second = submittedApplicationFor($program);

    $service = new AdmissionDecisionService();
    $service->accept($first, $program, new DateTimeImmutable());

    expect(fn () => $service->accept($second, $program, new DateTimeImmutable()))
        ->toThrow(NoSeatsAvailable::class);

    expect($second->status())->toBe(ApplicationStatus::Submitted)
        ->and($program->reservedSeats())->toBe(1);
});

it('releases the seat when an accepted student withdraws', function (): void {
    $program = Program::open(new ProgramId((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 1);
    $application = submittedApplicationFor($program);

    $service = new AdmissionDecisionService();
    $service->accept($application, $program, new DateTimeImmutable());
    $service->withdraw($application, $program);

    expect($application->status())->toBe(ApplicationStatus::Withdrawn)
        ->and($program->reservedSeats())->toBe(0);
});
```

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 409 when seats run out', function (): void {
    // ... تجهيز برنامج بسعة 1 وطلبان مُقدَّمان
    $this->actingAs($officer)
        ->postJson("/api/v1/applications/{$first->id()->value}/accept")
        ->assertOk();

    $this->actingAs($officer)
        ->postJson("/api/v1/applications/{$second->id()->value}/accept")
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'no_seats_available');
});

it('returns 403 when a student tries to accept', function (): void {
    $this->actingAs($student)
        ->postJson('/api/v1/applications/01J0000000000000000000000/accept')
        ->assertForbidden();
});
```

```php
// اختبار عمارة: يمنع الانحراف تدريجياً
arch('domain is framework-free')
    ->expect('Admissions\Domain')
    ->not->toUse(['Illuminate', 'App', 'Admissions\Infrastructure', 'Admissions\Presentation']);
```

---

## ١٧. ما تغيّر عن المثال الأصلي

| الأصلي | هنا | السبب |
|---|---|---|
| موديلات Eloquent داخل المجال | كيانات PHP + سجلات في البنية التحتية | اختبار بلا قاعدة بيانات |
| `accept()` بلا تحقق من المقعد | `AdmissionDecisionService` + `reserveSeat()` | الثابت يعبر تجمّعين |
| `seats` يتناقص | `capacity` + `reserved_seats` | تحرير المقعد ومنع التجاوز |
| `dispatch()` من الكيان | تسجيل ثم نشر بعد الـ commit | لا إشعار كاذب |
| اكتشاف Listeners تلقائياً | `Event::listen` صريح | لا فشل صامت |
| لا صلاحيات | `can:admissions.decide` + Gate | أمان فعلي |
| `DomainException → 422` للكل | خريطة 404/409/422/500 | دلالة صحيحة |
| `$guarded = []` + JSON مباشر | `forceFill` من المابّر فقط | لا تعبئة جماعية |

---

## ١٨. متى لا تستخدم هذه الهيكلية

- CRUD بسيط بلا قواعد متشابكة: عبء بلا مقابل.
- نموذج أولي تريد التحقق منه بسرعة.
- فريق صغير ومجال لا يتغيّر.
- عندما تبدأ: طبّقها على **Core Domain** فقط، واترك الباقي Eloquent + Actions.

الثمن الحقيقي: ملفات أكثر، مابّرات صريحة، ومنحنى تعلّم. المقابل: قواعد قابلة للاختبار في أجزاء من الثانية، وتغيير محدود الأثر.

---

## ١٩. التشغيل

```bash
composer dump-autoload
php artisan migrate
php artisan queue:work --tries=5
php artisan test
```

---

## ما لم يُغطَّ هنا

Outbox للأحداث، مفاتيح Idempotency على HTTP، سجل تدقيق، نموذج قراءة بفصل كامل. كلها إضافات تُبنى فوق هذه الحدود لا بداخلها.

# DDD في Laravel — إعادة بناء نظيفة: تطبيق مرجعي كامل

> **ماذا يقدم هذا الملف؟** إعادة بناء كاملة لمثال نظام القبول الجامعي من صفحة `ddd-laravel-step-by-step.html`، لكن بهيكلية نظيفة تصلح كمرجع: مجال نقي، طبقات معرّفة، منافذ ومحولات، أحداث موثوقة، تفويض، واختبارات.
>
> **الملفات المرتبطة:**
> - [`ddd-laravel-step-by-step.html`](./ddd-laravel-step-by-step.html) — النموذج الأصلي.
> - [`ddd-laravel-step-by-step-explained.md`](./ddd-laravel-step-by-step-explained.md) — الشرح التفصيلي والنقد.
> - [`ddd-laravel-13.md`](./ddd-laravel-13.md) — أساس DDD في لارافل ١٣.
>
> **الحالة:** تطبيق مرجعي تعليمي مكتمل. ليس حزمة جاهزة للنسخ، لكن كل ملف فيه قابل للتشغيل داخل مشروع Laravel 13 جديد بعد ترحيل الجداول.

---

## فهرس الملف

| القسم | المحتوى |
|---|---|
| [لماذا إعادة البناء](#لماذا-إعادة-البناء) | العيوب الاثنا عشر في النموذج الأصلي وما يقابلها |
| [١](#١-المبادئ-العشرة-التي-يحكمها-هذا-التصميم) | المبادئ العشرة |
| [٢](#٢-الطبقات-واتجاه-الاعتماد) | الطبقات واتجاه الاعتماد |
| [٣](#٣-شجرة-المجلدات) | شجرة المجلدات |
| [٤](#٤-خريطة-الملفات-ومسؤولياتها) | خريطة الملفات ومسؤولياتها |
| [٥](#٥-طبقة-المجال--القيم-والمعرّفات) | القيم والمعرّفات (`Gpa`, ULID, الحالات) |
| [٦](#٦-طبقة-المجال--الأخطاء) | أخطاء المجال بأكواد ثابتة |
| [٧](#٧-طبقة-المجال--الأحداث) | أحداث المجال |
| [٨](#٨-تجمّع-program) | تجمّع `Program` والسعة |
| [٩](#٩-تجمّع-admissionapplication) | تجمّع `AdmissionApplication` |
| [١٠](#١٠-خدمة-المجال-admissiondecisionservice) | خدمة القرار |
| [١١](#١١-مستودعات-المجال-عقود-فقط) | مستودعات المجال |
| [١٢](#١٢-طبقة-التطبيق--المنافذ) | منافذ التطبيق |
| [١٣](#١٣-طبقة-التطبيق--جامع-الأحداث-ووحدة-العمل) | جامع الأحداث ووحدة العمل |
| [١٤](#١٤-طبقة-التطبيق--استثناءات-التطبيق) | استثناءات التطبيق |
| [١٥](#١٥-طبقة-التطبيق--أوامر-ومعالجات) | الأوامر والمعالجات |
| [١٦](#١٦-طبقة-التطبيق--نموذج-القراءة) | نموذج القراءة |
| [١٧](#١٧-البنية-التحتية--سجلات-eloquent) | سجلات Eloquent |
| [١٨](#١٨-البنية-التحتية--المابّرات-والمستودعات) | المابّرات والمستودعات |
| [١٩](#١٩-البنية-التحتية--تنفيذ-المنافذ) | تنفيذ المنافذ والمزوّد |
| [٢٠](#٢٠-سياق-الإشعارات--منفذ-ومحول-واستخدام) | سياق الإشعارات |
| [٢١](#٢١-طبقة-العرض--الطلبات-والتحقق) | الطلبات والتحقق |
| [٢٢](#٢٢-طبقة-العرض--الموارد-والاستجابات) | الموارد |
| [٢٣](#٢٣-طبقة-العرض--المتحكمات) | المتحكمات |
| [٢٤](#٢٤-ربط-الأخطاء-بـ-http) | خريطة الأخطاء إلى HTTP |
| [٢٥](#٢٥-التركيب-المزوّدون-والمسارات-والإعداد) | التركيب والمسارات |
| [٢٦](#٢٦-قاعدة-البيانات) | الترحيلات والقيود |
| [٢٧](#٢٧-الاختبارات) | الاختبارات |
| [٢٨](#٢٨-كيف-تشغّل-هذا-البناء) | التشغيل |
| [٢٩](#٢٩-جدول-التحويل-من-النموذج-القديم-إلى-البناء-الجديد) | جدول التحويل |
| [٣٠](#٣٠-مقارنة-القرارات-النموذج-القديم-مقابل-البناء-النظيف) | مقارنة القرارات |
| [٣١](#٣١-الثمن-متى-يكون-هذا-البناء-مبالغة) | الثمن ومتى لا تستخدمه |
| [٣٢](#٣٢-التوسعات-ما-الذي-يُضاف-قبل-الإنتاج) | Outbox وIdempotency والتدقيق |
| [٣٣](#٣٣-قائمة-تحقق-قبل-الإنتاج) | قائمة التحقق |
| [٣٤](#٣٤-حدود-هذا-البناء-بصراحة) | الحدود |
| [٣٥](#٣٥-الخلاصة) | الخلاصة |

---

## لماذا إعادة البناء؟

النموذج الأصلي ممتاز كمدخل، لكن الشرح التفصيلي كشف عيوباً تمنعه من أن يكون مرجعاً معمارياً:

| # | العيب في النموذج الأصلي | النتيجة | ما يفعله هذا البناء |
|---|---|---|---|
| ١ | الموديلات Eloquent داخل المجال | المجال يعتمد على الإطار وقاعدة البيانات، ويصعب اختباره | كيانات PHP نقية + موديلات Eloquent في البنية التحتية فقط |
| ٢ | لا يوجد فصل بين التجمّع وصف الجدول | شكل الجدول يتسرّب إلى التصميم | Mappers صريحة بين الكيان والسجل |
| ٣ | القبول لا يتحقق من المقعد | يمكن قبول طلب دون حجز مقعد | خدمة مجال تنسّق التجمّعين + `SeatReservation` كدليل حجز |
| ٤ | `$guarded = []` | تعبئة جماعية خطيرة | `forceFill` داخل المستودع فقط، وقائمة صريحة للكتابة |
| ٥ | لا صلاحيات على القبول | أي مستخدم يقبل أي طلب | Gate للدور + فحص ملكية داخل حالة الاستخدام |
| ٦ | اكتشاف المستمعين خارج `app/` يفشل بصمت | لا إيميل ولا خطأ | تسجيل صريح `Event::listen` + اختبار عمارة |
| ٧ | `InvalidArgumentException` من `Gpa` لا يُلتقط | ٥٠٠ بدل ٤٢٢ | أساس `AdmissionsException` مع `errorCode` |
| ٨ | لا `HasFactory` | الاختبارات لا تعمل | سجلات Eloquent مع factories صريحة |
| ٩ | لا قيود قاعدة بيانات | تكرار وتعارضات | `unique` و`check` و`index` و`foreign` |
| ١٠ | لا Idempotency ولا Outbox | تكرار أو فقدان إشعارات | منافذ تسمح بإضافتهما، وملحق يوضح الطريقة |
| ١١ | لا اختبارات كافية | لا حماية من الانحراف | وحدة + تكامل + ميزة + عمارة |
| ١٢ | لا `strict_types` | تحويلات ضمنية | `declare(strict_types=1)` في كل ملف |

الهدف ليس «DDD نقي ١٠٠٪» — فهذا غير عملي في أغلب مشاريع لارافل — بل **هيكلية نظيفة بأقل قدر من السحر، وقابلة للاختبار، وقابلة للاستبدال**.

---

## ١. المبادئ العشرة التي يحكمها هذا التصميم

1. **الاعتماد في اتجاه واحد.** العرض → التطبيق → المجال. البنية التحتية تنفّذ منافذ يعرّفها الداخل، ولا يعرفها الداخل.
2. **المجال نقي.** لا Eloquent، لا HTTP، لا Facades، لا `app()`. PHP وSPL فقط (`DateTimeImmutable`, `DomainException`).
3. **الحدود صريحة.** كل عبور بين طبقتين يمر عبر واجهة أو كائن نقل معرّف.
4. **الحالة تتغيّر من الداخل.** لا setters؛ أفعال تجارية `submit()`، `accept()`، `withdraw()`.
5. **الوقت والهوية يُحقنان.** `Clock` و`IdGenerator` منافذ، فلا `now()` ولا `Str::ulid()` داخل المجال.
6. **الأحداث تُسجَّل ولا تُرسل.** التجمّع يسجّل، ووحدة العمل تنشر بعد نجاح المعاملة.
7. **التسجيل صريح.** لا اكتشاف سحري للمستمعين؛ سطر واحد يربط الحدث بالمستمع.
8. **الأخطاء تُصنَّف.** استثناءات مجال بأكواد ثابتة، وتحويل واحد إلى HTTP في طبقة العرض.
9. **الاختبار يقود الحدود.** وحدة بلا قاعدة بيانات، تكامل للمستودعات، ميزة للمسارات، وعمارة تمنع الانحراف.
10. **لا سحر.** كل شيء قابل للتتبّع بقفزة واحدة في الـ IDE، بلا conventions خفية.

---

## ٢. الطبقات واتجاه الاعتماد

```text
┌───────────────────────────────────────────────────────────────┐
│ Presentation — HTTP                                           │
│ Controllers · FormRequests · Resources · Gates                │
└───────────────────────────┬───────────────────────────────────┘
                            │ Command objects
┌───────────────────────────▼───────────────────────────────────┐
│ Application — Use Cases                                       │
│ Handlers · UnitOfWork · Ports · Read Models · Exceptions       │
└───────────────────────────┬───────────────────────────────────┘
                            │ Domain types
┌───────────────────────────▼───────────────────────────────────┐
│ Domain — Rules                                                │
│ Aggregates · Value Objects · Enums · Events · Repositories     │
└───────────────────────────▲───────────────────────────────────┘
                            │ implements ports / interfaces
┌───────────────────────────┴───────────────────────────────────┐
│ Infrastructure                                                │
│ Eloquent · Mappers · Repositories · Clock · Queue · Mail       │
└───────────────────────────────────────────────────────────────┘
```

ثلاث قواعد تحكم الأسهم:

- **لا سهم من المجال إلى الأعلى.** المجال لا يستورد `Illuminate\*` ولا `App\*`.
- **التطبيق يعرف المجال، ولا يعرف البنية التحتية.** يتحدث معها عبر منافذ.
- **العرض يعرف التطبيق، ولا يعرف التخزين.** لا استعلامات Eloquent في المتحكم.

---

## ٣. شجرة المجلدات

```text
src/
├── Admissions/                            # Bounded Context
│   ├── Domain/                            # القواعد فقط — بلا إطار
│   │   ├── Exception/                     # InvalidGpa, NoSeatsAvailable, ...
│   │   ├── Model/
│   │   │   ├── Event/                     # ApplicationSubmitted, Accepted, ...
│   │   │   ├── AdmissionApplication.php   # Aggregate Root
│   │   │   ├── Program.php                # Aggregate Root
│   │   │   ├── Gpa.php                    # Value Object
│   │   │   ├── SeatReservation.php        # دليل حجز
│   │   │   ├── AdmissionRequirements.php
│   │   │   ├── ApplicationStatus.php
│   │   │   ├── Identifier.php + ApplicationId + ProgramId + StudentId
│   │   │   ├── DomainEvent.php
│   │   │   ├── RecordsEvents.php
│   │   │   └── RecordsDomainEvents.php
│   │   ├── Repository/                    # AdmissionApplicationRepository, ProgramRepository
│   │   └── Service/                       # AdmissionDecisionService
│   ├── Application/                       # حالات الاستخدام
│   │   ├── Exception/                     # ApplicationNotFound, ProgramNotFound
│   │   ├── Port/                          # Clock, IdentifierGenerator, TransactionManager,
│   │   │                                  # DomainEventDispatcher, ApplicationQueries
│   │   ├── ReadModel/                     # ApplicationView
│   │   ├── UseCase/                       # Command + Handler لكل سيناريو
│   │   ├── DomainEventCollector.php
│   │   └── UnitOfWork.php
│   ├── Infrastructure/                    # المحولات
│   │   ├── Laravel/                       # SystemClock, UlidGenerator, ...
│   │   ├── Persistence/                   # Records, Mappers, Repositories, Queries
│   │   └── Provider/                      # AdmissionsServiceProvider
│   └── Presentation/
│       └── Http/
│           ├── Controller/                # ApplicationController, ProgramController
│           ├── Request/                   # FormRequests
│           ├── Resource/                  # ApplicationResource
│           ├── DomainExceptionStatus.php
│           └── DomainExceptionRenderer.php
└── Notifications/                         # Bounded Context مستقل
    ├── Application/
    │   ├── Port/                          # AdmissionsDirectory, AcceptanceMailer, SentNotifications
    │   ├── NotifyStudentOfAcceptance.php
    │   ├── NotificationOutcome.php
    │   └── StudentProfile.php
    └── Infrastructure/
        ├── Directory/                     # EloquentAdmissionsDirectory
        ├── Idempotency/                   # DatabaseSentNotifications
        ├── Listener/                      # OnApplicationAccepted
        ├── Mail/                          # AcceptanceMail, LaravelAcceptanceMailer
        └── Provider/                      # NotificationsServiceProvider
```

**لماذا `src/` وليس `app/`؟** `app/` يبقى لهيكل لارافل (المزوّدون، الموديلات المشتركة، الأوامر). `src/` يحمل حدود العمل. الفصل يجعل «ما هو تطبيق» و«ما هو مجال» واضحاً.

**لماذا `Admissions` أولاً ثم الطبقات؟** لأن الحدود (Bounded Context) هي أول قرار معماري، والطبقات قرار داخلي. عكس الترتيب ينتج `src/Domain/Admissions/...` ويجعل إضافة سياق ثانٍ أصعب.

---

## ٤. خريطة الملفات ومسؤولياتها

| الملف | الطبقة | المسؤولية | يعتمد على |
|---|---|---|---|
| `Domain/Model/Gpa.php` | مجال | قيمة المعدل وقواعدها | استثناءات المجال |
| `Domain/Model/Identifier.php` | مجال | أساس المعرّفات المكتوبة | استثناءات المجال |
| `Domain/Model/AdmissionApplication.php` | مجال | جذر تجمّع الطلب | قيم، حالات، أحداث |
| `Domain/Model/Program.php` | مجال | جذر تجمّع البرنامج والمقاعد | قيم، أحداث |
| `Domain/Service/AdmissionDecisionService.php` | مجال | تنسيق تجمّعين في قرار واحد | التجمّعان |
| `Domain/Repository/*` | مجال | عقود التخزين | أنواع المجال |
| `Application/UseCase/*` | تطبيق | سيناريو واحد لكل حالة استخدام | المجال + المنافذ |
| `Application/Port/*` | تطبيق | عقود الخارج (وقت، هوية، معاملة، أحداث) | لا شيء |
| `Application/UnitOfWork.php` | تطبيق | معاملة + إعادة محاولة + نشر بعد commit | المنافذ |
| `Application/ReadModel/*` | تطبيق | نموذج قراءة للعرض | لا شيء |
| `Infrastructure/Persistence/*` | بنية تحتية | Eloquent + Mappers + تنفيذ المستودعات | المجال + الإطار |
| `Infrastructure/Laravel/*` | بنية تحتية | تنفيذ المنافذ | الإطار |
| `Presentation/Http/*` | عرض | استقبال، تفويض، استجابة | التطبيق |

---

## ٥. طبقة المجال — القيم والمعرّفات

### ٥.١ `Identifier` — أساس المعرّفات المكتوبة

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\InvalidIdentifier;

/**
 * أساس لكل معرّف في المجال.
 * المعرّف كائن قيمة: يُقارن بقيمته، ولا يحمل سلوكاً تجارياً.
 */
abstract readonly class Identifier
{
    final protected function __construct(private string $value)
    {
    }

    final public static function fromString(string $value): static
    {
        return new static(static::normalize($value));
    }

    /**
     * كل معرّف يحدد صيغته المقبولة.
     */
    protected static function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            throw InvalidIdentifier::empty();
        }

        return $value;
    }

    final public function value(): string
    {
        return $this->value;
    }

    final public function equals(?Identifier $other): bool
    {
        return $other !== null
            && $this::class === $other::class
            && $this->value === $other->value;
    }

    final public function __toString(): string
    {
        return $this->value;
    }
}
```

**لماذا كل هذا لمعرّف؟**

- يمنع تمرير `string` عشوائي حيث يُتوقع معرّف.
- يمنع الخلط بين `ApplicationId` و`ProgramId` (المرور عبر `equals` يفحص الصنف).
- يجعل التوقيعات واضحة: `find(ApplicationId $id)` بدل `find(string $id)`.
- يجعل التحقق من الصيغة في مكان واحد.

### ٥.٢ المعرّفات الثلاثة

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\InvalidIdentifier;

final readonly class ApplicationId extends Identifier
{
    protected static function normalize(string $value): string
    {
        if (preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value) !== 1) {
            throw InvalidIdentifier::forFormat('ApplicationId', $value, 'ULID');
        }

        return $value;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\InvalidIdentifier;

final readonly class ProgramId extends Identifier
{
    protected static function normalize(string $value): string
    {
        if (preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value) !== 1) {
            throw InvalidIdentifier::forFormat('ProgramId', $value, 'ULID');
        }

        return $value;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

/**
 * معرّف الطالب يعكس معرّف جدول المستخدمين.
 * لا نفرض صيغة ULID هنا، لأن جدول users قد يستخدم مفاتيح رقمية.
 */
final readonly class StudentId extends Identifier
{
}
```

**لماذا ULID وليس رقم متزايد؟**

- الهوية تُمنح لحظة إنشاء الكائن، لا بعد الإدراج في قاعدة البيانات. هذا يسمح بتسجيل الأحداث قبل الحفظ.
- لا يكشف عدد الطلبات (بعكس `1, 2, 3`).
- قابل للفرز زمنياً، وقيمته نصية آمنة في الروابط.

**الثمن:** 26 حرفاً بدل 8 بايتات، وفهارس أكبر قليلاً. لمشروع قبول جامعي، لا يذكر.

### ٥.٣ `Gpa` — قيمة بلا هوية

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\InvalidGpa;

final readonly class Gpa
{
    public const MIN = 0.0;
    public const MAX = 5.0;

    private function __construct(private float $value)
    {
    }

    public static function fromFloat(float $value): self
    {
        if (! is_finite($value) || $value < self::MIN || $value > self::MAX) {
            throw InvalidGpa::outsideRange($value, self::MIN, self::MAX);
        }

        return new self(round($value, 2));
    }

    /**
     * للقراءة من قاعدة البيانات: decimal يُرجع نصاً.
     */
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

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.005;
    }

    public function __toString(): string
    {
        return number_format($this->value, 2, '.', '');
    }
}
```

**قرارات مقصودة:**

- `is_finite()` ترفض `NAN` و`INF` معاً. `NAN` تحديداً تمرّ من مقارنات `<` و`>`، وهي ثغرة شائعة في كائنات القيمة.
- `round($value, 2)` يجعل التمثيل متسقاً مع `decimal(4,2)` في قاعدة البيانات.
- `fromDatabase(mixed)` تستقبل ما يعيده PDO (نص أو float) وتتحقق منه، بدل `(float)` أعمى.
- لا `setters`. لتغيير المعدل، يُنشأ كائن جديد (وهو ما لا يحدث فعلياً: المعدل لقطة وقت التقديم).

### ٥.٤ `ApplicationStatus` — آلة حالات صغيرة

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

    public function isDecided(): bool
    {
        return $this === self::Accepted || $this === self::Rejected;
    }
}
```

**لماذا `Withdrawn` من `Submitted` و`Accepted`؟**

- من `Submitted`: الطالب يسحب قبل القرار. لا مقعد محجوز، فلا شيء يُحرَّر.
- من `Accepted`: الطالب ينسحب بعد القبول. يجب تحرير المقعد — وهذا ما تنسّقه خدمة المجال.
- `Rejected` و`Withdrawn` نهائيتان. إعادة التقديم تعني طلباً جديداً (أو `intake` جديد).

**ملاحظة:** `match` بلا `default` يرمي `UnhandledMatchError` إذا أُضيفت حالة ونسي المؤلف تعديلها. هذا **سلوك مرغوب**: فشل صاخب بدل `false` صامت.

### ٥.٥ `AdmissionRequirements` — لماذا لا نمرر `Program`؟

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

/**
 * الحد الأدنى الذي يحتاجه الطلب ليقرر أهليته.
 * وجوده يمنع تمرير تجمّع Program كاملاً إلى تجمّع AdmissionApplication.
 */
final readonly class AdmissionRequirements
{
    public function __construct(
        private ProgramId $programId,
        private string $programName,
        private Gpa $minimumGpa,
    ) {
    }

    public function programId(): ProgramId
    {
        return $this->programId;
    }

    public function programName(): string
    {
        return $this->programName;
    }

    public function minimumGpa(): Gpa
    {
        return $this->minimumGpa;
    }
}
```

في النموذج القديم كانت `submit(Program $program)` تستقبل تجمّعاً آخر. هذا يخلق اقتراناً ويجعل حدود الاتساق غامضة. هنا يمرّر التطبيق **أقل قدر من البيانات**، ويبقى `AdmissionApplication` مستقلاً.

### ٥.٦ `SeatReservation` — دليل الحجز

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use DateTimeImmutable;

/**
 * دليل على أن مقعداً حُجز فعلاً في برنامج معيّن.
 * لا يمكن إنشاؤه إلا عبر Program::reserveSeat().
 *
 * @internal
 */
final readonly class SeatReservation
{
    private function __construct(
        private ProgramId $programId,
        private DateTimeImmutable $reservedAt,
    ) {
    }

    /**
     * @internal لا يُستدعى من خارج تجمّع Program.
     */
    public static function issue(ProgramId $programId, DateTimeImmutable $at): self
    {
        return new self($programId, $at);
    }

    public function programId(): ProgramId
    {
        return $this->programId;
    }

    public function reservedAt(): DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function belongsTo(ProgramId $programId): bool
    {
        return $this->programId->equals($programId);
    }
}
```

هذا الكائن هو محاولة **تقنية** لحماية الثابت الذي يعبر تجمّعين: «لا قبول بدون مقعد». `AdmissionApplication::accept()` تشترط دليلاً، والدليل لا يُنشأ إلا من `Program::reserveSeat()`.

**كن صادقاً:** PHP لا تملك `friend classes`، فمن يريد التحايل يستطيع استدعاء `SeatReservation::issue()` مباشرة. الحماية الحقيقية هي:

1. `assertCanBeAccepted()` قبل الحجز (فشل سريع).
2. الدليل يمنع الخطأ العَرَضي لا التحايل المتعمّد.
3. اختبار عمارة/مراجعة تمنع استدعاء `accept()` خارج `AdmissionDecisionService`.
4. اختبار تكاملي يثبت أن كل قبول ينقص المقاعد.

### ٥.٧ `DomainEvent` وتسجيل الأحداث

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

/**
 * علامة على أن الكائن حدث مجال.
 * لا يرث أي شيء من الإطار، ولا يعرف الطابور ولا البريد.
 */
interface DomainEvent
{
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

interface RecordsEvents
{
    /**
     * @return list<DomainEvent>
     */
    public function releaseDomainEvents(): array;
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

/**
 * يجمع الأحداث داخل التجمّع، ولا يرسلها.
 * الإرسال مسؤولية وحدة العمل بعد نجاح المعاملة.
 */
trait RecordsDomainEvents
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    final protected function recordThat(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return list<DomainEvent>
     */
    final public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
```

**لماذا لا يُرسل التجمّع الحدث بنفسه؟**

- لو أرسله داخل المعاملة، قد يصل المستمع قبل الـ commit ثم تُراجع المعاملة → إشعار كاذب.
- لو أرسله بعد `save()` مباشرة، فقد يحدث خطأ قبل الـ commit.
- فصل التسجيل عن النشر يجعل التجمّع قابلاً للاختبار بلا أي بنية تحتية: تنادي الدالة، ثم تفحص `releaseDomainEvents()`.


---

## ٦. طبقة المجال — الأخطاء

### ٦.١ العقد: `HasErrorCode`

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

/**
 * كل خطأ متوقع في النظام يحمل كوداً ثابتاً.
 * طبقة العرض تحوّل الكود إلى HTTP، فلا يعرف المجال شيئاً عن 404 أو 422.
 */
interface HasErrorCode
{
    public function errorCode(): string;

    /**
     * @return array<string, mixed>
     */
    public function context(): array;
}
```

### ٦.٢ الأساس: `AdmissionsException`

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

use DomainException;

abstract class AdmissionsException extends DomainException implements HasErrorCode
{
    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
```

**لماذا `DomainException`؟** لأنها من SPL، ولأنها تصنّف الخطأ كـ«انتهاك قاعدة عمل» لا «خطأ برمجي». ولأن كل استثناءات المجال ترثها، يمكن التقاطها دفعة واحدة عند الحاجة.

### ٦.٣ بقية الاستثناءات

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

final class InvalidIdentifier extends AdmissionsException
{
    public static function empty(): self
    {
        return new self('Identifier cannot be empty.');
    }

    public static function forFormat(string $type, string $value, string $expected): self
    {
        return new self("Invalid {$type} [{$value}]: expected {$expected}.");
    }

    public function errorCode(): string
    {
        return 'invalid_identifier';
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

final class InvalidGpa extends AdmissionsException
{
    public static function outsideRange(float $value, float $min, float $max): self
    {
        return new self("GPA must be between {$min} and {$max}, got {$value}.");
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
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

use Admissions\Domain\Model\ApplicationStatus;

final class InvalidTransition extends AdmissionsException
{
    private function __construct(
        string $message,
        private readonly string $from,
        private readonly string $to,
    ) {
        parent::__construct($message);
    }

    public static function between(ApplicationStatus $from, ApplicationStatus $to): self
    {
        return new self(
            "Cannot move application from [{$from->value}] to [{$to->value}].",
            $from->value,
            $to->value,
        );
    }

    public function errorCode(): string
    {
        return 'invalid_transition';
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return ['from' => $this->from, 'to' => $this->to];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

use Admissions\Domain\Model\Gpa;

final class GpaBelowMinimum extends AdmissionsException
{
    private function __construct(
        string $message,
        private readonly string $program,
        private readonly float $actual,
        private readonly float $minimum,
    ) {
        parent::__construct($message);
    }

    public static function for(string $programName, Gpa $actual, Gpa $minimum): self
    {
        return new self(
            "GPA [{$actual}] is below the minimum [{$minimum}] required for [{$programName}].",
            $programName,
            $actual->value(),
            $minimum->value(),
        );
    }

    public function errorCode(): string
    {
        return 'gpa_below_minimum';
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'program' => $this->program,
            'actual' => $this->actual,
            'minimum' => $this->minimum,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

final class NoSeatsAvailable extends AdmissionsException
{
    public static function for(string $programName): self
    {
        return new self("No seats available in [{$programName}].");
    }

    public function errorCode(): string
    {
        return 'no_seats_available';
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

final class AlreadyApplied extends AdmissionsException
{
    public static function for(string $studentId, string $programId): self
    {
        return new self("Student [{$studentId}] already has an application for program [{$programId}].");
    }

    public function errorCode(): string
    {
        return 'already_applied';
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

use Admissions\Domain\Model\ApplicationId;

final class RejectionReasonRequired extends AdmissionsException
{
    public static function for(ApplicationId $applicationId): self
    {
        return new self("A rejection reason is required for application [{$applicationId->value()}].");
    }

    public function errorCode(): string
    {
        return 'rejection_reason_required';
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ProgramId;

final class ReservationMismatch extends AdmissionsException
{
    public static function for(ApplicationId $application, ProgramId $reserved, ProgramId $applied): self
    {
        return new self(
            "Seat reservation for program [{$reserved->value()}] cannot be used for "
            . "application [{$application->value()}] to program [{$applied->value()}].",
        );
    }

    public function errorCode(): string
    {
        return 'reservation_mismatch';
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Exception;

final class InvalidProgramCapacity extends AdmissionsException
{
    public static function mustBePositive(int $capacity): self
    {
        return new self("Program capacity must be at least 1, got {$capacity}.");
    }

    public static function belowReserved(int $capacity, int $reserved): self
    {
        return new self("Capacity [{$capacity}] cannot be below reserved seats [{$reserved}].");
    }

    public function errorCode(): string
    {
        return 'invalid_program_capacity';
    }
}
```

**ملاحظات على التصميم:**

- كل استثناء يحمل **رمزاً ثابتاً** (`errorCode`) لا يعتمد على نص الرسالة. العميل يستطيع الترجمة أو التصرف حسب الرمز.
- `context()` يعيد بيانات منظمة للسجل والمراقبة، بدل حشرها في نص.
- لا استثناء يحمل كائن تجمّع كامل (`Program` مثلاً)؛ فقط قيم بسيطة. هذا يمنع اقتران الاستثناء بالكيان.
- الأخطاء التي تعني «خلل برمجي» (مثل تمرير برنامج مختلف، أو تحرير مقعد غير محجوز) لا تُنمذج كاستثناء مجال؛ نستخدم `LogicException` لتظهر كـ ٥٠٠ وتُسجَّل.

---

## ٧. طبقة المجال — الأحداث

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model\Event;

use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\DomainEvent;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use DateTimeImmutable;

final readonly class ApplicationSubmitted implements DomainEvent
{
    public function __construct(
        public ApplicationId $applicationId,
        public ProgramId $programId,
        public StudentId $studentId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model\Event;

use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\DomainEvent;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use DateTimeImmutable;

final readonly class ApplicationAccepted implements DomainEvent
{
    public function __construct(
        public ApplicationId $applicationId,
        public ProgramId $programId,
        public StudentId $studentId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model\Event;

use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\DomainEvent;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use DateTimeImmutable;

final readonly class ApplicationRejected implements DomainEvent
{
    public function __construct(
        public ApplicationId $applicationId,
        public ProgramId $programId,
        public StudentId $studentId,
        public string $reason,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model\Event;

use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\DomainEvent;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use DateTimeImmutable;

final readonly class ApplicationWithdrawn implements DomainEvent
{
    public function __construct(
        public ApplicationId $applicationId,
        public ProgramId $programId,
        public StudentId $studentId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
```

**قرارات مقصودة في الأحداث:**

| القرار | السبب |
|---|---|
| كائنات immutable (`readonly`) | الحدث حقيقة تاريخية، لا يُعدَّل بعد وقوعه |
| تحمل معرّفات وقيمًا بسيطة، لا كائن التجمّع | لا تُسلسَل كائنات ضخمة إلى الطابور، ولا يتسرّب شكل الجدول |
| `occurredAt` ممرَّر من الخارج | المجال لا يقرأ الساعة بنفسه؛ الاختبارات حتمية |
| لا `eventId` | المفتاح الطبيعي (النوع + معرّف التجمّع + الحالة) كافٍ للتكرار المنطقي |
| لا `Dispatchable` ولا `SerializesModels` | الحدث لا يعرف الإطار؛ النقل مسؤولية البنية التحتية |

---

## ٨. تجمّع `Program`

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\InvalidProgramCapacity;
use Admissions\Domain\Exception\NoSeatsAvailable;
use DateTimeImmutable;
use LogicException;

/**
 * جذر تجمّع: يحمي ثابت السعة.
 * المقاعد المحجوزة لا تُعدَّل من خارج هذه الدوال.
 */
final class Program implements RecordsEvents
{
    use RecordsDomainEvents;

    private function __construct(
        private readonly ProgramId $id,
        private readonly string $name,
        private readonly Gpa $minGpa,
        private int $capacity,
        private int $reservedSeats,
    ) {
    }

    public static function open(ProgramId $id, string $name, Gpa $minGpa, int $capacity): self
    {
        if ($capacity < 1) {
            throw InvalidProgramCapacity::mustBePositive($capacity);
        }

        return new self($id, $name, $minGpa, $capacity, 0);
    }

    /**
     * يعيد بناء التجمّع من التخزين، ويتحقق من سلامة الحالة.
     */
    public static function reconstitute(
        ProgramId $id,
        string $name,
        Gpa $minGpa,
        int $capacity,
        int $reservedSeats,
    ): self {
        if ($capacity < 1 || $reservedSeats < 0 || $reservedSeats > $capacity) {
            throw new LogicException(
                "Corrupted program state: capacity [{$capacity}], reserved [{$reservedSeats}].",
            );
        }

        return new self($id, $name, $minGpa, $capacity, $reservedSeats);
    }

    public function id(): ProgramId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
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

    public function requirements(): AdmissionRequirements
    {
        return new AdmissionRequirements($this->id, $this->name, $this->minGpa);
    }

    /**
     * يحجز مقعداً ويعيد دليلاً على الحجز.
     * يجب أن يُستدعى داخل معاملة تقفل صف البرنامج.
     */
    public function reserveSeat(DateTimeImmutable $at): SeatReservation
    {
        if ($this->availableSeats() < 1) {
            throw NoSeatsAvailable::for($this->name);
        }

        $this->reservedSeats++;

        return SeatReservation::issue($this->id, $at);
    }

    /**
     * يحرر مقعداً بعد انسحاب.
     */
    public function releaseSeat(): void
    {
        if ($this->reservedSeats < 1) {
            throw new LogicException("Program [{$this->id->value()}] has no reserved seats to release.");
        }

        $this->reservedSeats--;
    }

    /**
     * تغيير السعة الإدارية، مع منع النزول تحت المحجوز.
     */
    public function changeCapacity(int $capacity): void
    {
        if ($capacity < $this->reservedSeats) {
            throw InvalidProgramCapacity::belowReserved($capacity, $this->reservedSeats);
        }

        $this->capacity = $capacity;
    }
}
```

**لماذا `capacity` و`reservedSeats` بدل `seats`؟**

في النموذج القديم كان `seats` يعني «المتاح»، فينقص عند القبول. المشكلة: عند الانسحاب لا تعرف السعة الأصلية، ولا يمكنك منع تجاوزها. الفصل بين السعة والمحجوز:

- `available = capacity - reserved`
- القبول: `reserved++`
- الانسحاب: `reserved--`
- قاعدة البيانات: `CHECK (reserved_seats BETWEEN 0 AND capacity)`

هذا يحوّل ثابتاً ضمنياً إلى ثابت صريح وقابل للفحص.

**ملاحظة على `releaseSeat()`:** يرمي `LogicException` عندما لا يوجد مقعد محجوز. هذا خلل برمجي (استدعاء خاطئ)، لا قاعدة عمل. ظهوره كـ ٥٠٠ ووجوده في السجلات هو المطلوب.

---

## ٩. تجمّع `AdmissionApplication`

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Model;

use Admissions\Domain\Exception\GpaBelowMinimum;
use Admissions\Domain\Exception\InvalidTransition;
use Admissions\Domain\Exception\RejectionReasonRequired;
use Admissions\Domain\Exception\ReservationMismatch;
use Admissions\Domain\Model\Event\ApplicationAccepted;
use Admissions\Domain\Model\Event\ApplicationRejected;
use Admissions\Domain\Model\Event\ApplicationSubmitted;
use Admissions\Domain\Model\Event\ApplicationWithdrawn;
use DateTimeImmutable;
use LogicException;

/**
 * جذر تجمّع الطلب.
 * كل تغيير على الحالة يمر من هنا، ولا يوجد setter للحالة.
 */
final class AdmissionApplication implements RecordsEvents
{
    use RecordsDomainEvents;

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
        if ($status === ApplicationStatus::Rejected
            && ($decisionReason === null || trim($decisionReason) === '')) {
            throw new LogicException('Corrupted application state: rejected without reason.');
        }

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

    public function isAccepted(): bool
    {
        return $this->status === ApplicationStatus::Accepted;
    }

    /**
     * تقديم الطلب: التحقق من الحالة، ثم من الأهلية، ثم الانتقال.
     */
    public function submit(AdmissionRequirements $requirements, DateTimeImmutable $at): void
    {
        $this->assertTransitionAllowed(ApplicationStatus::Submitted);

        if (! $this->gpa->meets($requirements->minimumGpa())) {
            throw GpaBelowMinimum::for(
                $requirements->programName(),
                $this->gpa,
                $requirements->minimumGpa(),
            );
        }

        $this->transitionTo(ApplicationStatus::Submitted);

        $this->recordThat(new ApplicationSubmitted(
            $this->id,
            $this->programId,
            $this->studentId,
            $at,
        ));
    }

    /**
     * فحص مسبق بلا تغيير، تستخدمه خدمة المجال قبل حجز المقعد.
     */
    public function assertCanBeAccepted(): void
    {
        $this->assertTransitionAllowed(ApplicationStatus::Accepted);
    }

    /**
     * القبول يتطلب دليلاً على حجز مقعد في نفس البرنامج.
     */
    public function accept(SeatReservation $reservation, DateTimeImmutable $at): void
    {
        if (! $reservation->belongsTo($this->programId)) {
            throw ReservationMismatch::for($this->id, $reservation->programId(), $this->programId);
        }

        $this->transitionTo(ApplicationStatus::Accepted);

        $this->recordThat(new ApplicationAccepted(
            $this->id,
            $this->programId,
            $this->studentId,
            $at,
        ));
    }

    public function reject(string $reason, DateTimeImmutable $at): void
    {
        $this->assertTransitionAllowed(ApplicationStatus::Rejected);

        $reason = trim($reason);

        if ($reason === '') {
            throw RejectionReasonRequired::for($this->id);
        }

        $this->transitionTo(ApplicationStatus::Rejected);
        $this->decisionReason = $reason;

        $this->recordThat(new ApplicationRejected(
            $this->id,
            $this->programId,
            $this->studentId,
            $reason,
            $at,
        ));
    }

    public function withdraw(DateTimeImmutable $at): void
    {
        $this->transitionTo(ApplicationStatus::Withdrawn);

        $this->recordThat(new ApplicationWithdrawn(
            $this->id,
            $this->programId,
            $this->studentId,
            $at,
        ));
    }

    private function assertTransitionAllowed(ApplicationStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw InvalidTransition::between($this->status, $next);
        }
    }

    private function transitionTo(ApplicationStatus $next): void
    {
        $this->assertTransitionAllowed($next);
        $this->status = $next;
    }
}
```

**ما الذي يجعله نظيفاً؟**

1. **لا setters.** لا يمكن كتابة `$application->status = ...`؛ الخاصية `private`.
2. **التحقق قبل التغيير.** `submit()` يفحص الحالة والأهلية قبل أي تعديل، فلا تُترك حالة نصف مكتملة في الذاكرة.
3. **الأحداث بعد التغيير فقط.** لا حدث إذا فشل أي شرط.
4. **`reconstitute` منفصلة عن `draft`.** البناء من التخزين لا يولّد أحداثاً، ويحمل تحققاً من سلامة البيانات.
5. **المنطق مرئي.** `submit()` تقرأ كقصة: تحقق، أهلية، انتقال، حدث.

**ملاحظة على `LogicException`:** تُستخدم هنا للأخطاء التي تعني «بيانات فاسدة» أو «استدعاء خاطئ»، لا لانتهاك قاعدة عمل. تظهر كـ ٥٠٠ وتُسجَّل، وهذا هو المطلوب: لا تُخفَ تحت ٤٢٢ كأنها خطأ مستخدم.

---

## ١٠. خدمة المجال: `AdmissionDecisionService`

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Service;

use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationStatus;
use Admissions\Domain\Model\Program;
use DateTimeImmutable;
use LogicException;

/**
 * تنسّق التجمّعين في عملية قرار واحدة.
 *
 * سبب وجودها: ثابت «لا قبول بدون مقعد» يعبر تجمّعين،
 * فلا يمكن أن يعيش داخل AdmissionApplication ولا داخل Program وحده.
 */
final class AdmissionDecisionService
{
    public function accept(
        AdmissionApplication $application,
        Program $program,
        DateTimeImmutable $at,
    ): void {
        if (! $application->programId()->equals($program->id())) {
            throw new LogicException('Application and program do not match.');
        }

        // فشل سريع قبل حجز أي مقعد.
        $application->assertCanBeAccepted();

        // الحجز أولاً: إن فشل، لا يتغيّر الطلب.
        $reservation = $program->reserveSeat($at);

        // القبول يشترط الدليل الناتج عن الحجز.
        $application->accept($reservation, $at);
    }

    public function reject(
        AdmissionApplication $application,
        string $reason,
        DateTimeImmutable $at,
    ): void {
        $application->reject($reason, $at);
    }

    public function withdraw(
        AdmissionApplication $application,
        Program $program,
        DateTimeImmutable $at,
    ): void {
        $holdsSeat = $application->status() === ApplicationStatus::Accepted;

        $application->withdraw($at);

        if ($holdsSeat) {
            $program->releaseSeat();
        }
    }
}
```

**ترتيب الخطوات في `accept()` مهم:**

1. التحقق من تطابق الطلب والبرنامج (خطأ برمجي إن اختلفا).
2. التحقق من قابلية الانتقال **قبل** الحجز. في النموذج القديم كان الحجز يحدث ثم يفشل القبول، فتُراجع المعاملة بلا داعٍ.
3. حجز المقعد (قد يرمي `NoSeatsAvailable`).
4. القبول بالدليل.

**لماذا خدمة مجال وليس طريقة في التجمّع؟**

- `AdmissionApplication::accept()` لا تستطيع حجز مقعد في `Program` (تجمّع آخر).
- `Program::reserveSeat()` لا تعرف حالة الطلب.
- خدمة المجال تجمع القاعدتين دون أن تخلق اعتماداً بين التجمّعين.

**الثمن:** الخدمة تعمل على كائنين محمّلين داخل معاملة. أي مسار آخر يقبل الطلبات يجب أن يمر بها. الحماية هنا **اتفاقية + اختبارات**، لا قيد لغوي.

---

## ١١. مستودعات المجال (عقود فقط)

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

    /**
     * قراءة بقصد التعديل. التنفيذ يقرر آلية القفل المناسبة.
     */
    public function findForUpdate(ApplicationId $id): ?AdmissionApplication;

    public function existsFor(StudentId $student, ProgramId $program): bool;

    public function save(AdmissionApplication $application): void;
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Domain\Repository;

use Admissions\Domain\Model\Program;
use Admissions\Domain\Model\ProgramId;

interface ProgramRepository
{
    public function find(ProgramId $id): ?Program;

    public function findForUpdate(ProgramId $id): ?Program;

    public function save(Program $program): void;
}
```

**لماذا `findForUpdate` في العقد؟**

قد يبدو تسريباً لتقنية القفل. لكن الاسم يعبّر عن **نية الاستخدام** («سأعدّل هذا الكائن»)، والتنفيذ يختار القفل التشاؤمي أو المتفائل. وجوده يجعل التزامن جزءاً من التصميم، لا تفصيلاً ينساه أحد.

**لماذا لا `findOrFail`؟** لأن الواجهة لا تعرف كيف تُترجم «غير موجود» إلى استثناء تطبيقي. إرجاع `null` يجعل حالة الاستخدام تقرر: `ApplicationNotFound` (٤٠٤) أو تجاهل.

**لماذا لا Repository عام (BaseRepository)؟** لأنه يفرض دوالاً لا تحتاجها التجمّعات (`all()`, `paginate()`) ويشجع على استعلامات من خارج المجال. واجهة صغيرة لكل تجمّع أوضح.


---

## ١٢. طبقة التطبيق — المنافذ

طبقة التطبيق لا تعرف **كيف** تُنفَّذ الأشياء، بل تعرف **أنها مطلوبة**. كل منفذ هنا واجهة صغيرة، وتنفيذها في البنية التحتية.

### ١٢.١ `Clock`

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Port;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
```

**لماذا؟** لأن `new DateTimeImmutable()` داخل المجال يجعل الاختبار غير حتمي. مع `Clock`، الاختبار يمرّر `FixedClock` ويعرف النتيجة مسبقاً.

### ١٢.٢ `IdentifierGenerator`

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Port;

interface IdentifierGenerator
{
    /**
     * يعيد معرّفاً فريداً بصيغة ULID (26 حرفاً).
     */
    public function next(): string;
}
```

**لماذا لا `Str::ulid()` في حالة الاستخدام؟** لأنها Facade من لارافل. حالة الاستخدام تبقى قابلة للتشغيل في أي بيئة PHP.

### ١٢.٣ `TransactionManager`

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Port;

interface TransactionManager
{
    /**
     * ينفّذ العمل داخل معاملة، ويعيد نتيجته.
     * يلتزم بإعادة المحاولة عند الجمود (deadlock) حسب التنفيذ.
     *
     * @template T
     *
     * @param  callable():T  $work
     * @return T
     */
    public function transactional(callable $work): mixed;
}
```

### ١٢.٤ `DomainEventDispatcher`

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Port;

use Admissions\Domain\Model\DomainEvent;

interface DomainEventDispatcher
{
    public function dispatch(DomainEvent $event): void;
}
```

**ملاحظة مهمة:** المنفذ ينشر حدثاً **واحداً**. من يقرر أن بعض الأحداث تُوضع في طابور، وكيف، هو التنفيذ. طبقة التطبيق لا تعرف `ShouldQueue`.

---

## ١٣. طبقة التطبيق — جامع الأحداث ووحدة العمل

### ١٣.١ `DomainEventCollector`

```php
<?php

declare(strict_types=1);

namespace Admissions\Application;

use Admissions\Domain\Model\DomainEvent;
use Admissions\Domain\Model\RecordsEvents;

/**
 * يجمع أحداث كل التجمّعات التي لمستها حالة الاستخدام.
 * يُسجَّل كـ scoped: نسخة واحدة لكل طلب/وظيفة.
 */
final class DomainEventCollector
{
    /** @var list<DomainEvent> */
    private array $events = [];

    public function collectFrom(RecordsEvents ...$aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            foreach ($aggregate->releaseDomainEvents() as $event) {
                $this->events[] = $event;
            }
        }
    }

    /**
     * @return list<DomainEvent>
     */
    public function release(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }
}
```

**لماذا جامع منفصل؟**

- التجمّع يسجّل أحداثه، لكن لا يعرف من سيقرأها.
- حالة الاستخدام تحدد **متى** يكون العمل مكتملاً (بعد الحفظ).
- وحدة العمل تحدد **كيف** تُنشر (بعد الـ commit).

بدون هذا الفصل، إما أن ينشر التجمّع بنفسه (فيفقد التطبيق السيطرة على التوقيت)، أو تنشر كل حالة استخدام بطريقتها (فتتكرر).

### ١٣.٢ `UnitOfWork`

```php
<?php

declare(strict_types=1);

namespace Admissions\Application;

use Admissions\Application\Port\DomainEventDispatcher;
use Admissions\Application\Port\TransactionManager;

/**
 * يفتح معاملة، ينفّذ العمل، ثم ينشر الأحداث بعد نجاح الـ commit.
 */
final class UnitOfWork
{
    public function __construct(
        private readonly TransactionManager $transactions,
        private readonly DomainEventDispatcher $events,
        private readonly DomainEventCollector $collector,
    ) {
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

        // لا نصل إلى هنا إلا إذا نجح الـ commit.
        foreach ($this->collector->release() as $event) {
            $this->events->dispatch($event);
        }

        return $result;
    }
}
```

**تحليل التوقيت:**

```text
BEGIN
  ├── load aggregates
  ├── mutate (record events)
  ├── save
  └── collect events
COMMIT
  └── publish events  ← هنا فقط
```

- لو فشل أي شيء داخل المعاملة: `transactional` يرمي، ولا يُنفَّذ سطر النشر، ويُفرَّغ الجامع في الطلب التالي (لأنه scoped).
- لو فشل النشر بعد الـ commit: البيانات محفوظة والحدث ضائع. هذا **قيد معروف** في هذا النمط، وحله Outbox (انظر الملحق ٢).

**لماذا لا `DB::afterCommit`؟** لأنها ربط بالإطار. وحدة العمل تحقق نفس الضمان دون أن يعرف التطبيق أن هناك قاعدة بيانات أصلاً.

**القيود:**

- لا ندعم المعاملات المتداخلة. حالة استخدام واحدة = معاملة واحدة.
- لا نعيد المحاولة على مستوى النشر. إعادة المحاولة على مستوى المعاملة فقط (deadlock).

---

## ١٤. طبقة التطبيق — استثناءات التطبيق

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Exception;

use Admissions\Domain\Exception\HasErrorCode;
use RuntimeException;

abstract class ApplicationException extends RuntimeException implements HasErrorCode
{
    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Exception;

use Admissions\Domain\Model\ApplicationId;

final class ApplicationNotFound extends ApplicationException
{
    public static function withId(ApplicationId $id): self
    {
        return new self("Application [{$id->value()}] was not found.");
    }

    public function errorCode(): string
    {
        return 'application_not_found';
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Exception;

use Admissions\Domain\Model\ProgramId;

final class ProgramNotFound extends ApplicationException
{
    public static function withId(ProgramId $id): self
    {
        return new self("Program [{$id->value()}] was not found.");
    }

    public function errorCode(): string
    {
        return 'program_not_found';
    }
}
```

**لماذا `RuntimeException` وليس `DomainException`؟** لأن «المورد غير موجود» ليس انتهاك قاعدة مجال؛ إنه نتيجة استعلام. لكنه يبقى استثناءً متوقعاً يحمل كود خطأ، فتُوحَّد معالجته في طبقة العرض.

---

## ١٥. طبقة التطبيق — أوامر ومعالجات

### ١٥.١ التقديم

**الأمر:**

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;

final readonly class SubmitApplication
{
    public function __construct(
        public StudentId $studentId,
        public ProgramId $programId,
        public float $gpa,
    ) {
    }
}
```

**المعالج:**

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Application\DomainEventCollector;
use Admissions\Application\Exception\ProgramNotFound;
use Admissions\Application\Port\Clock;
use Admissions\Application\Port\IdentifierGenerator;
use Admissions\Application\UnitOfWork;
use Admissions\Domain\Exception\AlreadyApplied;
use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\Gpa;
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
        private DomainEventCollector $collector,
    ) {
    }

    public function __invoke(SubmitApplication $command): string
    {
        return $this->unitOfWork->run(function () use ($command): string {
            $program = $this->programs->find($command->programId)
                ?? throw ProgramNotFound::withId($command->programId);

            if ($this->applications->existsFor($command->studentId, $command->programId)) {
                throw AlreadyApplied::for(
                    $command->studentId->value(),
                    $command->programId->value(),
                );
            }

            $application = AdmissionApplication::draft(
                ApplicationId::fromString($this->ids->next()),
                $command->studentId,
                $command->programId,
                Gpa::fromFloat($command->gpa),
            );

            $application->submit($program->requirements(), $this->clock->now());

            $this->applications->save($application);
            $this->collector->collectFrom($application);

            return $application->id()->value();
        });
    }
}
```

**ملاحظات:**

- الترتيب مقصود: جلب البرنامج، فحص التكرار، إنشاء المسودة، التقديم، الحفظ، ثم الجمع.
- `submit()` قد ترمي `GpaBelowMinimum` أو `InvalidTransition`؛ في الحالتين لا يُحفظ شيء لأن المعاملة تُراجع.
- `existsFor` فحص **ودّي** يعطي رسالة واضحة. الحماية النهائية من التكرار هي قيد `unique` في قاعدة البيانات، والمستودع يحوّل انتهاكه إلى نفس الاستثناء (انظر ١٨.٦).
- المعالج يعيد المعرّف فقط، لا الكيان. طبقة العرض تقرأ ما تحتاجه من نموذج القراءة.

### ١٥.٢ القبول

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Domain\Model\ApplicationId;

final readonly class AcceptApplication
{
    public function __construct(
        public ApplicationId $applicationId,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Application\DomainEventCollector;
use Admissions\Application\Exception\ProgramNotFound;
use Admissions\Application\Port\Clock;
use Admissions\Application\UnitOfWork;
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
        private DomainEventCollector $collector,
    ) {
    }

    public function __invoke(AcceptApplication $command): string
    {
        return $this->unitOfWork->run(function () use ($command): string {
            // قراءة بقصد التعديل: المستودع يقفل الصفوف.
            $application = $this->applications->findForUpdate($command->applicationId)
                ?? throw ApplicationNotFound::withId($command->applicationId);

            $program = $this->programs->findForUpdate($application->programId())
                ?? throw ProgramNotFound::withId($application->programId());

            $this->decisions->accept($application, $program, $this->clock->now());

            $this->applications->save($application);
            $this->programs->save($program);

            $this->collector->collectFrom($application, $program);

            return $application->id()->value();
        });
    }
}
```

**لماذا هذا أفضل من النموذج القديم؟**

| النموذج القديم | هذا البناء |
|---|---|
| `accept()` على الكيان تحفظ نفسها | الكيان لا يعرف التخزين؛ الحفظ في المعالج |
| لا يوجد دليل على الحجز | `reserveSeat()` يعيد `SeatReservation`، و`accept()` تشترطه |
| ترتيب الحجز والقبول غير محمي | خدمة مجال تضمن: فحص، حجز، قبول |
| الأحداث تُرسل من الكيان | تُجمع ثم تُنشر بعد الـ commit |

### ١٥.٣ الرفض

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Domain\Model\ApplicationId;

final readonly class RejectApplication
{
    public function __construct(
        public ApplicationId $applicationId,
        public string $reason,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Application\DomainEventCollector;
use Admissions\Application\Exception\ApplicationNotFound;
use Admissions\Application\Port\Clock;
use Admissions\Application\UnitOfWork;
use Admissions\Domain\Repository\AdmissionApplicationRepository;

final readonly class RejectApplicationHandler
{
    public function __construct(
        private AdmissionApplicationRepository $applications,
        private Clock $clock,
        private UnitOfWork $unitOfWork,
        private DomainEventCollector $collector,
    ) {
    }

    public function __invoke(RejectApplication $command): string
    {
        return $this->unitOfWork->run(function () use ($command): string {
            $application = $this->applications->findForUpdate($command->applicationId)
                ?? throw ApplicationNotFound::withId($command->applicationId);

            $application->reject($command->reason, $this->clock->now());

            $this->applications->save($application);
            $this->collector->collectFrom($application);

            return $application->id()->value();
        });
    }
}
```

### ١٥.٤ الانسحاب

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Domain\Model\ApplicationId;

final readonly class WithdrawApplication
{
    public function __construct(
        public ApplicationId $applicationId,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Application\DomainEventCollector;
use Admissions\Application\Exception\ProgramNotFound;
use Admissions\Application\Port\Clock;
use Admissions\Application\UnitOfWork;
use Admissions\Domain\Repository\AdmissionApplicationRepository;
use Admissions\Domain\Repository\ProgramRepository;
use Admissions\Domain\Service\AdmissionDecisionService;

final readonly class WithdrawApplicationHandler
{
    public function __construct(
        private AdmissionApplicationRepository $applications,
        private ProgramRepository $programs,
        private AdmissionDecisionService $decisions,
        private Clock $clock,
        private UnitOfWork $unitOfWork,
        private DomainEventCollector $collector,
    ) {
    }

    public function __invoke(WithdrawApplication $command): string
    {
        return $this->unitOfWork->run(function () use ($command): string {
            $application = $this->applications->findForUpdate($command->applicationId)
                ?? throw ApplicationNotFound::withId($command->applicationId);

            $program = $this->programs->findForUpdate($application->programId())
                ?? throw ProgramNotFound::withId($application->programId());

            $this->decisions->withdraw($application, $program, $this->clock->now());

            $this->applications->save($application);
            $this->programs->save($program);

            $this->collector->collectFrom($application, $program);

            return $application->id()->value();
        });
    }
}
```

### ١٥.٥ فتح برنامج

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Domain\Model\ProgramId;

final readonly class OpenProgram
{
    public function __construct(
        public string $name,
        public float $minimumGpa,
        public int $capacity,
        public ?ProgramId $programId = null,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\UseCase;

use Admissions\Application\Port\IdentifierGenerator;
use Admissions\Domain\Model\Gpa;
use Admissions\Domain\Model\Program;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Repository\ProgramRepository;

final readonly class OpenProgramHandler
{
    public function __construct(
        private ProgramRepository $programs,
        private IdentifierGenerator $ids,
    ) {
    }

    public function __invoke(OpenProgram $command): string
    {
        $program = Program::open(
            $command->programId ?? ProgramId::fromString($this->ids->next()),
            $command->name,
            Gpa::fromFloat($command->minimumGpa),
            $command->capacity,
        );

        $this->programs->save($program);

        return $program->id()->value();
    }
}
```

**ملاحظة:** `OpenProgramHandler` لا يحتاج `UnitOfWork` لأن العملية كتابة واحدة بلا أحداث ولا قراءات متعددة. **لا تفتح معاملة لأن النمط يقول ذلك؛ افتحها عندما تحتاج ذرّية.**

---

## ١٦. طبقة التطبيق — نموذج القراءة

حالات الاستخدام تكتب. القراءة لا تحتاج تجمّعات ولا مستودعات؛ تحتاج استعلاماً يعيد ما تعرضه الواجهة بالضبط.

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\ReadModel;

use Admissions\Domain\Model\ApplicationStatus;
use DateTimeImmutable;

final readonly class ApplicationView
{
    public function __construct(
        public string $id,
        public string $studentId,
        public string $programId,
        public string $programName,
        public float $gpa,
        public ApplicationStatus $status,
        public ?string $decisionReason,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Application\Port;

use Admissions\Application\ReadModel\ApplicationView;
use Admissions\Domain\Model\ApplicationId;

interface ApplicationQueries
{
    public function find(ApplicationId $id): ?ApplicationView;

    /**
     * @return list<ApplicationView>
     */
    public function forProgram(string $programId, int $limit = 50): array;
}
```

**لماذا نموذج قراءة منفصل؟**

- لا نحمّل تجمّعاً كاملاً لعرض صف.
- يمكن تحسين الاستعلام (join مع اسم البرنامج) دون لمس المجال.
- لا نعرّض كائنات المجال في JSON.
- في CQRS الكامل، يمكن أن يكون مصدراً منفصلاً (Read DB)، وهنا نستخدم نفس القاعدة بلا تعقيد.

---

## ١٧. البنية التحتية — سجلات Eloquent

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * سجل تخزين فقط: لا قواعد عمل، لا أحداث، لا منطق.
 * كيانات المجال لا ترث هذا الصنف.
 *
 * @property string $id
 * @property string $name
 * @property string $min_gpa
 * @property int $capacity
 * @property int $reserved_seats
 */
final class ProgramRecord extends Model
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

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $student_id
 * @property string $program_id
 * @property string $gpa
 * @property string $status
 * @property string|null $decision_reason
 */
final class ApplicationRecord extends Model
{
    protected $table = 'admission_applications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'decision_reason' => 'string',
        ];
    }
}
```

**لماذا `$guarded = []` هنا آمن؟** لأن السجل لا يُملأ من طلب HTTP أبداً. كل الكتابات تمر عبر `forceFill()` داخل المستودع بقيم ينتجها المابّر من كيان المجال. لا يوجد مسار `$request->all()` إلى هنا.

**لماذا `final`؟** لا أحد يرث سجل تخزين. الوراثة هنا تعني أن سلوكاً ما يتسرّب، وهو غير مرغوب.

---

## ١٨. البنية التحتية — المابّرات والمستودعات

### ١٨.١ `ProgramMapper`

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Persistence;

use Admissions\Domain\Model\Gpa;
use Admissions\Domain\Model\Program;
use Admissions\Domain\Model\ProgramId;
use Admissions\Infrastructure\Persistence\Eloquent\ProgramRecord;

final class ProgramMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toAttributes(Program $program): array
    {
        return [
            'id' => $program->id()->value(),
            'name' => $program->name(),
            'min_gpa' => number_format($program->requirements()->minimumGpa()->value(), 2, '.', ''),
            'capacity' => $program->capacity(),
            'reserved_seats' => $program->reservedSeats(),
        ];
    }

    public static function toDomain(ProgramRecord $record): Program
    {
        return Program::reconstitute(
            ProgramId::fromString($record->id),
            $record->name,
            Gpa::fromDatabase($record->min_gpa),
            (int) $record->capacity,
            (int) $record->reserved_seats,
        );
    }
}
```

### ١٨.٢ `ApplicationMapper`

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
use Admissions\Infrastructure\Persistence\Eloquent\ApplicationRecord;

final class ApplicationMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toAttributes(AdmissionApplication $application): array
    {
        return [
            'id' => $application->id()->value(),
            'student_id' => $application->studentId()->value(),
            'program_id' => $application->programId()->value(),
            'gpa' => number_format($application->gpa()->value(), 2, '.', ''),
            'status' => $application->status()->value,
            'decision_reason' => $application->decisionReason(),
        ];
    }

    public static function toDomain(ApplicationRecord $record): AdmissionApplication
    {
        return AdmissionApplication::reconstitute(
            ApplicationId::fromString($record->id),
            StudentId::fromString($record->student_id),
            ProgramId::fromString($record->program_id),
            Gpa::fromDatabase($record->gpa),
            ApplicationStatus::from($record->status),
            $record->decision_reason,
        );
    }
}
```

### ١٨.٣ مستودع البرامج

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Persistence;

use Admissions\Domain\Model\Program;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Repository\ProgramRepository;
use Admissions\Infrastructure\Persistence\Eloquent\ProgramRecord;

final readonly class EloquentProgramRepository implements ProgramRepository
{
    public function find(ProgramId $id): ?Program
    {
        $record = ProgramRecord::query()->find($id->value());

        return $record === null ? null : ProgramMapper::toDomain($record);
    }

    public function findForUpdate(ProgramId $id): ?Program
    {
        $record = ProgramRecord::query()
            ->lockForUpdate()
            ->find($id->value());

        return $record === null ? null : ProgramMapper::toDomain($record);
    }

    public function save(Program $program): void
    {
        $attributes = ProgramMapper::toAttributes($program);

        $record = ProgramRecord::query()->find($program->id()->value());

        if ($record === null) {
            $record = new ProgramRecord();
            $record->forceFill($attributes);
            $record->save();

            return;
        }

        $record->forceFill($attributes)->save();
    }
}
```

### ١٨.٤ مستودع الطلبات

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Persistence;

use Admissions\Domain\Exception\AlreadyApplied;
use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Admissions\Domain\Repository\AdmissionApplicationRepository;
use Admissions\Infrastructure\Persistence\Eloquent\ApplicationRecord;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class EloquentAdmissionApplicationRepository implements AdmissionApplicationRepository
{
    public function find(ApplicationId $id): ?AdmissionApplication
    {
        $record = ApplicationRecord::query()->find($id->value());

        return $record === null ? null : ApplicationMapper::toDomain($record);
    }

    public function findForUpdate(ApplicationId $id): ?AdmissionApplication
    {
        $record = ApplicationRecord::query()
            ->lockForUpdate()
            ->find($id->value());

        return $record === null ? null : ApplicationMapper::toDomain($record);
    }

    public function existsFor(StudentId $student, ProgramId $program): bool
    {
        return ApplicationRecord::query()
            ->where('student_id', $student->value())
            ->where('program_id', $program->value())
            ->exists();
    }

    public function save(AdmissionApplication $application): void
    {
        $attributes = ApplicationMapper::toAttributes($application);

        $record = ApplicationRecord::query()->find($application->id()->value());

        if ($record === null) {
            $record = new ApplicationRecord();
            $record->forceFill($attributes);

            try {
                $record->save();
            } catch (UniqueConstraintViolationException) {
                // سباق: طلب آخر أُنشئ بنفس (الطالب، البرنامج) بين الفحص والإدراج.
                throw AlreadyApplied::for(
                    $application->studentId()->value(),
                    $application->programId()->value(),
                );
            }

            return;
        }

        $record->forceFill($attributes)->save();
    }
}
```

**لماذا تحويل استثناء قاعدة البيانات إلى استثناء مجال؟**

- الفحص `existsFor` والكتابة ليسا ذرّيين معاً. بينهما نافذة سباق.
- قيد `unique` هو الحكم النهائي.
- لكن `UniqueConstraintViolationException` تفصيل تقني لا يجب أن يصل لطبقة العرض.
- التحويل هنا، على حدود البنية التحتية، هو المكان الصحيح.

**ملاحظة:** استيراد `UniqueConstraintViolationException` صحيح في لارافل ١٠+ (متوفر أيضاً في ١١ و١٢ و١٣). في الإصدارات الأقدم، استخدم `QueryException` وافحص رمز SQL.

### ١٨.٥ قفل الصفوف والترتيب

- `findForUpdate` ينفّذ `SELECT ... FOR UPDATE`. على MySQL/PostgreSQL يقفل الصف.
- على SQLite، يُتجاهل القفل؛ الاختبارات على SQLite **لا تثبت** التزامن. هذا موثق لا مخفي.
- ترتيب الأقفال ثابت في كل المعالجات: **الطلب ثم البرنامج**. أي معالج جديد يجب أن يتبع نفس الترتيب لتجنّب الجمود.
- إعادة المحاولة عند الجمود مسؤولية `TransactionManager` (انظر ٢٠.٦).

### ١٨.٦ لماذا لا `updateOrCreate`؟

`updateOrCreate` يخفي الفرق بين «إدراج» و«تحديث»، ولا يسمح بالتقاط انتهاك القيد بدقة، وقد يُنشئ صفاً بمعرّف غير متوقع عند غياب المفتاح. الفحص الصريح أوضح، والثمن استعلام إضافي واحد.

---

### ١٨.٧ تنفيذ نموذج القراءة

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Persistence;

use Admissions\Application\Port\ApplicationQueries;
use Admissions\Application\ReadModel\ApplicationView;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ApplicationStatus;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;

final readonly class EloquentApplicationQueries implements ApplicationQueries
{
    public function __construct(private DatabaseManager $database)
    {
    }

    public function find(ApplicationId $id): ?ApplicationView
    {
        $row = $this->baseQuery()
            ->where('a.id', $id->value())
            ->first();

        return $row === null ? null : $this->toView($row);
    }

    /**
     * @return list<ApplicationView>
     */
    public function forProgram(string $programId, int $limit = 50): array
    {
        return $this->baseQuery()
            ->where('a.program_id', $programId)
            ->orderByDesc('a.created_at')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): ApplicationView => $this->toView($row))
            ->all();
    }

    private function baseQuery(): Builder
    {
        return $this->database->connection()
            ->table('admission_applications as a')
            ->join('programs as p', 'p.id', '=', 'a.program_id')
            ->select([
                'a.id',
                'a.student_id',
                'a.program_id',
                'p.name as program_name',
                'a.gpa',
                'a.status',
                'a.decision_reason',
                'a.created_at',
                'a.updated_at',
            ]);
    }

    private function toView(object $row): ApplicationView
    {
        return new ApplicationView(
            (string) $row->id,
            (string) $row->student_id,
            (string) $row->program_id,
            (string) $row->program_name,
            (float) $row->gpa,
            ApplicationStatus::from((string) $row->status),
            $row->decision_reason === null ? null : (string) $row->decision_reason,
            new DateTimeImmutable((string) $row->created_at),
            new DateTimeImmutable((string) $row->updated_at),
        );
    }
}
```

**لماذا استعلام واحد مع Join؟**

- لا نحمّل تجمّعين لعرض بطاقة واحدة.
- لا N+1.
- الأعمدة المطلوبة فقط.

**لماذا لا تستخدم `ApplicationView` داخل التجمّعات؟** لأن نموذج القراءة قد يتغيّر لأسباب عرض (إضافة اسم البرنامج، شعار، عدد المقاعد المتبقية) لا لأسباب عمل. فصله يحمي المجال من تقلّبات الواجهة.

---

## ١٩. البنية التحتية — تنفيذ المنافذ

### ١٩.١ `SystemClock`

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Laravel;

use Admissions\Application\Port\Clock;
use DateTimeImmutable;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
```

### ١٩.٢ `UlidGenerator`

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Laravel;

use Admissions\Application\Port\IdentifierGenerator;
use Illuminate\Support\Str;

final class UlidGenerator implements IdentifierGenerator
{
    public function next(): string
    {
        return (string) Str::ulid();
    }
}
```

### ١٩.٣ `LaravelTransactionManager`

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Laravel;

use Admissions\Application\Port\TransactionManager;
use Illuminate\Database\DatabaseManager;

final readonly class LaravelTransactionManager implements TransactionManager
{
    public function __construct(private DatabaseManager $database)
    {
    }

    public function transactional(callable $work): mixed
    {
        // المحاولة الثانية تُفعّل فقط عند الجمود أو خطأ الاتصال.
        return $this->database->connection()->transaction($work, attempts: 3);
    }
}
```

### ١٩.٤ `LaravelDomainEventDispatcher`

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Laravel;

use Admissions\Application\Port\DomainEventDispatcher;
use Admissions\Domain\Model\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class LaravelDomainEventDispatcher implements DomainEventDispatcher
{
    public function __construct(private Dispatcher $events)
    {
    }

    public function dispatch(DomainEvent $event): void
    {
        $this->events->dispatch($event);
    }
}
```

### ١٩.٥ مزوّد الخدمة

```php
<?php

declare(strict_types=1);

namespace Admissions\Infrastructure\Provider;

use Admissions\Application\DomainEventCollector;
use Admissions\Application\Port\ApplicationQueries;
use Admissions\Application\Port\Clock;
use Admissions\Application\Port\DomainEventDispatcher;
use Admissions\Application\Port\IdentifierGenerator;
use Admissions\Application\Port\TransactionManager;
use Admissions\Application\UnitOfWork;
use Admissions\Domain\Repository\AdmissionApplicationRepository;
use Admissions\Domain\Repository\ProgramRepository;
use Admissions\Domain\Service\AdmissionDecisionService;
use Admissions\Infrastructure\Laravel\LaravelDomainEventDispatcher;
use Admissions\Infrastructure\Laravel\LaravelTransactionManager;
use Admissions\Infrastructure\Laravel\SystemClock;
use Admissions\Infrastructure\Laravel\UlidGenerator;
use Admissions\Infrastructure\Persistence\EloquentAdmissionApplicationRepository;
use Admissions\Infrastructure\Persistence\EloquentApplicationQueries;
use Admissions\Infrastructure\Persistence\EloquentProgramRepository;
use Illuminate\Support\ServiceProvider;

final class AdmissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(IdentifierGenerator::class, UlidGenerator::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->bind(DomainEventDispatcher::class, LaravelDomainEventDispatcher::class);

        $this->app->bind(ProgramRepository::class, EloquentProgramRepository::class);
        $this->app->bind(AdmissionApplicationRepository::class, EloquentAdmissionApplicationRepository::class);
        $this->app->bind(ApplicationQueries::class, EloquentApplicationQueries::class);

        $this->app->singleton(AdmissionDecisionService::class);

        // scoped: نسخة لكل طلب/وظيفة، وتُصفَّر بعدها.
        $this->app->scoped(DomainEventCollector::class);
        $this->app->scoped(UnitOfWork::class);
    }
}
```

**لماذا `scoped` بدل `singleton`؟**

- `singleton` يعيش طوال عمر العامل (worker). في طابور طويل الأمد، هذا يعني أن أحداث طلب سابق قد تُنشر مع طلب لاحق.
- `scoped` يُصفَّر عند نهاية الطلب أو الوظيفة، وهو السلوك الصحيح.
- هذا خطأ شائع يظهر فقط في بيئة الطابور، ولا يظهر في الاختبارات.


---

## ٢٠. سياق الإشعارات — منفذ ومحول واستخدام

### ٢٠.١ العقد بين السياقين

سياق الإشعارات لا يقرأ جداول `admissions` مباشرة، ولا يستورد كياناتها. يعرّف ما يحتاجه كمنفذ:

```php
<?php

declare(strict_types=1);

namespace Notifications\Application;

final readonly class StudentProfile
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Notifications\Application\Port;

use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Notifications\Application\StudentProfile;

/**
 * منفذ القراءة الوحيد نحو سياق القبول.
 * لو تغيّر شكل جدول المستخدمين أو البرامج، يتغيّر المحوّل فقط.
 */
interface AdmissionsDirectory
{
    public function student(StudentId $id): ?StudentProfile;

    public function programName(ProgramId $id): ?string;
}
```

### ٢٠.٢ منافذ الإرسال والتكرار

```php
<?php

declare(strict_types=1);

namespace Notifications\Application\Port;

use Notifications\Application\StudentProfile;

interface AcceptanceMailer
{
    public function sendAcceptance(
        StudentProfile $student,
        string $programName,
        string $applicationId,
    ): void;
}
```

```php
<?php

declare(strict_types=1);

namespace Notifications\Application\Port;

/**
 * يسجّل الإشعارات المرسلة لمنع التكرار عند إعادة محاولة الوظيفة.
 */
interface SentNotifications
{
    public function alreadySent(string $key): bool;

    public function markSent(string $key): void;
}
```

### ٢٠.٣ حالة الاستخدام

```php
<?php

declare(strict_types=1);

namespace Notifications\Application;

use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Notifications\Application\Port\AcceptanceMailer;
use Notifications\Application\Port\AdmissionsDirectory;
use Notifications\Application\Port\SentNotifications;

enum NotificationOutcome
{
    case Sent;
    case AlreadySent;
    case Skipped;
}

final readonly class NotifyStudentOfAcceptance
{
    public function __construct(
        private AdmissionsDirectory $directory,
        private AcceptanceMailer $mailer,
        private SentNotifications $sent,
    ) {
    }

    public function __invoke(
        StudentId $studentId,
        ApplicationId $applicationId,
        ProgramId $programId,
    ): NotificationOutcome {
        $key = 'application_accepted:' . $applicationId->value();

        if ($this->sent->alreadySent($key)) {
            return NotificationOutcome::AlreadySent;
        }

        $student = $this->directory->student($studentId);
        $programName = $this->directory->programName($programId);

        if ($student === null || $programName === null) {
            // بيانات مرجعية مفقودة: إعادة المحاولة لن تغيّر النتيجة.
            return NotificationOutcome::Skipped;
        }

        $this->mailer->sendAcceptance($student, $programName, $applicationId->value());
        $this->sent->markSent($key);

        return NotificationOutcome::Sent;
    }
}
```

**لماذا `NotificationOutcome` بدل `void`؟** لأن المستمع يحتاج قراراً: هل يسجّل تحذيراً؟ هل يفشل الوظيفة؟ إرجاع حالة صريحة يجعل القرار في الطبقة التي تعرف الطابور، لا في حالة الاستخدام.

**لماذا المفتاح هو معرّف الطلب؟** لأن حدث القبول لطلب واحد يجب أن ينتج إشعاراً واحداً. إعادة المحاولة بعد فشل جزئي (أُرسل البريد ثم فشل التسجيل) قد ترسل مرة أخرى؛ هذا **مرة على الأقل**، وهو مقبول مقابل عدم فقدان الإشعار.

### ٢٠.٤ المحوّلات

```php
<?php

declare(strict_types=1);

namespace Notifications\Infrastructure\Directory;

use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Illuminate\Database\DatabaseManager;
use Notifications\Application\Port\AdmissionsDirectory;
use Notifications\Application\StudentProfile;

final readonly class EloquentAdmissionsDirectory implements AdmissionsDirectory
{
    public function __construct(private DatabaseManager $database)
    {
    }

    public function student(StudentId $id): ?StudentProfile
    {
        $row = $this->database->connection()
            ->table('users')
            ->select(['id', 'name', 'email'])
            ->where('id', $id->value())
            ->first();

        if ($row === null) {
            return null;
        }

        return new StudentProfile(
            (string) $row->id,
            (string) $row->name,
            (string) $row->email,
        );
    }

    public function programName(ProgramId $id): ?string
    {
        $name = $this->database->connection()
            ->table('programs')
            ->where('id', $id->value())
            ->value('name');

        return $name === null ? null : (string) $name;
    }
}
```

**لماذا استعلام صريح وليس `User::find()`؟**

- لا اقتران بموديل سياق آخر.
- نطلب ثلاثة أعمدة فقط.
- لو انتقل المستخدمون إلى خدمة خارجية، نغيّر هذا الملف وحده.

### ٢٠.٥ التنفيذ: البريد والتكرار

```php
<?php

declare(strict_types=1);

namespace Notifications\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class AcceptanceMail extends Mailable
{
    public function __construct(
        public readonly string $studentName,
        public readonly string $programName,
        public readonly string $applicationId,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'تم قبول طلبك');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.acceptance',
            with: [
                'studentName' => $this->studentName,
                'programName' => $this->programName,
                'applicationId' => $this->applicationId,
            ],
        );
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Notifications\Infrastructure\Mail;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Notifications\Application\Port\AcceptanceMailer;
use Notifications\Application\StudentProfile;

final readonly class LaravelAcceptanceMailer implements AcceptanceMailer
{
    public function __construct(private MailFactory $mail)
    {
    }

    public function sendAcceptance(
        StudentProfile $student,
        string $programName,
        string $applicationId,
    ): void {
        $this->mail
            ->mailer()
            ->to($student->email, $student->name)
            ->send(new AcceptanceMail($student->name, $programName, $applicationId));
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Notifications\Infrastructure\Idempotency;

use Illuminate\Database\DatabaseManager;
use Notifications\Application\Port\SentNotifications;

final readonly class DatabaseSentNotifications implements SentNotifications
{
    public function __construct(private DatabaseManager $database)
    {
    }

    public function alreadySent(string $key): bool
    {
        return $this->database->connection()
            ->table('sent_notifications')
            ->where('notification_key', $key)
            ->exists();
    }

    public function markSent(string $key): void
    {
        $this->database->connection()
            ->table('sent_notifications')
            ->insertOrIgnore([
                'notification_key' => $key,
                'sent_at' => now(),
            ]);
    }
}
```

### ٢٠.٦ المستمع: ترجمة الحدث إلى حالة استخدام

```php
<?php

declare(strict_types=1);

namespace Notifications\Infrastructure\Listener;

use Admissions\Domain\Model\Event\ApplicationAccepted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Notifications\Application\NotificationOutcome;
use Notifications\Application\NotifyStudentOfAcceptance;
use Psr\Log\LoggerInterface;

#[Tries(5)]
#[Backoff(10, 30, 60)]
final readonly class OnApplicationAccepted implements ShouldQueue
{
    public function __construct(
        private NotifyStudentOfAcceptance $notify,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(ApplicationAccepted $event): void
    {
        $outcome = ($this->notify)(
            $event->studentId,
            $event->applicationId,
            $event->programId,
        );

        if ($outcome === NotificationOutcome::Skipped) {
            $this->logger->warning('acceptance_notification_skipped', [
                'application_id' => $event->applicationId->value(),
                'student_id' => $event->studentId->value(),
            ]);
        }
    }
}
```

**ماذا يحدث بالضبط؟**

1. وحدة العمل تنشر `ApplicationAccepted` بعد الـ commit.
2. `Event::listen` (تسجيل صريح في مزوّد الخدمة) يربطه بهذا المستمع.
3. لأنه `ShouldQueue`، تُدفع وظيفة إلى الطابور تحمل الحدث (كائنات قيمة بسيطة، لا موديلات).
4. عامل الطابور ينفّذ `handle()`، فينادي حالة الاستخدام.
5. حالة الاستخدام تفحص سجل الإرسال، تقرأ البيانات، ترسل، تسجّل.
6. عند الفشل: إعادة محاولة حتى ٥ مرات بفواصل تصاعدية، ثم `failed_jobs`.

### ٢٠.٧ التسجيل الصريح

```php
<?php

declare(strict_types=1);

namespace Notifications\Infrastructure\Provider;

use Admissions\Domain\Model\Event\ApplicationAccepted;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Notifications\Application\NotifyStudentOfAcceptance;
use Notifications\Application\Port\AcceptanceMailer;
use Notifications\Application\Port\AdmissionsDirectory;
use Notifications\Application\Port\SentNotifications;
use Notifications\Infrastructure\Directory\EloquentAdmissionsDirectory;
use Notifications\Infrastructure\Idempotency\DatabaseSentNotifications;
use Notifications\Infrastructure\Listener\OnApplicationAccepted;
use Notifications\Infrastructure\Mail\LaravelAcceptanceMailer;

final class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdmissionsDirectory::class, EloquentAdmissionsDirectory::class);
        $this->app->bind(AcceptanceMailer::class, LaravelAcceptanceMailer::class);
        $this->app->bind(SentNotifications::class, DatabaseSentNotifications::class);
    }

    public function boot(Dispatcher $events): void
    {
        // سطر واحد يربط الحدث بالمستمع: لا اكتشاف، لا تخمين أسماء، لا صمت.
        $events->listen(ApplicationAccepted::class, OnApplicationAccepted::class);
    }
}
```

**لماذا التسجيل الصريح أهم من الاكتشاف التلقائي هنا؟**

- الاكتشاف التلقائي في لارافل يخمّن اسم الكلاس من مساره، ويفترض `App\` أو مسار `app/`. أي هيكل مثل `src/Notifications/...` قد يفشل **بصمت**.
- التسجيل الصريح يعمل مع أي هيكل، ولا يتأثر بـ `event:cache` أو مسارات غير قياسية.
- اختبار واحد يثبت أن الربط موجود (انظر ٢٥.٥).

---

## ٢١. طبقة العرض — الطلبات والتحقق

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // كل مستخدم مصادق يقدّم لنفسه؛ الهوية تُؤخذ من الجلسة لا من الحقل.
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
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class RejectApplicationRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProgramRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'minimum_gpa' => ['required', 'numeric', 'between:0,5'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
```

**حدود التحقق هنا:**

- `size:26` يتحقق من **شكل** المعرّف فقط. صحة ULID الفعلية (قاعدة الأحرف) يفحصها `ProgramId::fromString()` في المجال.
- `between:0,5` يكرر مدى `Gpa`. هذا مقصود: رسالة خطأ مبكرة وواضحة، ثم تحقق المجال كحماية نهائية.
- قواعد العمل (هل البرنامج مفتوح؟ هل المعدل يكفي؟ هل الطالب قدّم سابقاً؟) **ليست هنا**.

---

## ٢٢. طبقة العرض — الموارد والاستجابات

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http\Resource;

use Admissions\Application\ReadModel\ApplicationView;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ApplicationView
 */
final class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'program' => [
                'id' => $this->programId,
                'name' => $this->programName,
            ],
            'gpa' => $this->gpa,
            'status' => $this->status->value,
            'decision_reason' => $this->decisionReason,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
```

**لماذا Resource وليس إرجاع الموديل؟**

- شكل الاستجابة عقد عام؛ لا يجب أن يتغيّر لأن عموداً أُضيف.
- لا نكشف `reserved_seats` أو حقولاً داخلية.
- يمكن إخفاء `student_id` لمن لا يملك صلاحية، أو تحويل التواريخ لصيغة موحّدة.

---

## ٢٣. طبقة العرض — المتحكمات

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http\Controller;

use Admissions\Application\Port\ApplicationQueries;
use Admissions\Application\UseCase\AcceptApplication;
use Admissions\Application\UseCase\AcceptApplicationHandler;
use Admissions\Application\UseCase\RejectApplication;
use Admissions\Application\UseCase\RejectApplicationHandler;
use Admissions\Application\UseCase\SubmitApplication;
use Admissions\Application\UseCase\SubmitApplicationHandler;
use Admissions\Application\UseCase\WithdrawApplication;
use Admissions\Application\UseCase\WithdrawApplicationHandler;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Admissions\Presentation\Http\Request\RejectApplicationRequest;
use Admissions\Presentation\Http\Request\SubmitApplicationRequest;
use Admissions\Presentation\Http\Resource\ApplicationResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final readonly class ApplicationController
{
    public function __construct(private ApplicationQueries $queries)
    {
    }

    public function store(
        SubmitApplicationRequest $request,
        SubmitApplicationHandler $handler,
    ): HttpResponse {
        $id = $handler(new SubmitApplication(
            StudentId::fromString((string) $request->user()->getAuthIdentifier()),
            ProgramId::fromString((string) $request->string('program_id')),
            (float) $request->float('gpa'),
        ));

        return $this->respond(ApplicationId::fromString($id), HttpResponse::HTTP_CREATED);
    }

    public function show(string $id): HttpResponse
    {
        return $this->respond(ApplicationId::fromString($id));
    }

    public function accept(string $id, AcceptApplicationHandler $handler): HttpResponse
    {
        Gate::authorize('admissions.decide');

        $applicationId = ApplicationId::fromString($id);

        $handler(new AcceptApplication($applicationId));

        return $this->respond($applicationId);
    }

    public function reject(
        string $id,
        RejectApplicationRequest $request,
        RejectApplicationHandler $handler,
    ): HttpResponse {
        Gate::authorize('admissions.decide');

        $applicationId = ApplicationId::fromString($id);

        $handler(new RejectApplication(
            $applicationId,
            (string) $request->string('reason'),
        ));

        return $this->respond($applicationId);
    }

    public function withdraw(string $id, WithdrawApplicationHandler $handler): HttpResponse
    {
        $applicationId = ApplicationId::fromString($id);

        $view = $this->queries->find($applicationId);

        abort_if($view === null, Response::HTTP_NOT_FOUND);

        Gate::authorize('application.withdraw', $view);

        $handler(new WithdrawApplication($applicationId));

        return $this->respond($applicationId);
    }

    private function respond(
        ApplicationId $id,
        int $status = HttpResponse::HTTP_OK,
    ): HttpResponse {
        $view = $this->queries->find($id);

        abort_if($view === null, Response::HTTP_NOT_FOUND);

        return ApplicationResource::make($view)
            ->response()
            ->setStatusCode($status);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http\Controller;

use Admissions\Application\UseCase\OpenProgram;
use Admissions\Application\UseCase\OpenProgramHandler;
use Admissions\Presentation\Http\Request\StoreProgramRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final readonly class ProgramController
{
    public function store(StoreProgramRequest $request, OpenProgramHandler $handler): JsonResponse
    {
        $id = $handler(new OpenProgram(
            (string) $request->string('name'),
            (float) $request->float('minimum_gpa'),
            (int) $request->integer('capacity'),
        ));

        return new JsonResponse(['id' => $id], HttpResponse::HTTP_CREATED);
    }
}
```

**ما الذي لا يوجد في المتحكم؟**

- لا `DB::transaction`.
- لا `find` أو `where`.
- لا قواعد عمل.
- لا `try/catch` لتحويل الاستثناءات (مكانهما مركزي في `bootstrap/app.php`).
- لا بناء استجابة يدوي.

المتحكم يعرف: كيف يقرأ المدخلات، ومن ينادي، وكيف يعيد النتيجة. لا شيء آخر.

---

## ٢٤. ربط الأخطاء بـ HTTP

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http;

use Admissions\Domain\Exception\HasErrorCode;

/**
 * تحويل واحد لأكواد الأخطاء إلى رموز HTTP.
 * لا يعرف المجال شيئاً عن هذا الجدول.
 */
final class DomainExceptionStatus
{
    /** @var array<string, int> */
    private const MAP = [
        'invalid_identifier' => 422,
        'invalid_gpa' => 422,
        'gpa_below_minimum' => 422,
        'rejection_reason_required' => 422,
        'invalid_program_capacity' => 422,
        'invalid_transition' => 409,
        'no_seats_available' => 409,
        'already_applied' => 409,
        'application_not_found' => 404,
        'program_not_found' => 404,
        'reservation_mismatch' => 500,
    ];

    public static function for(string $errorCode): int
    {
        return self::MAP[$errorCode] ?? 500;
    }

    public static function isClientError(string $errorCode): bool
    {
        return self::for($errorCode) < 500;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Admissions\Presentation\Http;

use Admissions\Domain\Exception\HasErrorCode;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

final class DomainExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (HasErrorCode $exception, Request $request) {
            $status = DomainExceptionStatus::for($exception->errorCode());

            if (! $request->expectsJson()) {
                return back()->withErrors([
                    'domain' => $status >= 500
                        ? 'An unexpected error occurred.'
                        : $exception->getMessage(),
                ])->setStatusCode($status);
            }

            $payload = [
                'error' => [
                    'code' => $exception->errorCode(),
                    'message' => $status >= 500
                        ? 'An unexpected error occurred.'
                        : $exception->getMessage(),
                ],
            ];

            // التفاصيل المنظمة تُرسل فقط لأخطاء المستخدم، لا لأخطاء الخادم.
            if (DomainExceptionStatus::isClientError($exception->errorCode())
                && $exception->context() !== []) {
                $payload['error']['context'] = $exception->context();
            }

            return response()->json($payload, $status);
        });
    }
}
```

**قواعد التحويل:**

| الرمز | الدلالة | مثال |
|---|---|---|
| ٤٠٤ | المورد غير موجود | `application_not_found`, `program_not_found` |
| ٤٢٢ | مدخلات مفهومة لكن غير صالحة | `invalid_gpa`, `gpa_below_minimum` |
| ٤٠٩ | تعارض مع حالة المورد | `invalid_transition`, `no_seats_available`, `already_applied` |
| ٥٠٠ | خلل داخلي | `reservation_mismatch` |

**لماذا ٤٠٩ للنقص في المقاعد وليس ٤٢٢؟** لأن الطلب نفسه صالح؛ التعارض بين الطلب وحالة المورد (امتلأ البرنامج). ٤٠٩ يسمح للعميل بالتمييز بين «صحّح بياناتك» و«اعد المحاولة لاحقاً أو غيّر البرنامج».

**لماذا لا نكشف رسائل ٥٠٠؟** الرسالة قد تحتوي معرّفات داخلية. نسجّل التفاصيل، ونرسل رسالة عامة.


---

## ٢٥. التركيب: المزوّدون والمسارات والإعداد

### ٢٥.١ `composer.json`

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Admissions\\": "src/Admissions/",
            "Notifications\\": "src/Notifications/"
        }
    }
}
```

ثم:

```bash
composer dump-autoload
```

**نصيحة:** شغّل `composer dump-autoload -o` في الإنتاج، وأضف `src/` إلى PHPStan وPint وRector، وإلا فلن تُفحص هذه الملفات.

### ٢٥.٢ التسجيل في `bootstrap/providers.php`

```php
<?php

return [
    App\Providers\AppServiceProvider::class,

    Admissions\Infrastructure\Provider\AdmissionsServiceProvider::class,
    Notifications\Infrastructure\Provider\NotificationsServiceProvider::class,
];
```

### ٢٥.٣ `bootstrap/app.php`

```php
<?php

use Admissions\Presentation\Http\DomainExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // لا شيء خاص هنا؛ Sanctum يُسجَّل في config أو عبر middleware('auth:sanctum').
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        DomainExceptionRenderer::register($exceptions);
    })
    ->create();
```

**لماذا لا `withEvents(discover: ...)`؟** لأن التسجيل صريح في `NotificationsServiceProvider::boot()`. لا اكتشاف، لا مسارات نمطية، لا صمت عند الفشل.

### ٢٥.٤ الصلاحيات

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Admissions\Application\ReadModel\ApplicationView;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define(
            'admissions.decide',
            static fn ($user): bool => method_exists($user, 'hasRole')
                && $user->hasRole('admissions_officer'),
        );

        Gate::define(
            'admissions.manage',
            static fn ($user): bool => method_exists($user, 'hasRole')
                && $user->hasRole('admissions_manager'),
        );

        Gate::define(
            'application.withdraw',
            static fn ($user, ApplicationView $application): bool =>
                (string) $user->getAuthIdentifier() === $application->studentId
                || (method_exists($user, 'hasRole') && $user->hasRole('admissions_manager')),
        );
    }
}
```

**أين توضع الصلاحيات؟** في طبقة العرض/التطبيق، لا في المجال. المجال يجيب: «هل هذا الانتقال مسموح؟»؛ السياسة تجيب: «هل هذا المستخدم مخوّل؟». خلطهما يجعل الكيان يعرف عن المستخدمين والأدوار، وهو تسريب واضح.

### ٢٥.٥ المسارات

```php
<?php

declare(strict_types=1);

use Admissions\Presentation\Http\Controller\ApplicationController;
use Admissions\Presentation\Http\Controller\ProgramController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->group(function (): void {
        Route::post('/programs', [ProgramController::class, 'store'])
            ->middleware('can:admissions.manage');

        Route::post('/applications', [ApplicationController::class, 'store'])
            ->middleware('throttle:applications');

        Route::get('/applications/{id}', [ApplicationController::class, 'show']);

        Route::post('/applications/{id}/accept', [ApplicationController::class, 'accept'])
            ->middleware('can:admissions.decide');

        Route::post('/applications/{id}/reject', [ApplicationController::class, 'reject'])
            ->middleware('can:admissions.decide');

        Route::post('/applications/{id}/withdraw', [ApplicationController::class, 'withdraw']);
    });
```

### ٢٥.٦ محدد المعدل

```php
<?php

// AppServiceProvider::boot()
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('applications', static fn ($request) => Limit::perMinute(10)
    ->by((string) $request->user()?->getAuthIdentifier()));
```

### ٢٥.٧ نموذج البريد

```blade
{{-- resources/views/mail/acceptance.blade.php --}}
<div dir="rtl" style="font-family: Tahoma, sans-serif; line-height: 1.8">
    <p>مرحباً {{ $studentName }}،</p>

    <p>يسرّنا إبلاغك بقبول طلبك في برنامج <strong>{{ $programName }}</strong>.</p>

    <p>رقم الطلب: <code>{{ $applicationId }}</code></p>

    <p>يُرجى متابعة لوحة الطالب لإكمال إجراءات التسجيل.</p>

    <p>مع التحية،<br>إدارة القبول</p>
</div>
```

---

## ٢٦. قاعدة البيانات

### ٢٦.١ جدول البرامج

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

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement('ALTER TABLE programs ADD CONSTRAINT chk_programs_capacity CHECK (capacity >= 1)');
            DB::statement('ALTER TABLE programs ADD CONSTRAINT chk_programs_reserved CHECK (reserved_seats <= capacity)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
```

**لماذا `char(26)` وليس `varchar`؟** لأن ULID طوله ثابت 26. `char` يوفّر مساحة ويجعل الفهرس أصغر وأسرع على MySQL.

### ٢٦.٢ جدول الطلبات

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

            $table->unique(['student_id', 'program_id'], 'uniq_application_per_program');
            $table->index(['program_id', 'status'], 'idx_applications_program_status');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement(
                'ALTER TABLE admission_applications ADD CONSTRAINT chk_applications_gpa '
                . 'CHECK (gpa >= 0 AND gpa <= 5)',
            );

            DB::statement(
                'ALTER TABLE admission_applications ADD CONSTRAINT chk_applications_status '
                . "CHECK (status IN ('draft','submitted','accepted','rejected','withdrawn'))",
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
    }
};
```

**ملاحظة على نوع `student_id`:** استخدمت `unsignedBigInteger` افتراضاً لأن `users.id` الافتراضي في لارافل كذلك. إن كان مشروعك يستخدم UUID أو ULID للمستخدمين، غيّر النوع والعلاقة معاً.

**ملاحظة على `CHECK`:** MySQL 8.0.16+ وPostgreSQL يدعمان `ALTER TABLE ... ADD CONSTRAINT`. أما SQLite فلا يدعم إضافة قيد بعد إنشاء الجدول، ولذلك وضعت شرطاً على نوع المحرك. النتيجة: اختبارات SQLite لا تتحقق من قيود `CHECK`؛ اختبرها في CI على نفس محرك الإنتاج. (يمكن كتابة الجدول بـ SQL خام ليعمل القيد على كل المحركات، لكنه يفقد قابلية القراءة.)

### ٢٦.٣ جدول الإشعارات المرسلة

```php
Schema::create('sent_notifications', function (Blueprint $table): void {
    $table->string('notification_key', 120)->primary();
    $table->timestamp('sent_at');
});
```

### ٢٦.٤ فهارس إضافية للقراءة

```php
// إن كانت قوائم الطلبات تُفلتر بالحالة والتاريخ:
$table->index(['status', 'created_at'], 'idx_applications_status_created');
```

لا تضف فهارس «احتياطاً». كل فهرس يكلّف كتابة ومساحة. أضفه عندما تعرف الاستعلام.

---

## ٢٧. الاختبارات

### ٢٧.١ اختبار `Gpa` (وحدة، بلا قاعدة بيانات)

```php
<?php

declare(strict_types=1);

use Admissions\Domain\Exception\InvalidGpa;
use Admissions\Domain\Model\Gpa;

it('accepts boundary values', function (float $value): void {
    expect(Gpa::fromFloat($value)->value())->toBe($value);
})->with([0.0, 2.5, 5.0]);

it('rejects values outside the scale', function (float $value): void {
    expect(fn () => Gpa::fromFloat($value))->toThrow(InvalidGpa::class);
})->with([-0.01, 5.01, -100.0, 100.0]);

it('rejects non-finite values', function (float $value): void {
    expect(fn () => Gpa::fromFloat($value))->toThrow(InvalidGpa::class);
})->with([NAN, INF, -INF]);

it('rounds to two decimals', function (): void {
    expect(Gpa::fromFloat(4.567)->value())->toBe(4.57);
});

it('compares against a minimum', function (): void {
    expect(Gpa::fromFloat(4.50)->meets(Gpa::fromFloat(4.50)))->toBeTrue()
        ->and(Gpa::fromFloat(4.49)->meets(Gpa::fromFloat(4.50)))->toBeFalse();
});

it('reads decimal strings from the database', function (): void {
    expect(Gpa::fromDatabase('4.25')->value())->toBe(4.25);
});

it('rejects non numeric database values', function (): void {
    expect(fn () => Gpa::fromDatabase('not-a-number'))->toThrow(InvalidGpa::class);
});
```

**لماذا `NAN` في مجموعة الاختبار؟** لأن `NAN < 0` و`NAN > 5` كلاهما `false`، فأي تنفيذ يعتمد على المقارنات وحدها سيقبلها. الاختبار يثبت أن `is_finite` موجودة فعلاً.

### ٢٧.٢ اختبار آلة الحالات (وحدة)

```php
<?php

declare(strict_types=1);

use Admissions\Domain\Model\ApplicationStatus;

/**
 * جدول الحقيقة الكامل: كل حالة مصدر × كل حالة هدف.
 */
it('knows exactly which transitions are allowed', function (
    ApplicationStatus $from,
    ApplicationStatus $to,
    bool $allowed,
): void {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    [ApplicationStatus::Draft, ApplicationStatus::Submitted, true],
    [ApplicationStatus::Draft, ApplicationStatus::Accepted, false],
    [ApplicationStatus::Draft, ApplicationStatus::Rejected, false],
    [ApplicationStatus::Draft, ApplicationStatus::Withdrawn, false],
    [ApplicationStatus::Submitted, ApplicationStatus::Accepted, true],
    [ApplicationStatus::Submitted, ApplicationStatus::Rejected, true],
    [ApplicationStatus::Submitted, ApplicationStatus::Withdrawn, true],
    [ApplicationStatus::Submitted, ApplicationStatus::Draft, false],
    [ApplicationStatus::Accepted, ApplicationStatus::Withdrawn, true],
    [ApplicationStatus::Accepted, ApplicationStatus::Rejected, false],
    [ApplicationStatus::Rejected, ApplicationStatus::Accepted, false],
    [ApplicationStatus::Withdrawn, ApplicationStatus::Submitted, false],
]);
```

### ٢٧.٣ اختبار تجمّع الطلب (وحدة، بلا قاعدة بيانات)

```php
<?php

declare(strict_types=1);

use Admissions\Domain\Exception\GpaBelowMinimum;
use Admissions\Domain\Exception\InvalidTransition;
use Admissions\Domain\Exception\RejectionReasonRequired;
use Admissions\Domain\Exception\ReservationMismatch;
use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\AdmissionRequirements;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\ApplicationStatus;
use Admissions\Domain\Model\Event\ApplicationAccepted;
use Admissions\Domain\Model\Event\ApplicationSubmitted;
use Admissions\Domain\Model\Gpa;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\SeatReservation;
use Admissions\Domain\Model\StudentId;
use Illuminate\Support\Str;

function draftApplication(float $gpa = 4.5): AdmissionApplication
{
    return AdmissionApplication::draft(
        ApplicationId::fromString((string) Str::ulid()),
        StudentId::fromString('1001'),
        ProgramId::fromString((string) Str::ulid()),
        Gpa::fromFloat($gpa),
    );
}

function requirementsFor(AdmissionApplication $application, float $minimum = 4.0): AdmissionRequirements
{
    return new AdmissionRequirements(
        $application->programId(),
        'Computer Science',
        Gpa::fromFloat($minimum),
    );
}

it('submits a draft when the gpa meets the minimum', function (): void {
    $application = draftApplication(4.5);
    $at = new DateTimeImmutable('2026-01-10 09:00:00');

    $application->submit(requirementsFor($application, 4.0), $at);

    expect($application->status())->toBe(ApplicationStatus::Submitted);

    $events = $application->releaseDomainEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ApplicationSubmitted::class)
        ->and($events[0]->occurredAt)->toEqual($at);
});

it('rejects a submission below the minimum and records no event', function (): void {
    $application = draftApplication(3.9);

    expect(fn () => $application->submit(requirementsFor($application, 4.0), new DateTimeImmutable()))
        ->toThrow(GpaBelowMinimum::class);

    expect($application->status())->toBe(ApplicationStatus::Draft)
        ->and($application->releaseDomainEvents())->toBe([]);
});

it('cannot submit twice', function (): void {
    $application = draftApplication();
    $requirements = requirementsFor($application);

    $application->submit($requirements, new DateTimeImmutable());

    expect(fn () => $application->submit($requirements, new DateTimeImmutable()))
        ->toThrow(InvalidTransition::class);

    expect($application->releaseDomainEvents())->toHaveCount(1);
});

it('accepts only with a reservation for the same program', function (): void {
    $application = draftApplication();
    $application->submit(requirementsFor($application), new DateTimeImmutable());

    $foreignReservation = SeatReservation::issue(
        ProgramId::fromString((string) Str::ulid()),
        new DateTimeImmutable(),
    );

    expect(fn () => $application->accept($foreignReservation, new DateTimeImmutable()))
        ->toThrow(ReservationMismatch::class);

    $matching = SeatReservation::issue($application->programId(), new DateTimeImmutable());
    $application->accept($matching, new DateTimeImmutable());

    expect($application->status())->toBe(ApplicationStatus::Accepted)
        ->and($application->releaseDomainEvents())->toHaveCount(2); // submitted + accepted
});
```

**ماذا يثبت هذا الاختبار؟**

- لا قاعدة بيانات، لا HTTP، لا طابور. سرعة أجزاء من الثانية.
- القواعد (المعدل، التكرار، تطابق الحجز) مختبرة عند مصدرها.
- الأحداث تُفحص كجزء من العقد، لا كأثر جانبي غامض.

### ٢٧.٤ اختبار خدمة القرار (وحدة)

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
 * مساعد مشترك لاختبارات خدمة القرار (ضعه في tests/Pest.php).
 */
function submittedApplicationFor(Program $program, float $gpa = 4.5): AdmissionApplication
{
    $application = AdmissionApplication::draft(
        ApplicationId::fromString((string) Str::ulid()),
        StudentId::fromString('1001'),
        $program->id(),
        Gpa::fromFloat($gpa),
    );

    $application->submit($program->requirements(), new DateTimeImmutable());

    return $application;
}

it('accepts an application and reserves exactly one seat', function (): void {
    $program = Program::open(ProgramId::fromString((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 2);
    $application = submittedApplicationFor($program); // helper في tests/Pest.php

    (new AdmissionDecisionService())->accept($application, $program, new DateTimeImmutable());

    expect($application->status())->toBe(ApplicationStatus::Accepted)
        ->and($program->reservedSeats())->toBe(1)
        ->and($program->availableSeats())->toBe(1);
});

it('does not touch the application when the program is full', function (): void {
    $program = Program::open(ProgramId::fromString((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 1);
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
    $program = Program::open(ProgramId::fromString((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 1);
    $application = submittedApplicationFor($program);

    $service = new AdmissionDecisionService();
    $service->accept($application, $program, new DateTimeImmutable());
    $service->withdraw($application, $program, new DateTimeImmutable());

    expect($application->status())->toBe(ApplicationStatus::Withdrawn)
        ->and($program->reservedSeats())->toBe(0)
        ->and($program->availableSeats())->toBe(1);
});

it('does not release a seat when a submitted application withdraws', function (): void {
    $program = Program::open(ProgramId::fromString((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 1);
    $application = submittedApplicationFor($program);

    (new AdmissionDecisionService())->withdraw($application, $program, new DateTimeImmutable());

    expect($program->reservedSeats())->toBe(0);
});
```

**الاختبار الثاني هو الأهم:** يثبت أن الفشل لا يترك الطلب في حالة نصف مقبول. هذا ما كان غائباً في النموذج الأصلي.

### ٢٧.٥ اختبار المستودع (تكامل مع قاعدة بيانات)

```php
<?php

declare(strict_types=1);

use Admissions\Domain\Exception\AlreadyApplied;
use Admissions\Domain\Model\AdmissionApplication;
use Admissions\Domain\Model\ApplicationId;
use Admissions\Domain\Model\Gpa;
use Admissions\Domain\Model\Program;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Model\StudentId;
use Admissions\Domain\Repository\AdmissionApplicationRepository;
use Admissions\Domain\Repository\ProgramRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('round-trips a program through the database', function (): void {
    $program = Program::open(ProgramId::fromString((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 10);

    app(ProgramRepository::class)->save($program);

    $loaded = app(ProgramRepository::class)->find($program->id());

    expect($loaded)->not->toBeNull()
        ->and($loaded->name())->toBe('CS')
        ->and($loaded->capacity())->toBe(10)
        ->and($loaded->reservedSeats())->toBe(0)
        ->and($loaded->requirements()->minimumGpa()->value())->toBe(4.0);
});

it('enforces one application per student per program', function (): void {
    $program = Program::open(ProgramId::fromString((string) Str::ulid()), 'CS', Gpa::fromFloat(4.0), 10);
    app(ProgramRepository::class)->save($program);

    $studentId = StudentId::fromString('1001');

    $make = fn (): AdmissionApplication => AdmissionApplication::draft(
        ApplicationId::fromString((string) Str::ulid()),
        $studentId,
        $program->id(),
        Gpa::fromFloat(4.5),
    );

    app(AdmissionApplicationRepository::class)->save($make());

    expect(fn () => app(AdmissionApplicationRepository::class)->save($make()))
        ->toThrow(AlreadyApplied::class);
});
```

> **ملاحظة:** هذا الاختبار يحتاج مستخدماً موجوداً بسبب المفتاح الخارجي `student_id`. أضف `User::factory()->create(['id' => 1001])` أو استخدم معرّف المستخدم الفعلي.

### ٢٧.٦ اختبار الميزة (HTTP)

```php
<?php

declare(strict_types=1);

use Admissions\Domain\Model\Gpa;
use Admissions\Domain\Model\Program;
use Admissions\Domain\Model\ProgramId;
use Admissions\Domain\Repository\ProgramRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->program = Program::open(
        ProgramId::fromString((string) Str::ulid()),
        'Computer Science',
        Gpa::fromFloat(4.0),
        5,
    );

    app(ProgramRepository::class)->save($this->program);
});

it('submits an application', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/applications', [
            'program_id' => $this->program->id()->value(),
            'gpa' => 4.5,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.program.name', 'Computer Science');
});

it('rejects an out-of-range gpa before it reaches the domain', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/applications', [
            'program_id' => $this->program->id()->value(),
            'gpa' => 9,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('gpa');
});

it('returns 422 when the gpa is below the program minimum', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/applications', [
            'program_id' => $this->program->id()->value(),
            'gpa' => 3.5,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'gpa_below_minimum');
});

it('returns 403 when a regular user tries to accept', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/applications/01J0000000000000000000000/accept')
        ->assertForbidden();
});

it('returns 409 when accepting without available seats', function (): void {
    $officer = User::factory()->create(['role' => 'admissions_officer']);

    // ... إنشاء طلب، ملء المقاعد، ثم محاولة قبول
    // التوقع: 409 مع code = no_seats_available
});

it('is idempotent at the notification level', function (): void {
    // بعد قبول الطلب مرتين على مستوى الإشعار (إعادة تشغيل الوظيفة):
    // يجب ألا يُرسل البريد مرتين.
    Mail::fake();
    // ... تشغيل المستمع مرتين، ثم Mail::assertSent(AcceptanceMail::class, 1);
});
```

**ملاحظات على اختبارات الميزة:**

- `assertJsonPath('data.status', ...)` يفترض أن المورد يُغلَّف في `data`. `JsonResource` يفعل ذلك افتراضياً.
- اختبار 409 يحتاج ترتيباً: إنشاء طلبين، قبول الأول، ثم محاولة قبول الثاني على برنامج بسعة ١.
- اختبار الإشعار يتطلب `Queue::fake()` + `Mail::fake()` وتشغيل المستمع يدوياً، أو `Bus::fake`.

### ٢٧.٧ اختبارات العمارة

```php
<?php

declare(strict_types=1);

arch('domain is framework-free')
    ->expect('Admissions\Domain')
    ->not->toUse([
        'Illuminate',
        'Laravel',
        'Symfony',
        'App',
        'Admissions\Application',
        'Admissions\Infrastructure',
        'Admissions\Presentation',
    ]);

arch('application does not depend on infrastructure')
    ->expect('Admissions\Application')
    ->not->toUse([
        'Admissions\Infrastructure',
        'Admissions\Presentation',
        'Illuminate\Http',
        'Illuminate\Support\Facades',
    ]);

arch('presentation does not touch persistence')
    ->expect('Admissions\Presentation')
    ->not->toUse([
        'Admissions\Infrastructure',
        'Admissions\Domain\Repository',
        'Illuminate\Support\Facades\DB',
    ]);

arch('notifications does not use admissions persistence')
    ->expect('Notifications')
    ->not->toUse([
        'Admissions\Infrastructure',
        'Admissions\Domain\Model',
    ]);

arch('all source files declare strict types')
    ->expect('Admissions')
    ->toUseStrictTypes();
```

**لماذا `Admissions\Domain\Model` ممنوع في Notifications؟** لأن الإشعارات تحتاج **معرّفات** فقط (`StudentId`, `ApplicationId`, `ProgramId`). هذه تقع في `Admissions\Domain\Model`، فالمنع أعلاه **غير عملي** كما هو مكتوب. الصياغة الأدق:

```php
arch('notifications does not use admissions aggregates')
    ->expect('Notifications')
    ->not->toUse([
        'Admissions\Domain\Model\AdmissionApplication',
        'Admissions\Domain\Model\Program',
        'Admissions\Domain\Service',
        'Admissions\Domain\Repository',
        'Admissions\Infrastructure',
    ]);
```

هذا يسمح بالمعرّفات المشتركة (عقد الحدث) ويمنع الوصول إلى التجمّعات.

**ملاحظة على صيغة `arch()`:** السلاسل تُطابق بادئات مساحات الأسماء في Pest 2/3. راجع توثيق الإصدار الذي تستخدمه، وابدأ بقاعدة واحدة ثم وسّعها؛ اختبارات العمارة الصارمة جداً تولّد ضجيجاً وتُعطَّل.


---

## ٢٨. كيف تشغّل هذا البناء؟

### ٢٨.١ من الصفر

```bash
# 1) مشروع لارافل جديد
composer create-project laravel/laravel admissions
cd admissions

# 2) أنشئ شجرة src/ كما في القسم ٣

# 3) أضف مساحات الأسماء في composer.json (القسم ٢٥.١)
composer dump-autoload

# 4) سجّل المزوّدين في bootstrap/providers.php

# 5) أضف مسارات api في bootstrap/app.php

# 6) انسخ ملفات الترحيل إلى database/migrations
php artisan migrate

# 7) ثبّت Sanctum (إن لم يكن مثبتاً)
php artisan install:api

# 8) شغّل الاختبارات
php artisan test
```

### ٢٨.٢ بيانات أولية

```php
<?php

// database/seeders/ProgramSeeder.php
namespace Database\Seeders;

use Admissions\Application\UseCase\OpenProgram;
use Admissions\Application\UseCase\OpenProgramHandler;
use Illuminate\Database\Seeder;

final class ProgramSeeder extends Seeder
{
    public function run(OpenProgramHandler $handler): void
    {
        foreach ([
            ['Computer Science', 4.00, 120],
            ['Software Engineering', 3.75, 80],
            ['Data Science', 4.25, 60],
        ] as [$name, $gpa, $capacity]) {
            $handler(new OpenProgram($name, $gpa, $capacity));
        }
    }
}
```

لاحظ أن بيانات البداية تمر عبر حالة الاستخدام نفسها التي تمر بها الواجهة. لا كتابة مباشرة إلى الجداول، ولا التفاف على القواعد.

### ٢٨.٣ المتغيرات البيئية المهمة

```dotenv
DB_CONNECTION=mysql
QUEUE_CONNECTION=redis        # ليس sync في الإنتاج
MAIL_MAILER=smtp
```

### ٢٨.٤ تشغيل العامل

```bash
php artisan queue:work --tries=5 --backoff=10,30,60
```

بدون عامل، تُدفع الإشعارات إلى الطابور ولا تُرسل. هذا **ليس خطأً في الكود**؛ إنه شرط تشغيل يجب أن يكون في خطة النشر ومراقبته.

---

## ٢٩. جدول التحويل: من النموذج القديم إلى البناء الجديد

| النموذج القديم | البناء الجديد | لماذا |
|---|---|---|
| `src/Domain/Admissions/Models/AdmissionApplication.php` (Eloquent) | `src/Admissions/Domain/Model/AdmissionApplication.php` (PHP نقي) | المجال لا يعرف الإطار |
| نفس الملف: `save()` داخل الكيان | الحفظ في المعالج عبر المستودع | فصل الحالة عن التخزين |
| `Program::reserveSeat(): void` | `Program::reserveSeat(): SeatReservation` | دليل يمنع قبولاً بلا مقعد |
| `AdmissionApplication::accept()` بلا وسيط | `accept(SeatReservation $reservation, ...)` | الثابت المركّب محمي |
| `AcceptApplicationAction` (تنسيق + قواعد) | `AdmissionDecisionService` (قواعد) + `AcceptApplicationHandler` (تنسيق) | فصل المجال عن التطبيق |
| `ApplicationStatus` (4 حالات) | 5 حالات مع `Withdrawn` | دورة حياة واقعية |
| `Gpa` بـ `InvalidArgumentException` | `Gpa` + `InvalidGpa extends AdmissionsException` | أخطاء موحّدة قابلة للتحويل |
| `ApplicationAccepted` يحمل الموديل | يحمل معرّفات + وقت | لا تسلسل موديلات، لا تسريب جدول |
| `dispatch()` من الكيان | جامع + وحدة عمل تنشر بعد الـ commit | لا إشعار كاذب |
| `withEvents(discover: [...])` | `Event::listen()` صريح | لا فشل صامت |
| `App\Models\User` في المستمع | `AdmissionsDirectory` port | عزل السياقات |
| `Mail::send` داخل المستمع | حالة استخدام + `AcceptanceMailer` port + idempotency | قابل للاختبار، لا تكرار |
| `$guarded = []` | سجلات تخزين + `forceFill` من المابّر | لا تعبئة جماعية |
| `403` غير موجود | Gates للقرار والإدارة والملكية | أمان فعلي |
| `DomainException → 422` للكل | خريطة أكواد: 404/409/422/500 | دلالة HTTP صحيحة |
| 3 اختبارات | وحدة + تكامل + ميزة + عمارة | حماية من الانحراف |
| لا قيود قاعدة بيانات | `unique`, `check`, `index`, `foreign` | الحقيقة في مكانين |
| لا `strict_types` | إعلان في كل ملف | لا تحويلات ضمنية |

---

## ٣٠. مقارنة القرارات: النموذج القديم مقابل البناء النظيف

| السؤال | النموذج القديم | البناء الجديد |
|---|---|---|
| أين تعيش قاعدة المعدل؟ | `submit()` على الموديل | `submit()` على الكيان + `Gpa` |
| أين تعيش قاعدة المقاعد؟ | `reserveSeat()` على الموديل | `Program::reserveSeat()` + `SeatReservation` |
| من يضمن الترتيب بينهما؟ | لا أحد | `AdmissionDecisionService` |
| من يفتح المعاملة؟ | الـ Action | `UnitOfWork` عبر منفذ |
| من يقفل الصفوف؟ | الـ Action مباشرة | المستودع عبر `findForUpdate` |
| من ينشر الأحداث؟ | الكيان | وحدة العمل بعد الـ commit |
| كيف تُختبر القواعد؟ | تحتاج قاعدة بيانات | وحدات نقية |
| ماذا لو تغيّر التخزين؟ | تعديل الموديلات | استبدال محوّل المستودع |
| ماذا لو أُضيف سياق ثانٍ؟ | يشارك نفس الموديلات | سياق مستقل بمنافذه |
| كيف نمنع الانحراف؟ | مراجعة بشرية | اختبارات عمارة |

---

## ٣١. الثمن: متى يكون هذا البناء مبالغة؟

البناء النظيف ليس مجانياً. الثمن الحقيقي:

| التكلفة | التفصيل |
|---|---|
| ملفات أكثر | من ٨ ملفات في النموذج إلى ~٤٥ ملفاً |
| تحويلات صريحة | Mappers بين كل كيان وسجل |
| منحنى تعلّم | فريق لارافل معتاد على `Model::find()` يحتاج وقتاً |
| استعلامات أقل مباشرة | لا `$app->program->name`؛ تحتاج Join في نموذج القراءة |
| اختبارات أكثر | اختبارات العمارة والمابّرات تُضاف إلى الاختبارات الوظيفية |
| أدوات | PHPStan وRector يجب أن تعرف `src/` |

### متى **لا** تستخدمه؟

- مشروع CRUD بسيط (لوحة إدارة، مدونة، كتالوج) بلا قواعد عمل متشابكة.
- فريق صغير جداً بميزانية ضيقة، والمجال لن يتغيّر كثيراً.
- نموذج أولي تريد التحقق منه في أسبوع.
- نظام ستستبدله بعد ستة أشهر.

### متى **تستخدمه**؟

- قواعد عمل تتغيّر وتتزاحم (قبول، فوترة، حجوزات، تأمين).
- عمر النظام سنوات، وتكلفة التغيير عالية.
- أكثر من واجهة (API + CLI + طابور + لوحة).
- حاجة إلى اختبار القواعد بسرعة وثبات.
- فريق يزيد حجمه، وتحتاج حدوداً تمنع الفوضى.

### الوسط الذكي

لا تبنِ كل السياقات بنفس الصرامة:

- **Core Domain** (القبول): البناء الكامل.
- **Supporting** (إدارة البرامج، التقارير): Eloquent + Actions بسيطة.
- **Generic** (الإشعارات، الدفع): حزمة جاهزة أو خدمة خارجية.

هذا هو DDD الاستراتيجي في أبسط صوره: **استثمر حيث يميزك العمل، واختر البساطة في الباقي.**

---

## ٣٢. التوسعات: ما الذي يُضاف قبل الإنتاج؟

### ٣٢.١ Outbox Pattern — لضمان عدم فقدان الأحداث

المشكلة: بعد الـ commit، قد يتعطّل التطبيق قبل إنشاء وظيفة الطابور.

الحل:

```sql
CREATE TABLE outbox (
    id CHAR(26) PRIMARY KEY,
    event_type VARCHAR(120) NOT NULL,
    payload JSON NOT NULL,
    occurred_at TIMESTAMP NOT NULL,
    published_at TIMESTAMP NULL,
    attempts INT NOT NULL DEFAULT 0,
    INDEX idx_outbox_unpublished (published_at, occurred_at)
);
```

داخل وحدة العمل:

```php
public function run(callable $work): mixed
{
    return $this->transactions->transactional(function () use ($work) {
        $result = $work();

        foreach ($this->collector->release() as $event) {
            $this->outbox->store($event);   // نفس المعاملة
        }

        return $result;
    });
}
```

ثم أمر مجدول:

```bash
php artisan outbox:publish --limit=500
```

يفتح الدفعة، ينشر، يعلّم `published_at`. إذا تعطّل التطبيق، يبقى الصف ويُنشر لاحقاً.

**الثمن:** جدول إضافي، أمر مجدول، وتكرار ممكن (يُعالج بـ idempotency).

### ٣٢.٢ مفاتيح Idempotency على مستوى HTTP

```php
Route::post('/applications', ...)
    ->middleware(['throttle:applications', 'idempotent']);
```

middleware يقرأ `Idempotency-Key`، ويخزّن الاستجابة في `idempotency_keys` مع مفتاح فريد. إعادة الإرسال تعيد نفس الاستجابة بلا تنفيذ جديد.

### ٣٢.٣ سجل تدقيق

كل حدث مجال يمكن أن يُسجَّل في `audit_log` عبر مستمع عام:

```php
final readonly class AuditDomainEvents implements ShouldQueue
{
    public function handle(object $event): void
    {
        if (! $event instanceof DomainEvent) {
            return;
        }

        DB::table('audit_log')->insert([
            'event' => $event::class,
            'payload' => json_encode($event, JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);
    }
}
```

مع تسجيل صريح لكل حدث تريد تدقيقه (لا `*` عام على كل شيء).

### ٣٢.٤ نموذج قراءة منفصل (CQRS خفيف)

- اكتب في MySQL.
- اقرأ من جدول/عرض مُسطَّح يُحدَّث بالأحداث.
- يفيد عندما تكون التقارير الثقيلة على نفس الجداول.

لا تبدأ به. ابدأ بنموذج قراءة استعلامي كما في القسم ١٦، وانتقل إليه فقط إذا أثبت القياس أنك تحتاجه.

---

## ٣٣. قائمة تحقق قبل الإنتاج

- [ ] كل قاعدة عمل لها مكان واحد واضح.
- [ ] لا `Illuminate` في `src/*/Domain`.
- [ ] لا وصول إلى `Infrastructure` من `Application` أو `Presentation`.
- [ ] كل انتقال حالة مختبر (جدول حقيقة كامل).
- [ ] كل قبول يمر عبر `AdmissionDecisionService`.
- [ ] كل معاملة تُغلق قبل نشر الأحداث.
- [ ] `DomainEventCollector` مسجّل `scoped` لا `singleton`.
- [ ] المستمعون مسجّلون صراحة ومختبرون.
- [ ] الطابور يعمل في بيئة النشر (عامل + مراقبة).
- [ ] الإشعارات idempotent.
- [ ] Gates مطبقة ومختبرة (403).
- [ ] قيود قاعدة البيانات موجودة ومختبرة على نفس المحرك.
- [ ] لا `$guarded = []` على موديل يُملأ من HTTP.
- [ ] الاستجابات عبر Resources.
- [ ] خريطة أخطاء موحّدة (404/409/422/500).
- [ ] `strict_types` في كل ملف.
- [ ] اختبارات عمارة تعمل في CI.
- [ ] `event:cache` غير مستخدم أو محدّث في النشر.
- [ ] خطة تراجع (rollback) للترحيلات.
- [ ] مراقبة: نسبة فشل الوظائف، زمن الاستجابة، أخطاء 5xx.

---

## ٣٤. حدود هذا البناء (بصراحة)

1. **لا يمنع التحايل البرمجي.** PHP لا تملك `friend classes`؛ يمكن لمالك الكود استدعاء `accept()` مباشرة. الحماية هي تصميم + اختبارات + مراجعة.
2. **الاختبارات على SQLite لا تثبت التزامن.** `lockForUpdate` يُتجاهل. اختبر الجمود والقفل على MySQL/PostgreSQL في CI.
3. **الأحداث بعد الـ commit قابلة للفقدان.** الحل الكامل هو Outbox، وهو موثّق كتوسعة لا كجزء أساسي.
4. **نموذج القراءة يستعلم نفس القاعدة.** هذا ليس CQRS كاملاً؛ هو فصل مسؤوليات فقط.
5. **الكيانات تُحمَّل كاملة.** لا يوجد تحميل جزئي أو Lazy Loading في المجال. إن كان التجمّع ضخماً، أعد تقسيمه قبل أن تضيف تحسينات.
6. **`reconstitute` يثق بالبيانات بعد فحص أساسي.** أي فساد في القاعدة سيظهر كـ `LogicException` وقت التحميل، لا كتصحيح صامت.
7. **لا توجد إدارة مستخدمين أو أدوار في هذا البناء.** `hasRole()` افتراضية؛ استبدلها بنظام صلاحياتك.
8. **التوقيت الزمني UTC.** التواريخ تُخزَّن UTC وتُعرض بتوقيت المستخدم؛ لم أضف طبقة عرض زمنية.

---

## ٣٥. الخلاصة

البناء النظيف ليس «ملفات أكثر». هو **حدود تجعل التغيير رخيصاً**:

- تريد تغيير قاعدة المقاعد؟ مكانها `Program`.
- تريد تغيير من يُقبل؟ مكانها `AdmissionDecisionService`.
- تريد تغيير كيف يُحفظ؟ المستودع.
- تريد تغيير كيف يُرسل الإشعار؟ المحوّل.
- تريد تغيير كيف يُعرض؟ المورد.
- تريد تغيير كيف يُترجم الخطأ؟ `DomainExceptionStatus`.

كل سؤال من هذه له **ملف واحد** للفتح. وهذا هو المقياس الحقيقي للهيكلية النظيفة: لا «كم هي جميلة»، بل **كم يكلّف تغيير متوقع**.

> **آخر نصيحة:** لا تنقل هذا البناء كاملاً إلى مشروع قائم دفعة واحدة. ابدأ بسياق واحد (القبول)، وطبّق الحدود تدريجياً. أول ما تكسبه ليس الجمال، بل **القدرة على اختبار القواعد بسرعة** — وهذا وحده يغيّر طريقة تطويرك.

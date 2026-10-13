# Domain-Driven Design في Laravel 13

شرح عملي مبني على: كتاب Eric Evans الأصلي (2003)، وVaughn Vernon (Implementing DDD)، وكتاب Spatie «Laravel Beyond CRUD» (النهج الأشهر لتطبيق DDD في لارافل)، وملاحظات إصدار Laravel 13 (صدر 17 مارس 2026، يتطلب PHP 8.3+).

---

## ١. ما هو DDD؟

DDD منهج لتصميم البرمجيات المعقدة يضع **منطق العمل (Domain)** في مركز التطبيق، لا قاعدة البيانات ولا الـ Framework. الفكرة: الكود يتكلم بنفس لغة خبراء المجال (Ubiquitous Language). مثلاً في مشروع التقديم الجامعي: «طلب قبول» و«مقعد» و«فترة تسجيل»، وليس `records` و`rows`.

مهم: DDD **ليس** هيكل مجلدات ولا Package. هو طريقة تفكير، والمجلدات مجرد نتيجة لها.

## ٢. متى تستخدمه ومتى لا؟

> **استخدمه** إذا كان عندك قواعد عمل معقدة تتغير (فوترة، حجوزات، قبول جامعي، سير موافقات)، وفريق يتكلم مع خبراء مجال، ونظام عمره طويل.
>
> **لا تستخدمه** مع CRUD بسيط أو لوحة إدارة عادية؛ يصير تعقيد بلا فائدة. المنطقي: طبّق DDD على الأجزاء المعقدة فقط (Core Domain) وخلّ الباقي Laravel عادي.

## ٣. DDD الاستراتيجي (الأهم وغالباً يُهمل)

| المفهوم | المعنى | مثال |
|---|---|---|
| Ubiquitous Language | مصطلحات موحدة بين المطورين والعمل، تظهر في أسماء الكلاسات | `AdmissionApplication::submit()` |
| Bounded Context | حدود يكون فيها المصطلح له معنى واحد فقط | «Student» في التسجيل ≠ «Student» في الفوترة |
| Subdomain | Core (ميزتك التنافسية) / Supporting / Generic (مثل الإشعارات، الدفع) | الإشعارات = Generic، استخدم حزمة جاهزة |
| Context Map | كيف تتواصل الـ Contexts (Events، API، Anti-Corruption Layer) | Admissions يرسل `ApplicationAccepted` لـ Notifications |

في لارافل غالباً كل Bounded Context يصير مجلداً (أو Module) داخل `src/Domain`، والتواصل بينها عبر Events أو واجهات (Interfaces) وليس باستدعاء Models بعضها مباشرة.

## ٤. DDD التكتيكي: اللبنات

| اللبنة | الوصف | مقابلها في Laravel |
|---|---|---|
| Entity | كائن له هوية (ID) وتتغير حالته | Eloquent Model |
| Value Object | بلا هوية، غير قابل للتغيير، يُقارن بقيمته | `readonly class`، أو Cast مخصص |
| Aggregate / Root | مجموعة كائنات تُعدَّل كوحدة واحدة عبر الـ Root فقط، وتحمي الـ Invariants | Model رئيسي + علاقاته (Invoice + InvoiceLines) |
| Domain Service | منطق لا يخص كياناً واحداً | كلاس خدمة بسيط |
| Domain Event | حدث حصل في المجال (ماضي) | Laravel Events |
| Repository | واجهة تحفظ وتجلب الـ Aggregates | Interface + تنفيذ Eloquent (اختياري) |
| Application Service / Action | يُنسّق حالة استخدام واحدة | Action class أو Job |

> **نقطة جدل مهمة:** Eloquent هو Active Record أي يخلط المنطق مع الحفظ، وهذا يخالف DDD «النقي». هناك مدرستان:
> (أ) **براغماتية** (Spatie): تقبل Eloquent داخل الـ Domain وتنقل المنطق إلى Actions وQuery Builders وCollections مخصصة.
> (ب) **نقية**: كيانات PHP عادية + Repositories + Mappers.
> الأولى هي الأنسب لأغلب مشاريع لارافل، والثانية فقط لو المجال معقد جداً وتبي عزلاً كاملاً.

## ٥. هيكل المشروع

الطريقة الشائعة: تبقي `app/` كما هو (طبقة التطبيق: Controllers وRequests)، وتضيف `src/Domain` للمجال. نسجّل الـ Namespace في `composer.json` ثم نشغّل `composer dump-autoload`.

```text
app/                      # طبقة التطبيق (HTTP, Console, Providers)
src/
  Domain/
    Admissions/           # Bounded Context
      Actions/            # حالات الاستخدام: SubmitApplicationAction
      DataTransferObjects/
      Models/             # Eloquent (Entities)
      QueryBuilders/
      Collections/
      Enums/              # حالات (States)
      Events/
      Exceptions/
      ValueObjects/
    Notifications/
  Support/                # أدوات عامة غير مرتبطة بمجال
```

```json
"autoload": {
  "psr-4": {
    "App\\": "app/",
    "Domain\\": "src/Domain/"
  }
}
```

**القاعدة الذهبية للاعتماديات:** الـ Domain لا يعرف شيئاً عن HTTP أو Request أو Controller. الاتجاه دائماً من الخارج للداخل: Controller ← Action ← Domain.

## ٦. التطبيق بالكود (مثال: نظام قبول جامعي)

### ١) Value Object

يغلّف قيمة مع قواعدها. غير قابل للتعديل، ويتحقق من صحته عند الإنشاء فلا يمكن وجود قيمة خاطئة.

```php
<?php
namespace Domain\Admissions\ValueObjects;

use InvalidArgumentException;

final readonly class Gpa
{
    public function __construct(public float $value)
    {
        if ($value < 0 || $value > 5) {
            throw new InvalidArgumentException('GPA must be between 0 and 5');
        }
    }

    public function meets(self $minimum): bool
    {
        return $this->value >= $minimum->value;
    }
}
```

### ٢) Enum للحالة

الحالات والانتقالات المسموحة تعيش في المجال، لا في الـ Controller.

```php
<?php
namespace Domain\Admissions\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => $next === self::Submitted,
            self::Submitted => in_array($next, [self::Accepted, self::Rejected]),
            default => false,
        };
    }
}
```

### ٣) الـ Entity (Aggregate Root) بسلوك حقيقي

لاحظ: الموديل يحمي قواعده بنفسه (`submit()`) بدل ما نغيّر `status` من أي مكان. وفي Laravel 13 يمكن تعريف إعدادات الموديل بـ PHP Attributes بدل خصائص `protected` (راجع قسم Laravel 13).

```php
<?php
namespace Domain\Admissions\Models;

use Domain\Admissions\Enums\ApplicationStatus;
use Domain\Admissions\Events\ApplicationSubmitted;
use Domain\Admissions\Exceptions\InvalidTransition;
use Illuminate\Database\Eloquent\Model;

class AdmissionApplication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => ApplicationStatus::class];
    }

    // سلوك المجال: القاعدة في مكان واحد
    public function submit(): void
    {
        $this->transitionTo(ApplicationStatus::Submitted);
        $this->save();

        ApplicationSubmitted::dispatch($this);
    }

    private function transitionTo(ApplicationStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw InvalidTransition::from($this->status, $next);
        }
        $this->status = $next;
    }
}
```

### ٤) DTO + Action (حالة استخدام)

الـ DTO ينقل بيانات مُتحقَّق منها إلى المجال بدل تمرير `Request` أو مصفوفات. الـ Action كلاس واحد = مهمة واحدة، وهو مكان التنسيق.

```php
<?php
namespace Domain\Admissions\DataTransferObjects;

final readonly class SubmitApplicationData
{
    public function __construct(
        public int $studentId,
        public int $programId,
        public float $gpa,
    ) {}
}

// ---------------------------------------------
namespace Domain\Admissions\Actions;

use Domain\Admissions\DataTransferObjects\SubmitApplicationData;
use Domain\Admissions\Models\AdmissionApplication;
use Domain\Admissions\ValueObjects\Gpa;
use Illuminate\Support\Facades\DB;

final class SubmitApplicationAction
{
    public function execute(SubmitApplicationData $data): AdmissionApplication
    {
        return DB::transaction(function () use ($data) {
            $application = AdmissionApplication::create([
                'student_id' => $data->studentId,
                'program_id' => $data->programId,
                'gpa'        => (new Gpa($data->gpa))->value,
                'status'     => 'draft',
            ]);

            $application->submit();

            return $application;
        });
    }
}
```

### ٥) Controller رفيع

```php
<?php
namespace App\Http\Controllers;

use App\Http\Requests\SubmitApplicationRequest;
use Domain\Admissions\Actions\SubmitApplicationAction;
use Domain\Admissions\DataTransferObjects\SubmitApplicationData;

class ApplicationController extends Controller
{
    public function store(
        SubmitApplicationRequest $request,
        SubmitApplicationAction $submit,
    ) {
        $application = $submit->execute(new SubmitApplicationData(
            studentId: $request->user()->id,
            programId: $request->integer('program_id'),
            gpa: $request->float('gpa'),
        ));

        return response()->json($application, 201);
    }
}
```

### ٦) Domain Event بين Contexts

سياق الإشعارات يستمع للحدث بدون أن يعرف Admissions شيئاً عنه. هذا هو التفكيك (Decoupling) الحقيقي. لارافل تكتشف الـ Listeners تلقائياً من نوع الـ Type-hint في `handle()`، وللعمل الثقيل استخدم `ShouldQueue`.

```php
<?php
namespace Domain\Admissions\Events;

use Domain\Admissions\Models\AdmissionApplication;
use Illuminate\Foundation\Events\Dispatchable;

final class ApplicationSubmitted
{
    use Dispatchable;

    public function __construct(public AdmissionApplication $application) {}
}

// ---------------------------------------------
namespace Domain\Notifications\Listeners;

use Domain\Admissions\Events\ApplicationSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyStudentOfSubmission implements ShouldQueue
{
    public function handle(ApplicationSubmitted $event): void
    {
        // أرسل إشعاراً للطالب
    }
}
```

### ٧) Repository (اختياري، استخدمه عند الحاجة فقط)

مفيد عند عزل المجال عن مصدر البيانات (API خارجي، أو تبديل التخزين). لو كل ما يفعله تغليف `Model::find()` فهو زيادة تعقيد. وعادةً Query Builder مخصص أنظف.

```php
<?php
namespace Domain\Admissions\Repositories;

use Domain\Admissions\Models\AdmissionApplication;

interface ApplicationRepository
{
    public function findOrFail(int $id): AdmissionApplication;
    public function save(AdmissionApplication $application): void;
}

// ---------------------------------------------
namespace App\Providers;

use Domain\Admissions\Repositories\ApplicationRepository;
use Infrastructure\Eloquent\EloquentApplicationRepository;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ApplicationRepository::class, EloquentApplicationRepository::class);
    }
}
// وسجّل المزوّد في bootstrap/providers.php
```

### ٨) Query Builder مخصص (بديل عملي)

```php
<?php
namespace Domain\Admissions\QueryBuilders;

use Domain\Admissions\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Builder;

final class ApplicationQueryBuilder extends Builder
{
    public function submitted(): self
    {
        return $this->where('status', ApplicationStatus::Submitted);
    }

    public function forProgram(int $programId): self
    {
        return $this->where('program_id', $programId);
    }
}

// داخل الموديل:
// public function newEloquentBuilder($query): ApplicationQueryBuilder
// {
//     return new ApplicationQueryBuilder($query);
// }
```

## ٧. ما الجديد في Laravel 13 وكيف يخدم DDD؟

- **PHP 8.3 كحد أدنى:** تستفيد من `readonly` وEnums وtyped constants، وهي أساس Value Objects وDTOs.
- **PHP Attributes:** بدل خصائص `protected` في الموديلات (مثل `#[Fillable]` و`#[Table]` و`#[Hidden]`) وفي الـ Jobs (`#[Tries]` و`#[Timeout]` و`#[Queue]`). الكود يصير أوضح وأقرب لكونه «إعلاناً» عن المجال.
- **`Queue::route()`:** مركزة توجيه الـ Jobs لطوابير محددة، مناسب لتوجيه Listeners كل Context لطابوره.
- **JSON:API Resources** من أول درجة: طبقة العرض (Presentation) منفصلة عن المجال.
- **Laravel AI SDK وبحث المتجهات:** اعتبرها *Infrastructure*. غلّفها خلف Interface في المجال حتى لا تتسرب تفاصيل المزوّد إلى منطق العمل.
- الترقية من 12 إلى 13 تُوصف بأنها شبه بلا Breaking Changes، لكن راجع دليل الترقية الرسمي لمشروعك.

```php
<?php
namespace Domain\Admissions\Jobs;

use Illuminate\Queue\Attributes\Tries;   // تحقق من الـ namespace الدقيق في توثيق 13
use Illuminate\Contracts\Queue\ShouldQueue;

#[Tries(3)]
final class ScoreApplication implements ShouldQueue
{
    public function __construct(public int $applicationId) {}

    public function handle(): void { /* ... */ }
}
```

> **ملاحظة أمانة:** أسماء الـ Attributes وأماكنها (namespaces) معتمدة على ملخصات الإصدار، وراجع توثيق laravel.com/docs/13.x قبل الاعتماد عليها حرفياً.

## ٨. أخطاء شائعة

- **تحويل DDD إلى مجلدات فقط** بدون Ubiquitous Language ولا Bounded Contexts. النتيجة CRUD بأسماء ثقيلة.
- **Anemic Model:** موديلات بلا سلوك وكل المنطق في Services ضخمة. ضع القواعد داخل الكيان.
- **Repository لكل جدول** فوق Eloquent بلا سبب.
- **Aggregate ضخم:** اجعله صغيراً، وارجع للـ Aggregates الأخرى بالـ ID لا بالكائن.
- **ربط Contexts بعلاقات Eloquent مباشرة** عبر الحدود. استخدم Events أو Interfaces.
- **تسريب `Request` أو `auth()` إلى الـ Domain.**
- **تطبيقه على كل شيء.** ابدأ بالـ Core Domain فقط.

## ٩. الاختبار

من فوائد DDD: قواعد المجال تُختبر بسرعة بدون HTTP. Value Objects وEnums تُختبر كـ Unit Tests نقية، والـ Actions كـ Feature/Integration مع قاعدة بيانات.

```php
it('rejects invalid transition', function () {
    $app = AdmissionApplication::factory()->create(['status' => ApplicationStatus::Accepted]);

    expect(fn () => $app->submit())->toThrow(InvalidTransition::class);
});

it('rejects out of range gpa', function () {
    new Gpa(6.2);
})->throws(InvalidArgumentException::class);
```

## خطة ابدأ بها

1. اكتب قاموس المصطلحات (Ubiquitous Language) وحدد الـ Contexts.
2. أنشئ `src/Domain` وانقل أعقد Context فقط.
3. ابدأ بـ Actions + DTOs + Enums، ثم Value Objects، ثم Events.
4. أضف Repositories فقط عند وجود سبب فعلي.

---

**المراجع:** Eric Evans «Domain-Driven Design» (2003)؛ Vaughn Vernon «Implementing Domain-Driven Design» (2013)؛ Brent Roose «Laravel Beyond CRUD» (Spatie)؛ ملاحظات إصدار Laravel 13 وتوثيقه الرسمي.

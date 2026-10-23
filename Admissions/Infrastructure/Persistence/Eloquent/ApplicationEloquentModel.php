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

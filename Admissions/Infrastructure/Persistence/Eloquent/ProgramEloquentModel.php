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

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

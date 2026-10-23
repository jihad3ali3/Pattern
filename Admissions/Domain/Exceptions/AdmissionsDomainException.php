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

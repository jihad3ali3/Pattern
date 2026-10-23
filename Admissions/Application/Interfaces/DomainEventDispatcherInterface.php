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

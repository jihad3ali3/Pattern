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

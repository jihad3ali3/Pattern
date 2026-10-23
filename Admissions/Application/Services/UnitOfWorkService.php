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

<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Providers/AdmissionsServiceProvider.php
 * اسم الصفحة: AdmissionsServiceProvider.php
 * الوظيفة: مزوّد خدمة Laravel (Service Provider) يربط واجهات المجال والتطبيق بتنفيذاتها في البنية التحتية داخل حاوية الاعتماديات (DI Container)، ويسجّل مستمعات الأحداث وأوامر الـ Console.
 */

namespace Admissions\Infrastructure\Providers;

use Admissions\Application\Interfaces\ApplicantNotifierInterface;
use Admissions\Application\Interfaces\ClockInterface;
use Admissions\Application\Interfaces\DomainEventDispatcherInterface;
use Admissions\Application\Interfaces\IdGeneratorInterface;
use Admissions\Application\Interfaces\TransactionManagerInterface;
use Admissions\Application\Services\UnitOfWorkService;
use Admissions\Domain\Events\ApplicationAccepted;
use Admissions\Domain\Repositories\AdmissionApplicationRepositoryInterface;
use Admissions\Domain\Repositories\ProgramRepositoryInterface;
use Admissions\Domain\Services\AdmissionDecisionDomainService;
use Admissions\Infrastructure\Events\LaravelDomainEventDispatcher;
use Admissions\Infrastructure\Events\SendAcceptanceNotificationListener;
use Admissions\Infrastructure\Persistence\Repositories\EloquentAdmissionApplicationRepository;
use Admissions\Infrastructure\Persistence\Repositories\EloquentProgramRepository;
use Admissions\Infrastructure\Services\LaravelTransactionManager;
use Admissions\Infrastructure\Services\MailApplicantNotifierService;
use Admissions\Infrastructure\Services\SystemClockService;
use Admissions\Infrastructure\Services\UlidGeneratorService;
use Admissions\Presentation\Console\ExpireDraftApplicationsCommand;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class AdmissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ربط مستودعات المجال بتنفيذات Eloquent
        $this->app->bind(
            AdmissionApplicationRepositoryInterface::class,
            EloquentAdmissionApplicationRepository::class,
        );

        $this->app->bind(
            ProgramRepositoryInterface::class,
            EloquentProgramRepository::class,
        );

        // ربط منافذ التطبيق (Application Interfaces) بخدمات البنية التحتية
        $this->app->bind(ClockInterface::class, SystemClockService::class);
        $this->app->bind(IdGeneratorInterface::class, UlidGeneratorService::class);
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->bind(DomainEventDispatcherInterface::class, LaravelDomainEventDispatcher::class);
        $this->app->bind(ApplicantNotifierInterface::class, MailApplicantNotifierService::class);

        // تسجيل خدمات المجال والتطبيق
        $this->app->singleton(AdmissionDecisionDomainService::class);
        $this->app->scoped(UnitOfWorkService::class);
    }

    public function boot(Dispatcher $events): void
    {
        // تسجيل صريح لمستمع الحدث لأن المجلد خارج app/Listeners
        $events->listen(
            ApplicationAccepted::class,
            SendAcceptanceNotificationListener::class,
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireDraftApplicationsCommand::class,
            ]);
        }
    }
}

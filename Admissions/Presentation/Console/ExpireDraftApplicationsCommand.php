<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Console/ExpireDraftApplicationsCommand.php
 * اسم الصفحة: ExpireDraftApplicationsCommand.php
 * الوظيفة: أمر طرفية (Artisan Console Command) يستدعي حالة الاستخدام ExpireDraftApplicationsUseCase لحذف مسودات طلبات القبول المتروكة منذ أكثر من عدد محدد من الأيام.
 */

namespace Admissions\Presentation\Console;

use Admissions\Application\UseCases\ExpireDraftApplicationsUseCase;
use Illuminate\Console\Command;

final class ExpireDraftApplicationsCommand extends Command
{
    protected $signature = 'admissions:expire-drafts {--days=30 : عدد الأيام قبل اعتبار المسودة منتهية}';

    protected $description = 'حذف مسودات طلبات القبول الجامعي المتروكة التي تجاوزت المهلة المحددة';

    public function handle(ExpireDraftApplicationsUseCase $useCase): int
    {
        $days = max(1, (int) $this->option('days'));

        $deletedCount = $useCase->execute($days);

        $this->info("تم حذف [{$deletedCount}] مسودة طلب قبول مضى عليها أكثر من {$days} يوماً.");

        return self::SUCCESS;
    }
}

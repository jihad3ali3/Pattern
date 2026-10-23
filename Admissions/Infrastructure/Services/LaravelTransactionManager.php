<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Infrastructure/Services/LaravelTransactionManager.php
 * اسم الصفحة: LaravelTransactionManager.php
 * الوظيفة: محوّل (Adapter) ينفّذ واجهة TransactionManagerInterface لإدارة معاملات قاعدة البيانات عبر DatabaseManager في Laravel مع دعم إعادة المحاولة عند التعارض.
 */

namespace Admissions\Infrastructure\Services;

use Admissions\Application\Interfaces\TransactionManagerInterface;
use Illuminate\Database\DatabaseManager;

final readonly class LaravelTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private DatabaseManager $database,
    ) {
    }

    public function transactional(callable $callback): mixed
    {
        return $this->database->connection()->transaction($callback, attempts: 3);
    }
}

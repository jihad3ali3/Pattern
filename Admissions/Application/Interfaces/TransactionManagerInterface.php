<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Application/Interfaces/TransactionManagerInterface.php
 * اسم الصفحة: TransactionManagerInterface.php
 * الوظيفة: منفذ (Port / Interface) لإدارة معاملات قاعدة البيانات (Database Transactions) بشكل مجرد عن إطار العمل.
 */

namespace Admissions\Application\Interfaces;

interface TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public function transactional(callable $callback): mixed;
}

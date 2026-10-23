<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Middleware/EnsureAdmissionsWindowOpenMiddleware.php
 * اسم الصفحة: EnsureAdmissionsWindowOpenMiddleware.php
 * الوظيفة: وسيط HTTP (Middleware) يتحقق من أن فترة التقديم الجامعي مفتوحة حالياً قبل السماح بإنشاء أو تقديم طلبات قبول جديدة.
 */

namespace Admissions\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdmissionsWindowOpenMiddleware
{
    /**
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isWindowOpen = (bool) config('admissions.window_open', true);

        if (! $isWindowOpen) {
            return response()->json([
                'error' => [
                    'code' => 'admissions_window_closed',
                    'message' => 'فترة التقديم الجامعي مغلقة حالياً.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

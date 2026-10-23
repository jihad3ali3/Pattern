<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Middleware/RenderAdmissionsExceptionMiddleware.php
 * اسم الصفحة: RenderAdmissionsExceptionMiddleware.php
 * الوظيفة: وسيط HTTP (Middleware) يلتقط استثناءات المجال الحاملة لكود خطأ (HasErrorCode) ويترجمها إلى رموز حالة HTTP مناسبة (404، 409، 422) دون تلويث المجال بتفاصيل البروتوكول.
 */

namespace Admissions\Presentation\Http\Middleware;

use Admissions\Domain\Exceptions\HasErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RenderAdmissionsExceptionMiddleware
{
    /** @var array<string, int> */
    private const STATUS_MAP = [
        'invalid_gpa' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'gpa_below_minimum' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'rejection_reason_required' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'invalid_transition' => Response::HTTP_CONFLICT,
        'no_seats_available' => Response::HTTP_CONFLICT,
        'already_applied' => Response::HTTP_CONFLICT,
        'application_not_found' => Response::HTTP_NOT_FOUND,
        'program_not_found' => Response::HTTP_NOT_FOUND,
    ];

    /**
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (HasErrorCode $exception) {
            $status = self::STATUS_MAP[$exception->errorCode()] ?? Response::HTTP_INTERNAL_SERVER_ERROR;

            return response()->json([
                'error' => [
                    'code' => $exception->errorCode(),
                    'message' => $exception->getMessage(),
                ],
            ], $status);
        }
    }
}

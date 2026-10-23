<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Requests/RejectApplicationRequest.php
 * اسم الصفحة: RejectApplicationRequest.php
 * الوظيفة: طلب تحقق HTTP (FormRequest) يتحقق من صلاحية موظف القبول ووجود سبب الرفض نصياً، ويحوّل البيانات إلى كائن RejectApplicationDTO.
 */

namespace Admissions\Presentation\Http\Requests;

use Admissions\Application\DTOs\RejectApplicationDTO;
use Illuminate\Foundation\Http\FormRequest;

final class RejectApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admissions.decide') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function toDTO(string $applicationId): RejectApplicationDTO
    {
        return new RejectApplicationDTO(
            applicationId: $applicationId,
            reason: (string) $this->string('reason'),
        );
    }
}

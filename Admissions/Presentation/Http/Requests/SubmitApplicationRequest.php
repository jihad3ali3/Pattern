<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Requests/SubmitApplicationRequest.php
 * اسم الصفحة: SubmitApplicationRequest.php
 * الوظيفة: طلب تحقق HTTP (FormRequest) يفحص شكل وصيغة مدخلات تقديم الطلب (program_id و gpa) ويحوّلها إلى كائن SubmitApplicationDTO.
 */

namespace Admissions\Presentation\Http\Requests;

use Admissions\Application\DTOs\SubmitApplicationDTO;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['required', 'string', 'size:26'],
            'gpa' => ['required', 'numeric', 'between:0,5'],
        ];
    }

    public function toDTO(): SubmitApplicationDTO
    {
        return new SubmitApplicationDTO(
            studentId: (string) $this->user()->getAuthIdentifier(),
            programId: (string) $this->string('program_id'),
            gpa: $this->float('gpa'),
        );
    }
}

<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Resources/AdmissionApplicationResource.php
 * اسم الصفحة: AdmissionApplicationResource.php
 * الوظيفة: مورد عرض (API JsonResource) يحوّل كائن القراءة ApplicationReadDTO إلى استجابة JSON موحّدة لواجهة البرمجة (API).
 */

namespace Admissions\Presentation\Http\Resources;

use Admissions\Application\DTOs\ApplicationReadDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read ApplicationReadDTO $resource
 */
final class AdmissionApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'student_id' => $this->resource->studentId,
            'program_id' => $this->resource->programId,
            'gpa' => $this->resource->gpa,
            'status' => [
                'code' => $this->resource->status,
                'label' => $this->resource->statusLabel,
            ],
            'decision_reason' => $this->resource->decisionReason,
            'submitted_at' => $this->resource->submittedAt,
            'decided_at' => $this->resource->decidedAt,
        ];
    }
}

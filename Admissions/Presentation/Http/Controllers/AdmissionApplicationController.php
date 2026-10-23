<?php

declare(strict_types=1);

/**
 * المسار: Admissions/Presentation/Http/Controllers/AdmissionApplicationController.php
 * اسم الصفحة: AdmissionApplicationController.php
 * الوظيفة: متحكم HTTP (Controller) نحيف يستقبل طلبات الويب (عرض، تقديم، قبول، رفض، انسحاب)، يفوّض التنفيذ إلى حالات الاستخدام في طبقة التطبيق، ويعيد الاستجابة عبر AdmissionApplicationResource.
 */

namespace Admissions\Presentation\Http\Controllers;

use Admissions\Application\Services\ApplicationQueryService;
use Admissions\Application\UseCases\AcceptApplicationUseCase;
use Admissions\Application\UseCases\RejectApplicationUseCase;
use Admissions\Application\UseCases\SubmitApplicationUseCase;
use Admissions\Application\UseCases\WithdrawApplicationUseCase;
use Admissions\Presentation\Http\Requests\RejectApplicationRequest;
use Admissions\Presentation\Http\Requests\SubmitApplicationRequest;
use Admissions\Presentation\Http\Resources\AdmissionApplicationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final readonly class AdmissionApplicationController
{
    public function show(
        string $id,
        ApplicationQueryService $queryService,
    ): AdmissionApplicationResource {
        $dto = $queryService->getById($id);

        return new AdmissionApplicationResource($dto);
    }

    public function store(
        SubmitApplicationRequest $request,
        SubmitApplicationUseCase $useCase,
    ): JsonResponse {
        $dto = $useCase->execute($request->toDTO());

        return (new AdmissionApplicationResource($dto))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function accept(
        string $id,
        AcceptApplicationUseCase $useCase,
    ): AdmissionApplicationResource {
        Gate::authorize('admissions.decide');

        $dto = $useCase->execute($id);

        return new AdmissionApplicationResource($dto);
    }

    public function reject(
        string $id,
        RejectApplicationRequest $request,
        RejectApplicationUseCase $useCase,
    ): AdmissionApplicationResource {
        $dto = $useCase->execute($request->toDTO($id));

        return new AdmissionApplicationResource($dto);
    }

    public function withdraw(
        string $id,
        WithdrawApplicationUseCase $useCase,
    ): AdmissionApplicationResource {
        $dto = $useCase->execute($id);

        return new AdmissionApplicationResource($dto);
    }
}

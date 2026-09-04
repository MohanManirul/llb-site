<?php

namespace App\Http\Controllers\V1\Admin\Projects;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\Payment\PaymentHistoryResource;
use App\Http\Resources\Payment\PaymentResource;
use App\Http\Resources\Payment\PaymentStatusResource;
use App\Models\Project;
use App\Services\ApiResponseService;
use App\Services\Payment\ImageUploadService;
use App\Services\Payment\PaymentHistoryService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PaymentController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('throttle:10,1', only: ['store']),
            new Middleware('throttle:60,1', only: ['status', 'history']),
        ];
    }

    public function __construct(
        private ApiResponseService $apiResponse,
        private PaymentService $paymentService,
        private PaymentHistoryService $historyService,
        private ImageUploadService $imageUploadService,
    ) {}

    public function store(StorePaymentRequest $request, Project $project): JsonResponse
    {
        $proofPath = null;

        if ($request->hasFile('proof')) {
            $proofPath = $this->imageUploadService->uploadProofImage($request->file('proof'));
        }

        $payment = $this->paymentService->createPayment(
            $project->id,
            (float) $request->input('amount'),
            $request->input('payment_type'),
            $request->validated(),
            $proofPath,
            auth()->id(),
            $request->input('next_payment_date'),
        );

        return $this->apiResponse->respondCreated(
            new PaymentResource($payment),
            'Payment recorded successfully',
        );
    }

    public function status(Project $project): JsonResponse
    {
        $status = $this->paymentService->getProjectPaymentStatus($project->id);

        return $this->apiResponse->respondWithResource(
            new PaymentStatusResource($status),
            'Payment status retrieved',
        );
    }

    public function history(Project $project): JsonResponse
    {
        $history = $this->historyService->getProjectHistory(
            $project->id,
            request('per_page', 15),
        );

        return $this->apiResponse->respondWithResourceCollection(
            PaymentHistoryResource::collection($history),
            'Payment history retrieved',
        );
    }
}

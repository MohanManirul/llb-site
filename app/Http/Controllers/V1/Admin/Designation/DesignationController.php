<?php

namespace App\Http\Controllers\V1\Admin\Designation;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Designation\IndexDesignationRequest;
use App\Http\Requests\V1\Admin\Designation\StoreDesignationRequest;
use App\Http\Requests\V1\Admin\Designation\UpdateDesignationRequest;
use App\Http\Resources\Designation\DesignationResource;
use App\Models\Designation;
use App\Services\Designation\DesignationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view designations', only: ['index', 'show', 'search']),
            new Middleware('permission:create designations', only: ['store']),
            new Middleware('permission:edit designations', only: ['update']),
            new Middleware('permission:delete designations', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly DesignationService $designationService,
    ) {}

    public function index(IndexDesignationRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            DesignationResource::collection($this->designationService->paginate($request->filters())),
            'Designations retrieved successfully.',
        );
    }

    public function search(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->designationService->searchOptions(
                $request->input('search'),
                $request->integer('department_id') ?: null,
            ),
            'Designations retrieved successfully.',
        );
    }

    public function store(StoreDesignationRequest $request): JsonResponse
    {
        $designation = DB::transaction(function () use ($request) {
            $designation = $this->designationService->create($request->validated());

            activity()->performedOn($designation)->log('Designation created.');

            return $designation;
        });

        return ApiResponse::respondWithResource(
            new DesignationResource($designation),
            'Designation created successfully.',
            201,
        );
    }

    public function show(Designation $designation): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new DesignationResource($designation),
            'Designation retrieved successfully.',
        );
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): JsonResponse
    {
        $designation = DB::transaction(function () use ($request, $designation) {
            $designation = $this->designationService->update($designation, $request->validated());

            activity()->performedOn($designation)->log('Designation updated.');

            return $designation;
        });

        return ApiResponse::respondWithResource(
            new DesignationResource($designation),
            'Designation updated successfully.',
        );
    }

    public function destroy(Designation $designation): JsonResponse
    {
        DB::transaction(function () use ($designation) {
            $this->designationService->delete($designation);

            activity()->performedOn($designation)->log('Designation deleted.');
        });

        return ApiResponse::respondSuccess('Designation deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\V1\Admin\Company;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Company\IndexCompanyRequest;
use App\Http\Requests\V1\Admin\Company\StoreCompanyRequest;
use App\Http\Requests\V1\Admin\Company\UpdateCompanyRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Models\Company;
use App\Services\Company\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view companies', only: ['index', 'show', 'search']),
            new Middleware('permission:create companies', only: ['store']),
            new Middleware('permission:edit companies', only: ['update']),
            new Middleware('permission:delete companies', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly CompanyService $companyService,
    ) {}

    public function index(IndexCompanyRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            CompanyResource::collection($this->companyService->paginate($request->filters())),
            'Companies retrieved successfully.',
        );
    }

    public function search(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->companyService->searchOptions($request->input('search')),
            'Companies retrieved successfully.',
        );
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = DB::transaction(function () use ($request) {
            $company = $this->companyService->create(
                $request->validated(),
                $this->storeFile($request, 'logo'),
            );

            activity()->performedOn($company)->log('Company created.');

            return $company;
        });

        return ApiResponse::respondWithResource(
            new CompanyResource($company),
            'Company created successfully.',
            201,
        );
    }

    public function show(Company $company): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new CompanyResource($company),
            'Company retrieved successfully.',
        );
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company = DB::transaction(function () use ($request, $company) {
            $company = $this->companyService->update(
                $company,
                $request->validated(),
                $this->storeFile($request, 'logo', $company->logo),
            );

            activity()->performedOn($company)->log('Company updated.');

            return $company;
        });

        return ApiResponse::respondWithResource(
            new CompanyResource($company),
            'Company updated successfully.',
        );
    }

    public function destroy(Company $company): JsonResponse
    {
        DB::transaction(function () use ($company) {
            $this->companyService->delete($company);

            activity()->performedOn($company)->log('Company deleted.');
        });

        return ApiResponse::respondSuccess('Company deleted successfully.');
    }
}

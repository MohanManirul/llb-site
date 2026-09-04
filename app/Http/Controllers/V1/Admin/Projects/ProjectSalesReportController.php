<?php

namespace App\Http\Controllers\V1\Admin\Projects;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Projects\ImportSalesReportsRequest;
use App\Http\Requests\V1\Admin\Projects\IndexSalesReportRequest;
use App\Http\Requests\V1\Admin\Projects\StoreSalesReportRequest;
use App\Http\Requests\V1\Admin\Projects\UpdateSalesReportRequest;
use App\Http\Resources\SalesReport\SalesReportResource;
use App\Jobs\ImportSalesReportsJob;
use App\Models\Project;
use App\Models\SalesReport;
use App\Services\Project\SalesReportImportProgress;
use App\Services\Project\SalesReportImportService;
use App\Services\Project\SalesReportService;
use App\Traits\ChecksProjectAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectSalesReportController extends Controller
{
    use ChecksProjectAccess;

    public function __construct(
        private readonly SalesReportService $salesReportService,
        private readonly SalesReportImportService $salesReportImportService,
        private readonly SalesReportImportProgress $importProgress,
    ) {}

    public function index(IndexSalesReportRequest $request, Project $project): JsonResponse
    {
        $this->ensureCanViewProjectReports($request->user(), $project);

        $reports = $this->salesReportService->listForProject(
            $project,
            $request->weekStart(),
            $request->weekEnd(),
        );

        return ApiResponse::respondWithResourceCollection(
            SalesReportResource::collection($reports),
            'Sales reports retrieved successfully.',
        );
    }

    public function store(StoreSalesReportRequest $request, Project $project): JsonResponse
    {
        $this->ensureCanSubmitProjectReports($request->user(), $project);

        $salesReport = $this->salesReportService->createForProject($project, $request->validated());

        activity()->performedOn($salesReport)->log('Sales report created.');

        return ApiResponse::respondWithResource(
            new SalesReportResource($salesReport),
            'Sales report created successfully.',
            201,
        );
    }

    public function import(ImportSalesReportsRequest $request, Project $project): JsonResponse
    {
        $this->ensureCanSubmitProjectReports($request->user(), $project);

        $file = $request->file('file');
        $batch = $this->salesReportImportService->readRows($file);
        $rows = count($batch['rows']);
        $importId = (string) Str::uuid();

        $this->importProgress->queued($importId, $project->id, $rows);

        dispatch(new ImportSalesReportsJob(
            $project->id,
            $batch['rows'],
            $batch['columns'],
            $importId,
        ));

        activity()->performedOn($project)->log(
            "Sales reports CSV queued for import: {$rows} row(s) from {$file->getClientOriginalName()}."
        );

        return ApiResponse::respondWithSuccess(
            data: ['import_id' => $importId, 'rows' => $rows],
            message: "{$rows} row(s) queued for import.",
        );
    }

    public function importStatus(Request $request, Project $project, string $importId): JsonResponse
    {
        $this->ensureCanSubmitProjectReports($request->user(), $project);

        $state = $this->importProgress->find($importId);

        abort_if($state === null || (int) $state['project_id'] !== $project->id, 404);

        return ApiResponse::respondWithSuccess(
            data: $state,
            message: 'Import status retrieved successfully.',
        );
    }

    public function update(UpdateSalesReportRequest $request, Project $project, SalesReport $salesReport): JsonResponse
    {
        abort_if($salesReport->project_id !== $project->id, 404);

        $this->ensureCanEditProjectReports($request->user(), $project);

        $salesReport = $this->salesReportService->update($salesReport, $request->validated());

        activity()->performedOn($salesReport)->log('Sales report updated.');

        return ApiResponse::respondWithResource(
            new SalesReportResource($salesReport),
            'Sales report updated successfully.',
        );
    }

    public function destroy(Request $request, Project $project, SalesReport $salesReport): JsonResponse
    {
        abort_if($salesReport->project_id !== $project->id, 404);

        $this->ensureCanDeleteProjectReports($request->user(), $project);

        activity()->performedOn($salesReport)->log('Sales report deleted.');

        $this->salesReportService->delete($salesReport);

        return ApiResponse::respondSuccess('Sales report deleted successfully.');
    }
}

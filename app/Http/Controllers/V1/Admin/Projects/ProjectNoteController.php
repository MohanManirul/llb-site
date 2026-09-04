<?php

namespace App\Http\Controllers\V1\Admin\Projects;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Projects\StoreProjectNoteRequest;
use App\Http\Requests\V1\Admin\Projects\UpdateProjectNoteRequest;
use App\Http\Resources\Project\ProjectNoteResource;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Services\Project\ProjectNoteService;
use App\Traits\ChecksProjectAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectNoteController extends Controller
{
    use ChecksProjectAccess;

    public function __construct(
        private readonly ProjectNoteService $projectNoteService,
    ) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        $this->ensureCanViewProjectNotes($user, $project);

        return ApiResponse::respondWithResourceCollection(
            ProjectNoteResource::collection($this->projectNoteService->listForProject($project, $user)),
            'Project notes retrieved successfully.',
        );
    }

    public function store(StoreProjectNoteRequest $request, Project $project): JsonResponse
    {
        $this->ensureCanAddProjectNotes($request->user(), $project);

        $note = $this->projectNoteService->create(
            $project,
            $request->validated(),
            $request->user(),
        );

        activity()->performedOn($note)->log('Project note created.');

        return ApiResponse::respondWithResource(
            new ProjectNoteResource($note->load('user:id,name')),
            'Project note created successfully.',
            201,
        );
    }

    public function update(UpdateProjectNoteRequest $request, Project $project, ProjectNote $note): JsonResponse
    {
        abort_if($note->project_id !== $project->id, 404);

        $this->ensureCanEditProjectNotes($request->user(), $project);

        $note = $this->projectNoteService->update($note, $request->validated());

        activity()->performedOn($note)->log('Project note updated.');

        return ApiResponse::respondWithResource(
            new ProjectNoteResource($note->load('user:id,name')),
            'Project note updated successfully.',
        );
    }

    public function destroy(Request $request, Project $project, ProjectNote $note): JsonResponse
    {
        abort_if($note->project_id !== $project->id, 404);

        $this->ensureCanDeleteProjectNotes($request->user(), $project);

        activity()->performedOn($note)->log('Project note deleted.');

        $this->projectNoteService->delete($note);

        return ApiResponse::respondSuccess('Note deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\V1\Admin\Teams;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Teams\IndexTeamRequest;
use App\Http\Requests\V1\Admin\Teams\StoreTeamRequest;
use App\Http\Requests\V1\Admin\Teams\UpdateTeamRequest;
use App\Http\Resources\Team\TeamResource;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TeamController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view teams', only: ['index', 'show', 'search', 'searchMembers']),
            new Middleware('permission:create teams', only: ['store']),
            new Middleware('permission:edit teams', only: ['update']),
            new Middleware('permission:delete teams', only: ['destroy']),
        ];
    }

    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    public function index(IndexTeamRequest $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            TeamResource::collection($this->teamService->paginate($request->filters())),
            'Teams retrieved successfully.',
        );
    }

    public function search(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->teamService->searchOptions(trim((string) $request->input('search', ''))),
            'Teams retrieved successfully.',
        );
    }

    public function searchMembers(Request $request): JsonResponse
    {
        return ApiResponse::respondWithSuccess(
            $this->teamService->memberSearchOptions(
                $request->input('role'),
                trim((string) $request->input('search', '')),
            ),
            'Team members retrieved successfully.',
        );
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = $this->teamService->create($request->validated());

        activity()->performedOn($team)->log('Team created.');

        return ApiResponse::respondWithResource(
            new TeamResource($this->loadForShow($team)),
            'Team created successfully.',
            201,
        );
    }

    public function show(Team $team): JsonResponse
    {
        return ApiResponse::respondWithResource(
            new TeamResource($this->loadForShow($team)),
            'Team retrieved successfully.',
        );
    }

    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $team = $this->teamService->update($team, $request->validated());

        activity()->performedOn($team)->log('Team updated.');

        return ApiResponse::respondWithResource(
            new TeamResource($this->loadForShow($team)),
            'Team updated successfully.',
        );
    }

    public function destroy(Team $team): JsonResponse
    {
        activity()->performedOn($team)->log('Team deleted.');

        $team->delete();

        return ApiResponse::respondSuccess('Team deleted successfully.');
    }

    private function loadForShow(Team $team): Team
    {
        return $team->load([
            'company:id,name',
            'department:id,name',
            'members' => fn ($query) => $query
                ->select('employees.id', 'employees.user_id', 'employees.designation_id')
                ->with(['user:id,name,email,phone,image', 'designation'])
                ->orderByPivot('role'),
        ]);
    }
}

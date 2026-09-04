<?php

namespace App\Http\Controllers\V1\Admin\Teams;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Team\LedTeamResource;
use App\Models\Team;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyTeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::respondWithResourceCollection(
            LedTeamResource::collection(
                $this->teamService->ledTeamsForEmployeeIds($this->employeeIds($request))
            ),
            'Teams retrieved successfully.',
        );
    }

    public function show(Request $request, Team $team): JsonResponse
    {
        $employeeIds = $this->employeeIds($request);

        abort_unless(
            $this->teamService->leadsTeamAsAny($employeeIds, $team),
            403,
            'You can only view the teams you lead.',
        );

        return ApiResponse::respondWithResource(
            new LedTeamResource(
                $this->teamService->ledTeamsForEmployeeIds($employeeIds)->firstWhere('id', $team->id)
            ),
            'Team retrieved successfully.',
        );
    }

    private function employeeIds(Request $request): array
    {
        $employeeIds = $request->user()?->employeeIds() ?? [];

        abort_if($employeeIds === [], 403, 'Only employees have a team.');

        return $employeeIds;
    }
}

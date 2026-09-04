<?php

namespace App\DTOs;

use App\Models\Project;
use Illuminate\Contracts\Pagination\Paginator;

final readonly class ProjectListResult
{
    /**
     * @param  Paginator<int, Project>  $projects
     * @param  array<int, int>  $employeeIds
     * @param  array<int, int>  $ledTeamIds
     */
    public function __construct(
        public Paginator $projects,
        public array $employeeIds = [],
        public array $ledTeamIds = [],
    ) {}
}

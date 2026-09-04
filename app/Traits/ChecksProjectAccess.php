<?php

namespace App\Traits;

use App\Models\Client;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

trait ChecksProjectAccess
{
    protected function ensureCanAccessProject(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canAccessProject($user, $project)) {
            return;
        }

        abort(403, 'You can only view your own projects.');
    }

    protected function ensureCanViewProjectNotes(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canViewProjectNotes($user, $project)) {
            return;
        }

        abort(403, 'You cannot view notes for this project.');
    }

    protected function ensureCanAddProjectNotes(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canAddProjectNotes($user, $project)) {
            return;
        }

        abort(403, 'You cannot add notes to this project.');
    }

    protected function ensureCanEditProjectNotes(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canEditProjectNotes($user, $project)) {
            return;
        }

        abort(403, 'You cannot edit notes for this project.');
    }

    protected function ensureCanDeleteProjectNotes(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canDeleteProjectNotes($user, $project)) {
            return;
        }

        abort(403, 'You cannot delete notes for this project.');
    }

    protected function ensureCanEditProject(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canEditProject($user, $project)) {
            return;
        }

        abort(403, 'You can only edit your own projects.');
    }

    protected function ensureCanDeleteProject(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canDeleteProject($user, $project)) {
            return;
        }

        abort(403, 'You can only delete your own projects.');
    }

    protected function ensureCanViewProjectReports(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canViewProjectReports($user, $project)) {
            return;
        }

        abort(403, 'You cannot view reports for this project.');
    }

    protected function ensureCanSubmitProjectReports(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canSubmitProjectReports($user, $project)) {
            return;
        }

        abort(403, 'You cannot submit reports for this project.');
    }

    protected function ensureCanEditProjectReports(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canEditProjectReports($user, $project)) {
            return;
        }

        abort(403, 'You cannot edit reports for this project.');
    }

    protected function ensureCanDeleteProjectReports(User|Client|null $user, Project $project): void
    {
        if (! $user) {
            abort(401);
        }

        if ($this->canDeleteProjectReports($user, $project)) {
            return;
        }

        abort(403, 'You cannot delete reports for this project.');
    }

    protected function canAccessProject(User|Client $user, Project $project): bool
    {
        if ($user instanceof Client) {
            return $user->id === $project->client_id;
        }

        if ($this->reachesEveryProject($user)) {
            return true;
        }

        return $this->isAssignedOrLeadingProject($user, $project);
    }

    /**
     * A project permission means the holder's own projects — the ones they are
     * assigned to or lead the team of — and nothing wider. super-admin is the
     * single exception, the same one `AppServiceProvider`'s `Gate::before`
     * makes for every other ability.
     */
    protected function reachesEveryProject(User|Client|null $user): bool
    {
        return $user instanceof User && $user->hasRole('super-admin');
    }

    protected function canEditProject(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
        array $ledTeamIds = [],
    ): bool {
        if (! $user || $user instanceof Client) {
            return false;
        }

        if ($this->reachesEveryProject($user)) {
            return true;
        }

        if (! $user->can('edit projects')) {
            return false;
        }

        return $this->ownsProject($user, $project, $employeeIds, $ledTeamIds);
    }

    protected function canDeleteProject(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
        array $ledTeamIds = [],
    ): bool {
        if (! $user || $user instanceof Client) {
            return false;
        }

        if ($this->reachesEveryProject($user)) {
            return true;
        }

        if (! $user->can('delete projects')) {
            return false;
        }

        return $this->ownsProject($user, $project, $employeeIds, $ledTeamIds);
    }

    protected function canViewProjectNotes(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
        array $ledTeamIds = [],
    ): bool {
        return $this->allowedOnProjectNotes($user, $project, 'view project notes', $employeeIds, $ledTeamIds);
    }

    protected function canAddProjectNotes(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
        array $ledTeamIds = [],
    ): bool {
        return $this->allowedOnProjectNotes($user, $project, 'create project notes', $employeeIds, $ledTeamIds);
    }

    protected function canEditProjectNotes(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
        array $ledTeamIds = [],
    ): bool {
        return $this->allowedOnProjectNotes($user, $project, 'edit project notes', $employeeIds, $ledTeamIds);
    }

    protected function canDeleteProjectNotes(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
        array $ledTeamIds = [],
    ): bool {
        return $this->allowedOnProjectNotes($user, $project, 'delete project notes', $employeeIds, $ledTeamIds);
    }

    /**
     * @param  list<int>  $employeeIds
     * @param  list<int>  $ledTeamIds
     */
    private function allowedOnProjectNotes(
        User|Client|null $user,
        Project $project,
        string $permission,
        array $employeeIds,
        array $ledTeamIds,
    ): bool {
        if (! $user || $user instanceof Client) {
            return false;
        }

        if ($this->reachesEveryProject($user)) {
            return true;
        }

        return $user->can($permission)
            && $this->ownsProject($user, $project, $employeeIds, $ledTeamIds);
    }

    protected function canViewProjectReports(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
        array $ledTeamIds = [],
    ): bool {
        if (! $user || $user instanceof Client) {
            return false;
        }

        if ($this->reachesEveryProject($user)) {
            return true;
        }

        return $user->can('view sales reports')
            && $this->ownsProject($user, $project, $employeeIds, $ledTeamIds);
    }

    protected function canSubmitProjectReports(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
    ): bool {
        if (! $user || $user instanceof Client) {
            return false;
        }

        if ($this->reachesEveryProject($user)) {
            return true;
        }

        return $user->can('create sales reports')
            && $this->isAssignedToProject($user, $project, $employeeIds);
    }

    protected function canEditProjectReports(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
    ): bool {
        if (! $user || $user instanceof Client) {
            return false;
        }

        if ($this->reachesEveryProject($user)) {
            return true;
        }

        return $user->can('edit sales reports')
            && $this->isAssignedToProject($user, $project, $employeeIds);
    }

    protected function canDeleteProjectReports(
        User|Client|null $user,
        Project $project,
        array $employeeIds = [],
    ): bool {
        if (! $user || $user instanceof Client) {
            return false;
        }

        if ($this->reachesEveryProject($user)) {
            return true;
        }

        return $user->can('delete sales reports')
            && $this->isAssignedToProject($user, $project, $employeeIds);
    }

    /**
     * @param  list<int>  $employeeIds
     * @param  list<int>  $ledTeamIds
     */
    protected function ownsProject(
        User $user,
        Project $project,
        array $employeeIds = [],
        array $ledTeamIds = [],
    ): bool {
        if ($employeeIds === [] && $ledTeamIds === []) {
            return $this->isAssignedOrLeadingProject($user, $project);
        }

        return $this->matchesAssignedOrLedTeam($project, $employeeIds, $ledTeamIds);
    }

    protected function isAssignedOrLeadingProject(User $user, Project $project): bool
    {
        $employeeIds = $user->employeeIds();

        if ($employeeIds === []) {
            return false;
        }

        if (in_array($project->assigned_employee_id, $employeeIds, true)) {
            return true;
        }

        return $project->team_id
            && Team::whereKey($project->team_id)
                ->ledByEmployees($employeeIds)
                ->exists();
    }

    /**
     * @param  list<int>  $employeeIds
     */
    protected function isAssignedToProject(
        User $user,
        Project $project,
        array $employeeIds = [],
    ): bool {
        $ids = $employeeIds !== [] ? $employeeIds : $user->employeeIds();

        return $ids !== []
            && in_array($project->assigned_employee_id, $ids, true);
    }

    /**
     * @param  list<int>  $employeeIds
     * @param  list<int>  $ledTeamIds
     */
    protected function matchesAssignedOrLedTeam(
        Project $project,
        array $employeeIds,
        array $ledTeamIds,
    ): bool {
        if ($employeeIds !== [] && in_array($project->assigned_employee_id, $employeeIds, true)) {
            return true;
        }

        return $project->team_id !== null
            && $ledTeamIds !== []
            && in_array((int) $project->team_id, $ledTeamIds, true);
    }
}

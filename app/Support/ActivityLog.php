<?php

namespace App\Support;

use App\Models\ActivityLog as ActivityLogModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog
{
    protected ?Model $causedBy = null;

    protected ?Model $performedOn = null;

    protected string $type = 'admin';

    public function causedBy(Model|Authenticatable|null $user): self
    {
        $this->causedBy = $user instanceof Model ? $user : null;

        return $this;
    }

    public function performedOn(?Model $model): self
    {
        $this->performedOn = $model;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function log(string $description): ActivityLogModel
    {
        $activityLog = new ActivityLogModel;
        $activityLog->type = $this->type;
        $activityLog->description = $description;
        $activityLog->causer()->associate($this->causedBy ?? $this->resolveCauser());
        $activityLog->subject()->associate($this->performedOn);
        $activityLog->impersonator_id = $this->resolveImpersonatorId();
        $activityLog->save();

        return $activityLog;
    }

    protected function resolveImpersonatorId(): ?int
    {
        if (! app()->bound('session') || ! app('session')->isStarted()) {
            return null;
        }

        $impersonatorId = app('session')->get('impersonator_id');

        return $impersonatorId ? (int) $impersonatorId : null;
    }

    protected function resolveCauser(): ?Model
    {
        if (app()->bound('request') && ! app()->runningInConsole()) {
            $user = app('request')->user();

            if ($user instanceof Model) {
                return $user;
            }
        }

        foreach (['sanctum', 'web'] as $guard) {
            $auth = Auth::guard($guard);

            if ($auth->hasUser() || $auth->check()) {
                $user = $auth->user();

                if ($user instanceof Model) {
                    return $user;
                }
            }
        }

        return null;
    }
}

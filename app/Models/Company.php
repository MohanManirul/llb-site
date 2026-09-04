<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, Searchable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'logo',
        'email',
        'phone',
        'website',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? assetUrl($this->logo) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->logo ? assetUrl(getThumbnailPath($this->logo)) : null;
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['study_material_id', 'material_file_id', 'visitor_id', 'ip_hash', 'downloaded_at'])]
class MaterialDownload extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<StudyMaterial, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(StudyMaterial::class, 'study_material_id');
    }

    /**
     * @return BelongsTo<MaterialFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(MaterialFile::class, 'material_file_id');
    }
}

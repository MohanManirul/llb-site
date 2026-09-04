<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'study_material_id', 'disk', 'path', 'original_name', 'extension',
    'mime_type', 'size', 'checksum', 'page_count', 'label_bn', 'label_en',
    'sort_order',
])]
class MaterialFile extends Model
{
    use HasTranslatedFields;

    /**
     * @return BelongsTo<StudyMaterial, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(StudyMaterial::class, 'study_material_id');
    }
}

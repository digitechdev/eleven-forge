<?php

namespace App\Models;

use App\InnovationMediaType;
use Database\Factories\InnovationMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['innovation_id', 'media_type', 'file_path', 'video_url', 'title', 'caption', 'alt_text', 'is_cover', 'is_public', 'sort_order'])]
class InnovationMedia extends Model
{
    /** @use HasFactory<InnovationMediaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['media_type' => InnovationMediaType::class, 'is_cover' => 'boolean', 'is_public' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return BelongsTo<Innovation, $this> */
    public function innovation(): BelongsTo
    {
        return $this->belongsTo(Innovation::class);
    }
}

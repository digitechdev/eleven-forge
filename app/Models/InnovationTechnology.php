<?php

namespace App\Models;

use Database\Factories\InnovationTechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['innovation_id', 'technology_id', 'version', 'purpose'])]
class InnovationTechnology extends Model
{
    /** @use HasFactory<InnovationTechnologyFactory> */
    use HasFactory;

    /** @return BelongsTo<Innovation, $this> */
    public function innovation(): BelongsTo
    {
        return $this->belongsTo(Innovation::class);
    }

    /** @return BelongsTo<Technology, $this> */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }
}

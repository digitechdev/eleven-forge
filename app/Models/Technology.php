<?php

namespace App\Models;

use App\TechnologyStatus;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'description', 'website', 'status'])]
class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => TechnologyStatus::class];
    }

    /** @return HasMany<InnovationTechnology, $this> */
    public function innovationTechnologies(): HasMany
    {
        return $this->hasMany(InnovationTechnology::class);
    }

    /** @return BelongsToMany<Innovation, $this> */
    public function innovations(): BelongsToMany
    {
        return $this->belongsToMany(Innovation::class, 'innovation_technologies')->withPivot(['id', 'version', 'purpose'])->withTimestamps();
    }
}

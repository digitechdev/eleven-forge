<?php

namespace App\Models;

use App\Enums\TechnologyCategory;
use App\Enums\TechnologyStatus;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property TechnologyCategory $category
 * @property string|null $description
 * @property string|null $website
 * @property TechnologyStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'category', 'description', 'website', 'status'])]
class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['category' => TechnologyCategory::class, 'status' => TechnologyStatus::class];
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

    /**
     * Determine whether any innovation (including archived ones) is linked to the technology.
     */
    public function isInUse(): bool
    {
        return $this->innovationTechnologies()->exists();
    }
}

<?php

namespace App\Models;

use App\InnovationStatus;
use App\InnovationType;
use App\InnovationVisibility;
use Database\Factories\InnovationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['innovation_code', 'category_id', 'name', 'slug', 'type', 'tagline', 'short_description', 'description', 'problem_statement', 'solution_description', 'key_features', 'benefits', 'status', 'visibility', 'developed_at', 'is_featured', 'created_by', 'updated_by'])]
class Innovation extends Model
{
    /** @use HasFactory<InnovationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['type' => InnovationType::class, 'status' => InnovationStatus::class, 'visibility' => InnovationVisibility::class, 'key_features' => 'array', 'benefits' => 'array', 'developed_at' => 'date', 'is_featured' => 'boolean'];
    }

    /** @return BelongsTo<InnovationCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InnovationCategory::class, 'category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<InnovationMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(InnovationMedia::class);
    }

    /** @return HasMany<InnovationItem, $this> */
    public function innovationItems(): HasMany
    {
        return $this->hasMany(InnovationItem::class);
    }

    /** @return HasMany<InnovationTechnology, $this> */
    public function innovationTechnologies(): HasMany
    {
        return $this->hasMany(InnovationTechnology::class);
    }

    /** @return BelongsToMany<InventoryItem, $this> */
    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'innovation_items')->withPivot(['id', 'unit_id', 'quantity_required', 'usage_description', 'specification_notes', 'is_optional', 'sort_order'])->withTimestamps();
    }

    /** @return BelongsToMany<Technology, $this> */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'innovation_technologies')->withPivot(['id', 'version', 'purpose'])->withTimestamps();
    }
}

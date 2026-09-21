<?php

namespace App\Models;

use Database\Factories\InnovationCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_active', 'sort_order'])]
class InnovationCategory extends Model
{
    /** @use HasFactory<InnovationCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return HasMany<Innovation, $this> */
    public function innovations(): HasMany
    {
        return $this->hasMany(Innovation::class, 'category_id');
    }
}

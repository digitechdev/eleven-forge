<?php

namespace App\Models;

use App\Enums\InnovationMediaType;
use Database\Factories\InventoryItemMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * An image or video attached to an inventory item, stored on the public disk.
 *
 * @property int $id
 * @property int $inventory_item_id
 * @property InnovationMediaType $media_type
 * @property string $file_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int $sort_order
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['inventory_item_id', 'media_type', 'file_path', 'original_name', 'mime_type', 'size', 'sort_order', 'uploaded_by'])]
class InventoryItemMedia extends Model
{
    /** @use HasFactory<InventoryItemMediaFactory> */
    use HasFactory;

    /**
     * Most files one item may have.
     */
    public const int MAX_PER_ITEM = 10;

    protected function casts(): array
    {
        return ['media_type' => InnovationMediaType::class, 'size' => 'integer', 'sort_order' => 'integer'];
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}

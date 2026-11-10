<?php

namespace App\Models;

use App\Enums\InnovationMediaType;
use Database\Factories\InnovationMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $innovation_id
 * @property InnovationMediaType $media_type
 * @property string|null $file_path
 * @property string|null $video_url
 * @property string|null $title
 * @property string|null $caption
 * @property string|null $alt_text
 * @property bool $is_cover
 * @property bool $is_public
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['innovation_id', 'media_type', 'file_path', 'video_url', 'title', 'caption', 'alt_text', 'is_cover', 'is_public', 'sort_order'])]
class InnovationMedia extends Model
{
    /** @use HasFactory<InnovationMediaFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['url'];

    protected function casts(): array
    {
        return ['media_type' => InnovationMediaType::class, 'is_cover' => 'boolean', 'is_public' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return BelongsTo<Innovation, $this> */
    public function innovation(): BelongsTo
    {
        return $this->belongsTo(Innovation::class);
    }

    /**
     * Media allowed on the public showcase.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function public(Builder $query): void
    {
        $query->where('is_public', true);
    }

    /**
     * The public URL: the stored file for images, the external link for videos.
     *
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): ?string => match (true) {
            $this->media_type === InnovationMediaType::Video => $this->video_url,
            filled($this->file_path) => Storage::disk('public')->url($this->file_path),
            default => null,
        });
    }
}

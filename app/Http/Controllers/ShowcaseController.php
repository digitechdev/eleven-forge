<?php

namespace App\Http\Controllers;

use App\Enums\InnovationMediaType;
use App\Enums\InnovationVisibility;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\InnovationMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public showcase. Only PUBLIC innovations and media flagged public are ever exposed,
 * and only presentation fields are sent (no creators, BOM, costs, or internal media).
 */
class ShowcaseController extends Controller
{
    /**
     * Browse public innovations, featured first.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category']);

        return Inertia::render('showcase/Index', [
            'innovations' => Innovation::query()
                ->where('visibility', InnovationVisibility::Public)
                ->with([
                    'category:id,name,slug',
                    'media' => fn ($query) => $query
                        ->where('is_public', true)
                        ->where('media_type', InnovationMediaType::Image)
                        ->whereNotNull('file_path')
                        ->orderByDesc('is_cover')
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                ])
                ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                    fn (Builder $query) => $query
                        ->whereLike('name', "%{$search}%")
                        ->orWhereLike('tagline', "%{$search}%")
                        ->orWhereLike('short_description', "%{$search}%"),
                ))
                ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                    'category',
                    fn (Builder $query) => $query->where('slug', $slug),
                ))
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString()
                ->through(function (Innovation $innovation): array {
                    $cover = $innovation->media->first();

                    return [
                        'slug' => $innovation->slug,
                        'name' => $innovation->name,
                        'tagline' => $innovation->tagline,
                        'short_description' => $innovation->short_description,
                        'type' => $innovation->type->label(),
                        'status' => $innovation->status->label(),
                        'category' => $innovation->category?->name,
                        'is_featured' => $innovation->is_featured,
                        'cover' => $cover ? [
                            'url' => $this->fileUrl($cover),
                            'alt' => $cover->alt_text ?: $innovation->name,
                        ] : null,
                    ];
                }),
            'categories' => InnovationCategory::query()
                ->whereHas('innovations', fn (Builder $query) => $query->where('visibility', InnovationVisibility::Public))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['name', 'slug'])
                ->map(fn (InnovationCategory $category): array => ['value' => $category->slug, 'label' => $category->name]),
            'filters' => $filters,
        ]);
    }

    /**
     * Show a public innovation. Internal (and archived) innovations are not found.
     */
    public function show(Innovation $innovation): Response
    {
        abort_unless($innovation->visibility === InnovationVisibility::Public, 404);

        $innovation->load([
            'category:id,name,slug',
            'technologies' => fn ($query) => $query->orderBy('name'),
            'media' => fn ($query) => $query
                ->where('is_public', true)
                ->orderByDesc('is_cover')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        return Inertia::render('showcase/Show', [
            'innovation' => [
                'slug' => $innovation->slug,
                'name' => $innovation->name,
                'tagline' => $innovation->tagline,
                'short_description' => $innovation->short_description,
                'description' => $innovation->description,
                'problem_statement' => $innovation->problem_statement,
                'solution_description' => $innovation->solution_description,
                'key_features' => array_values(array_filter((array) $innovation->key_features, 'is_string')),
                'benefits' => array_values(array_filter((array) $innovation->benefits, 'is_string')),
                'type' => $innovation->type->label(),
                'status' => $innovation->status->label(),
                'category' => $innovation->category ? [
                    'name' => $innovation->category->name,
                    'slug' => $innovation->category->slug,
                ] : null,
                'is_featured' => $innovation->is_featured,
                'technologies' => $innovation->technologies->pluck('name')->values()->all(),
                'media' => $innovation->media
                    ->map(fn (InnovationMedia $media): ?array => $this->presentMedia($media, $innovation))
                    ->filter()
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * Public presentation of one media record, or null when it has nothing safe to show.
     *
     * @return array{id: int, type: 'image'|'video', title: string|null, caption: string|null, alt: string, url: string, embed_url: string|null}|null
     */
    private function presentMedia(InnovationMedia $media, Innovation $innovation): ?array
    {
        $isImage = $media->media_type === InnovationMediaType::Image;
        $url = $isImage ? $this->fileUrl($media) : $this->safeExternalUrl($media->video_url);

        if ($url === null) {
            return null;
        }

        return [
            'id' => $media->id,
            'type' => $isImage ? 'image' : 'video',
            'title' => $media->title,
            'caption' => $media->caption,
            'alt' => $media->alt_text ?: ($media->title ?: $innovation->name),
            'url' => $url,
            'embed_url' => $isImage ? null : $this->embedUrl($url),
        ];
    }

    /**
     * The public storage URL for a media file.
     */
    private function fileUrl(InnovationMedia $media): ?string
    {
        return $media->file_path ? Storage::disk('public')->url($media->file_path) : null;
    }

    /**
     * Only allow http(s) links so stored URLs cannot inject script schemes.
     */
    private function safeExternalUrl(?string $url): ?string
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) ? $url : null;
    }

    /**
     * A privacy-friendly embed URL for YouTube or Vimeo links, otherwise null.
     */
    private function embedUrl(string $url): ?string
    {
        $host = strtolower(preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)));
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $youtubeId = match (true) {
            $host === 'youtu.be' => ltrim($path, '/'),
            in_array($host, ['youtube.com', 'm.youtube.com'], true) && $path === '/watch' => (is_string($query['v'] ?? null) ? $query['v'] : ''),
            in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)
                && preg_match('#^/(embed|shorts|live)/([^/]+)#', $path, $matches) === 1 => $matches[2],
            default => null,
        };

        if ($youtubeId !== null) {
            return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $youtubeId) === 1
                ? "https://www.youtube-nocookie.com/embed/{$youtubeId}"
                : null;
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true) && preg_match('#/(?:video/)?(\d+)#', $path, $matches) === 1) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers\Innovations;

use App\Enums\InnovationMediaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Innovations\InnovationMediaRequest;
use App\Models\Innovation;
use App\Models\InnovationMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InnovationMediaController extends Controller
{
    /**
     * Upload an image or add a video link to the innovation's gallery.
     */
    public function store(InnovationMediaRequest $request, Innovation $innovation): RedirectResponse
    {
        $data = $request->validated();
        $type = InnovationMediaType::from($data['media_type']);

        $filePath = $type === InnovationMediaType::Image
            ? $request->file('file')->store("innovations/{$innovation->id}", 'public')
            : null;

        DB::transaction(function () use ($innovation, $data, $filePath): void {
            $media = $innovation->media()->create([
                ...Arr::except($data, ['file']),
                'file_path' => $filePath,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->keepSingleCover($media);
        });

        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Media added.')]);

        return back();
    }

    /**
     * Update a media entry's details.
     */
    public function update(InnovationMediaRequest $request, Innovation $innovation, InnovationMedia $media): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($media, $data): void {
            $media->update([...$data, 'sort_order' => $data['sort_order'] ?? 0]);

            $this->keepSingleCover($media);
        });

        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Media updated.')]);

        return back();
    }

    /**
     * Remove a media entry and its stored file.
     */
    public function destroy(Request $request, Innovation $innovation, InnovationMedia $media): RedirectResponse
    {
        Gate::authorize('delete', $media);

        if (filled($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Media removed.')]);

        return back();
    }

    /**
     * When this media is the cover, unset the cover flag on the innovation's other media.
     */
    private function keepSingleCover(InnovationMedia $media): void
    {
        if (! $media->is_cover) {
            return;
        }

        InnovationMedia::query()
            ->where('innovation_id', $media->innovation_id)
            ->whereKeyNot($media->id)
            ->where('is_cover', true)
            ->update(['is_cover' => false]);
    }
}

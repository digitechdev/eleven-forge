<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\InnovationMediaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryItemMediaRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemMediaRequest;
use App\Models\InventoryItem;
use App\Models\InventoryItemMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InventoryItemMediaController extends Controller
{
    /**
     * Upload an image or video to the end of the item's gallery.
     */
    public function store(StoreInventoryItemMediaRequest $request, InventoryItem $item): RedirectResponse
    {
        $file = $request->file('file');
        $mimeType = (string) $file->getMimeType();
        $path = $file->store("inventory-items/{$item->id}", 'public');

        $item->media()->create([
            'media_type' => str_starts_with($mimeType, 'image/') ? InnovationMediaType::Image : InnovationMediaType::Video,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'size' => $file->getSize(),
            'sort_order' => ((int) $item->media()->max('sort_order')) + ($item->media()->exists() ? 1 : 0),
            'uploaded_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Media uploaded.')]);

        return back();
    }

    /**
     * Move a file to a new position; the gallery is renumbered from zero.
     */
    public function update(UpdateInventoryItemMediaRequest $request, InventoryItem $item, InventoryItemMedia $media): RedirectResponse
    {
        DB::transaction(function () use ($request, $item, $media): void {
            $ordered = $item->media()->get()->reject(fn (InventoryItemMedia $entry): bool => $entry->is($media))->values();
            $position = min((int) $request->validated('sort_order'), $ordered->count());
            $ordered->splice($position, 0, [$media]);

            $ordered->each(function (InventoryItemMedia $entry, int $index): void {
                if ($entry->sort_order !== $index) {
                    $entry->update(['sort_order' => $index]);
                }
            });
        });

        return back();
    }

    /**
     * Remove a file from the gallery and from storage.
     */
    public function destroy(InventoryItem $item, InventoryItemMedia $media): RedirectResponse
    {
        Gate::authorize('update', $item);

        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Media deleted.')]);

        return back();
    }
}

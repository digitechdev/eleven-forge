<?php

namespace App\Http\Requests\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryItemMedia;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Upload one image or video to an item's gallery, up to InventoryItemMedia::MAX_PER_ITEM files.
 */
class StoreInventoryItemMediaRequest extends FormRequest
{
    /**
     * Largest image, in kilobytes (10 MB).
     */
    public const int MAX_IMAGE_KB = 10240;

    /**
     * Largest video, in kilobytes (100 MB).
     */
    public const int MAX_VIDEO_KB = 102400;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->inventoryItem());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isImage = str_starts_with((string) $this->file('file')?->getMimeType(), 'image/');

        return [
            'file' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm',
                'max:'.($isImage ? self::MAX_IMAGE_KB : self::MAX_VIDEO_KB),
            ],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->inventoryItem()->media()->count() >= InventoryItemMedia::MAX_PER_ITEM) {
                    $validator->errors()->add('file', __('An item can have at most :max media files. Delete one first.', ['max' => InventoryItemMedia::MAX_PER_ITEM]));
                }
            },
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimetypes' => __('Upload a JPEG, PNG, or WebP image, or an MP4 or WebM video.'),
            'file.max' => __('Images may not be larger than 10 MB and videos not larger than 100 MB.'),
        ];
    }

    private function inventoryItem(): InventoryItem
    {
        /** @var InventoryItem */
        return $this->route('item');
    }
}

<?php

namespace App\Http\Requests\Innovations;

use App\Enums\InnovationMediaType;
use App\Models\InnovationMedia;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InnovationMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $media = $this->route('media');

        return $media instanceof InnovationMedia
            ? $this->user()->can('update', $media)
            : $this->user()->can('create', [InnovationMedia::class, $this->route('innovation')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Images are uploaded on create only; editing changes the metadata (and a video's URL), never the type.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $media = $this->route('media');
        $isUpdate = $media instanceof InnovationMedia;
        $type = $isUpdate ? $media->media_type : InnovationMediaType::tryFrom((string) $this->input('media_type'));
        $isImage = $type === InnovationMediaType::Image;
        $isVideo = $type === InnovationMediaType::Video;

        return [
            'media_type' => $isUpdate ? ['exclude'] : ['required', Rule::enum(InnovationMediaType::class)],
            'file' => $isUpdate || ! $isImage
                ? ['exclude']
                : ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'video_url' => $isVideo ? ['required', 'url:http,https', 'max:255'] : ['exclude'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'alt_text' => [Rule::requiredIf($isImage), 'nullable', 'string', 'max:255'],
            'is_cover' => ['required', 'boolean'],
            'is_public' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
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
            'alt_text.required' => __('Describe the image for screen readers and search engines.'),
            'file.max' => __('Images may not be larger than 5 MB.'),
        ];
    }
}

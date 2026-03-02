<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deep_link_uri' => 'required|string|max:2048',
            'fallback_url' => 'nullable|url|max:2048',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:1000',
            'og_image_url' => 'nullable|url|max:2048',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}

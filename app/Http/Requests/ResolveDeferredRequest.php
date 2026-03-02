<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDeferredRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_agent' => 'required|string|max:1000',
            'platform' => 'required|in:ios,android',
            'language' => 'nullable|string|max:50',
            'screen_width' => 'nullable|integer|min:0',
            'screen_height' => 'nullable|integer|min:0',
            'timezone' => 'nullable|string|max:100',
        ];
    }
}

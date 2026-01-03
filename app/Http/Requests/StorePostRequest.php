<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'published_at' => ['nullable', 'date', 'after_or_equal:now'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('is_draft')) {
            $this->merge(['published_at' => null]);
        }

        if ($this->filled('published_at')) {
            $this->merge(['published_at' => $this->date('published_at')]);
        }
    }
}

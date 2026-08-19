<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PhoneEventIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'last_seen_order' => [
                'sometimes',
                'string',
                Rule::in(['asc', 'desc']),
            ],
        ];
    }

    public function sortDirection(): string
    {
        return $this->validated('last_seen_order', 'desc');
    }
}

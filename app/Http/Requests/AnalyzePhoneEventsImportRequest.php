<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzePhoneEventsImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
            'persist_summary' => ['nullable', 'boolean'],
        ];
    }

    public function persistSummary(): bool
    {
        return $this->boolean('persist_summary', true);
    }
}

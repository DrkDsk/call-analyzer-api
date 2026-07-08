<?php

namespace App\Http\Requests;

use App\Constants\PhoneEventsConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyzePhoneEventsAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['call'])],
            'group_by' => ['required', 'string', Rule::in(['date'])],
            'metric' => ['nullable', 'string', Rule::in(['count'])],
            'direction' => [
                'nullable',
                'string',
                Rule::in([
                    PhoneEventsConstants::CALL_DIRECTION_INCOMING,
                    PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
                    PhoneEventsConstants::CALL_DIRECTION_UNKNOWN,
                ]),
            ],
        ];
    }

    public function metric(): string
    {
        return $this->validated('metric', 'count');
    }

    public function direction(): ?string
    {
        return $this->validated('direction');
    }
}

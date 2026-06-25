<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneEventsImportSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_events' => $this['total_events'],
            'total_calls' => $this['total_calls'],
            'total_data' => $this['total_data'],

            'total_duration' => round($this['total_duration'] / 60, 2),
            'average_duration' => round($this['average_duration'] / 60, 2),

            'unique_contacts' => $this['unique_contacts'],
            'top_contact' => $this['top_contact'],
            'peak_hour' => $this['peak_hour'],
            'active_days' => $this['active_days'],
        ];
    }
}

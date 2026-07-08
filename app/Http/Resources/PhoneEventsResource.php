<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneEventsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'import_id' => $this->import_id,
            'contact' => $this->contact,
            'number' => $this->number,
            'first_seen_at' => optional($this->first_seen_at)
                ->format('Y-m-d\TH:i:sP'),

            'last_seen_at' => optional($this->last_seen_at)
                ->timezone('America/Mexico_City')
                ->format('Y-m-d\TH:i:sP'),
            'calls_count' => $this->calls_count,
            'call_direction' => $this->call_direction,
            'messages_count' => $this->messages_count,
            'data_count' => $this->data_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

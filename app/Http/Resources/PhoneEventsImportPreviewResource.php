<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneEventsImportPreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'import' => new PhoneEventsImportResource($this['import']),
            'summary' => new PhoneEventsImportSummaryResource($this['summary']),
        ];
    }
}

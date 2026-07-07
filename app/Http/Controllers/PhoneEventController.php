<?php

namespace App\Http\Controllers;

use App\Http\Resources\PhoneEventsResource;
use App\Models\Import;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PhoneEventController extends Controller
{
    public function index(Import $import): AnonymousResourceCollection
    {
        $events = $import->phoneEvents()
            ->orderByDesc('last_seen_at')
            ->paginate();

        return PhoneEventsResource::collection($events);
    }
}

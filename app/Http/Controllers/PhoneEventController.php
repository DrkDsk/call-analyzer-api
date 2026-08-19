<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhoneEventIndexRequest;
use App\Http\Resources\PhoneEventsResource;
use App\Models\Import;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PhoneEventController extends Controller
{
    public function index(PhoneEventIndexRequest $request, Import $import): AnonymousResourceCollection
    {
        $events = $import->phoneEvents()
            ->orderBy('last_seen_at', $request->sortDirection())
            ->paginate();

        return PhoneEventsResource::collection($events);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Import;
use Illuminate\Http\JsonResponse;

class PhoneEventController extends Controller
{
    public function index(Import $import): JsonResponse
    {
        $events = $import->phoneEvents()
            ->orderByDesc('last_seen_at')
            ->paginate();

        return response()->json($events);
    }
}

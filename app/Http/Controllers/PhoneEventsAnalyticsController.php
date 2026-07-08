<?php

namespace App\Http\Controllers;

use App\Actions\BuildPhoneEventsAnalyticsAction;
use App\Http\Requests\AnalyzePhoneEventsAnalyticsRequest;
use App\Models\Import;
use Illuminate\Http\JsonResponse;

class PhoneEventsAnalyticsController extends Controller
{
    public function __invoke(
        AnalyzePhoneEventsAnalyticsRequest $request,
        Import $import,
        BuildPhoneEventsAnalyticsAction $action,
    ): JsonResponse {
        return response()->json($action->execute(
            import: $import,
            type: $request->validated('type'),
            groupBy: $request->validated('group_by'),
            metric: $request->metric(),
            direction: $request->direction(),
        ));
    }
}

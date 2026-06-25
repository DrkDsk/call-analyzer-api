<?php

namespace App\Http\Controllers;

use App\Actions\AnalyzePhoneEventsImportAction;
use App\Exceptions\HeadersRequiredException;
use App\Http\Requests\AnalyzePhoneEventsImportRequest;
use App\Imports\PhoneRecordsImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PhoneEventsController extends Controller
{
    public function preview(
        AnalyzePhoneEventsImportRequest $request,
        AnalyzePhoneEventsImportAction $action
    ): JsonResponse {
        try {
            return response()->json($action->execute(
                $request->file('file'),
                $request->persistSummary(),
            ));
        } catch (HeadersRequiredException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Phone events import failed.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'No se pudo procesar el archivo.',
            ], 500);
        }
    }

    public function readFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new PhoneRecordsImport, $request->file('file'));

        return response()->json(['message' => 'File imported successfully']);
    }
}

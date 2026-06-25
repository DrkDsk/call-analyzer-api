<?php

namespace App\Http\Controllers;

use App\Actions\AnalyzePhoneEventsImportAction;
use App\Exceptions\HeadersRequiredException;
use App\Http\Requests\AnalyzePhoneEventsImportRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\PhoneEventsImportPreviewResource;
use Illuminate\Support\Facades\Log;
use Throwable;

class PhoneEventsController extends Controller
{
    public function preview(
        AnalyzePhoneEventsImportRequest $request,
        AnalyzePhoneEventsImportAction $action
    ) : PhoneEventsImportPreviewResource | ErrorResource {
        try {
            $result = $action->execute(
                $request->file('file'),
                $request->persistSummary(),
            );

            return new PhoneEventsImportPreviewResource($result);
        } catch (HeadersRequiredException $exception) {
            return new ErrorResource(
                $exception->getMessage(),
                422
            );
        } catch (Throwable $exception) {
            Log::error('Phone events import failed.', [
                'exception' => $exception,
            ]);

            return new ErrorResource(
                'No se pudo procesar el archivo.',
                500
            );
        }
    }
}

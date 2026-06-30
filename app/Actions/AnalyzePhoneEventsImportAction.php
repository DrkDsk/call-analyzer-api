<?php

namespace App\Actions;

use App\Exceptions\HeadersRequiredException;
use App\Imports\PhoneEventsPreviewImport;
use App\Models\Import;
use App\Support\PhoneEventsStatsAccumulator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AnalyzePhoneEventsImportAction
{
    public function __construct(
        private readonly PersistPhoneEventsFromRowsAction $persistPhoneEventsFromRows,
    ) {}

    /**
     * @throws Throwable
     * @throws HeadersRequiredException
     */
    public function execute(UploadedFile $file, bool $persistSummary = true): array
    {
        $path = $file->store('imports/phone-events');

        if ($path === false) {
            throw new \RuntimeException('No se pudo guardar el archivo temporal.');
        }

        $import = $persistSummary ? $this->createImport($file, $path) : null;

        try {
            $import?->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $accumulator = new PhoneEventsStatsAccumulator;
            $previewImport = new PhoneEventsPreviewImport($accumulator, import: $import);

            Excel::import(
                $previewImport,
                Storage::path($path)
            );

            if (! $previewImport->hasDetectedHeaders()) {
                throw new HeadersRequiredException('No se encontró una sección válida de eventos telefónicos en el archivo.');
            }

            $summary = $accumulator->result();

            $import?->update([
                'status' => 'completed',
                'total_rows' => $summary['total_events'],
                'processed_rows' => $summary['total_events'],
                'progress' => 100,
                'summary' => $summary,
                'finished_at' => now(),
            ]);

            if ($persistSummary && $import) {
                $this->persistPhoneEventsFromRows->execute($import, $previewImport->events());
            }

            return [
                'import' => $import ? [
                    'id' => $import->id,
                    'status' => $import->status,
                    'original_filename' => $import->original_filename,
                    'progress' => $import->progress,
                ] : null,
                'summary' => $summary,
            ];
        } catch (HeadersRequiredException $exception) {
            $this->markFailed($import, $exception->getMessage());

            throw $exception;
        } catch (Throwable $exception) {
            $this->markFailed($import, 'No se pudo procesar el archivo.');

            throw $exception;
        }
    }

    private function createImport(UploadedFile $file, string $path): Import
    {
        return Import::query()->create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'file_size' => $file->getSize() ?? 0,
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'status' => 'queued',
            'progress' => 0,
        ]);
    }

    private function markFailed(?Import $import, string $message): void
    {
        $import?->update([
            'status' => 'failed',
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }
}

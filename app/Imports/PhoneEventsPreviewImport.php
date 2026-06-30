<?php

namespace App\Imports;

use App\Data\PhoneEventData;
use App\Exceptions\HeadersRequiredException;
use App\Models\Import;
use App\Support\PhoneEventsHeaderDetector;
use App\Support\PhoneEventsStatsAccumulator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PhoneEventsPreviewImport implements SkipsEmptyRows, ToCollection, WithChunkReading
{
    private ?array $headersMap = null;

    /**
     * @var array<int, PhoneEventData>
     */
    private array $events = [];

    public function __construct(
        private readonly PhoneEventsStatsAccumulator $accumulator,
        private readonly PhoneEventsHeaderDetector $headerDetector = new PhoneEventsHeaderDetector,
        private readonly ?Import $import = null,
    ) {}

    /**
     * @throws HeadersRequiredException
     */
    public function collection(Collection $collection): void
    {
        $rows = $collection;

        if ($this->headersMap === null) {
            try {
                [$headerRowIndex, $this->headersMap] = $this->headerDetector->detect($rows);
            } catch (HeadersRequiredException) {
                return;
            }

            $rows = $rows->slice($headerRowIndex + 1);
        }

        $processedRows = 0;

        foreach ($rows as $row) {
            $event = PhoneEventData::fromExcelRow($row, $this->headersMap);

            if ($event === null) {
                continue;
            }

            $this->accumulator->add($event);
            $this->events[] = $event;
            $processedRows++;
        }

        if ($this->import && $processedRows > 0) {
            $this->import->increment('processed_rows', $processedRows);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function hasDetectedHeaders(): bool
    {
        return $this->headersMap !== null;
    }

    /**
     * @return array<int, PhoneEventData>
     */
    public function events(): array
    {
        return $this->events;
    }
}

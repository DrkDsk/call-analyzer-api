<?php

namespace App\Imports;

use App\Exceptions\HeadersRequiredException;
use App\Support\PhoneEventsHeaderDetector;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

readonly class PhoneRecordsImport implements ToCollection
{
    /**
     * @param PhoneEventsHeaderDetector $headerDetector
     */
    public function __construct(
        private PhoneEventsHeaderDetector $headerDetector = new PhoneEventsHeaderDetector,
    ) {}

    /**
     * @throws HeadersRequiredException
     */
    public function collection(Collection $collection): array
    {
        [$headerRowIndex, $columns] = $this->findHeaderRow($collection);
        $data = [];

        foreach ($collection->slice($headerRowIndex + 1) as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            foreach ($columns as $columnIndex => $fieldName) {
                $data[$fieldName] = $row[$columnIndex] ?? null;
            }
        }

        return $data;
    }

    /**
     * @throws HeadersRequiredException
     */
    private function findHeaderRow(Collection $rows): array
    {
        [$rowIndex, $headersMap] = $this->headerDetector->detect($rows);

        return [$rowIndex, array_flip($headersMap)];
    }

    private function normalizeHeader(mixed $value): string
    {
        return $this->headerDetector->normalizeHeader($value);
    }

    private function isEmptyRow(Collection $row): bool
    {
        return $row
            ->filter(fn ($value) => filled($value))
            ->isEmpty();
    }
}

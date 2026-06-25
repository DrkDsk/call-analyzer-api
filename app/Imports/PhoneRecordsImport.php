<?php

namespace App\Imports;

use App\Exceptions\HeadersRequiredException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class PhoneRecordsImport implements ToCollection
{
    /**
    * @param Collection $collection
    */

    private array $headerMap = [
        'telefono' => 'phone',
        'tipo' => 'type',
        'numero a' => 'number_a',
        'numero b' => 'number_b',
        'fecha' => 'date',
        'hora' => 'time',
        'duracion' => 'duration',
        'imei' => 'imei',
        'ubicacion geografica latitud longitud' => 'geo_location',
        'azimuth' => 'azimuth',
    ];

    private array $requiredFields = [
        'phone',
        'type',
        'number_a',
        'number_b',
        'date',
        'time',
        'duration',
        'imei',
        'geo_location',
        'azimuth',
    ];

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

        logger($data);

        return $data;
    }

    /**
     * @throws HeadersRequiredException
     */
    private function findHeaderRow(Collection $rows): array
    {
        foreach ($rows as $rowIndex => $row) {
            $columns = [];

            foreach ($row as $columnIndex => $cellValue) {
                $normalizedHeader = $this->normalizeHeader($cellValue);

                if (isset($this->headerMap[$normalizedHeader])) {
                    $columns[$columnIndex] = $this->headerMap[$normalizedHeader];
                }
            }

            $foundFields = array_unique(array_values($columns));

            $hasAllRequiredHeaders = empty(array_diff(
                $this->requiredFields,
                $foundFields
            ));

            if ($hasAllRequiredHeaders) {
                return [$rowIndex, $columns];
            }
        }

        throw new HeadersRequiredException("No se encontró la fila de encabezados requerida en el archivo Excel.");
    }

    private function normalizeHeader(mixed $value): string
    {
        $value = trim((string) $value);

        $value = Str::of($value)
            ->ascii()
            ->lower()
            ->toString();

        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function isEmptyRow(Collection $row): bool
    {
        return $row
            ->filter(fn ($value) => filled($value))
            ->isEmpty();
    }
}

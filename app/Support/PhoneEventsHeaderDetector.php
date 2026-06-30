<?php

namespace App\Support;

use App\Exceptions\HeadersRequiredException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PhoneEventsHeaderDetector
{
    private const HEADER_MAP = [
        'telefono' => 'phone',
        'tipo' => 'type',
        'numero a' => 'number_a',
        'numero b' => 'number_b',
        'fecha' => 'date',
        'hora' => 'time',
        'duracion' => 'duration',
        'durac seg' => 'duration',
        'imei' => 'imei',
        'ubicacion geografica latitud longitud' => 'location',
        'ubicacion geografica' => 'location',
        'latitud longitud' => 'location',
        'azimuth' => 'azimuth',
        'azimut' => 'azimuth',
    ];

    private const REQUIRED_FIELDS = [
        'phone',
        'type',
        'number_a',
        'number_b',
        'date',
        'time',
        'duration',
        'imei',
        'location',
        'azimuth',
    ];

    private const REQUIRED_DATA_FIELDS = [
        'phone',
        'type',
        'number_a',
        'number_b',
        'date',
        'time',
    ];

    private const NO_DETAILS_TEXT = 'sin detalle para el periodo seleccionado';

    /**
     * @return array{0:int, 1:array<string, int>}
     *
     * @throws HeadersRequiredException
     */
    public function detect(Collection $rows): array
    {
        foreach ($rows as $rowIndex => $row) {
            $headersMap = $this->mapRow($row instanceof Collection ? $row : collect($row));

            if ($this->hasRequiredHeaders($headersMap) && $this->hasValidDataAfterHeader($rows, (int) $rowIndex, $headersMap)) {
                return [$rowIndex, $headersMap];
            }
        }

        throw new HeadersRequiredException('No se encontró una sección válida de eventos telefónicos en el archivo.');
    }

    public function hasValidDataAfterHeader(Collection $rows, int $headerRowIndex, array $headersMap): bool
    {
        foreach ($rows->slice($headerRowIndex + 1) as $row) {
            $row = $row instanceof Collection ? $row : collect($row);

            if ($this->isEmptyRow($row)) {
                continue;
            }

            if ($this->isNoDetailsRow($row) || $this->isHeaderRow($row)) {
                return false;
            }

            if ($this->isDataRow($row, $headersMap)) {
                return true;
            }
        }

        return false;
    }

    public function isHeaderRow(array|Collection $row): bool
    {
        $row = $row instanceof Collection ? $row : collect($row);

        return $this->hasRequiredHeaders($this->mapRow($row));
    }

    public function isNoDetailsRow(array|Collection $row): bool
    {
        $row = $row instanceof Collection ? $row : collect($row);

        return $row
            ->filter(fn (mixed $value): bool => filled($value))
            ->contains(fn (mixed $value): bool => str_contains($this->normalizeHeader($value), self::NO_DETAILS_TEXT));
    }

    public function isEmptyRow(array|Collection $row): bool
    {
        $row = $row instanceof Collection ? $row : collect($row);

        return $row->filter(fn (mixed $value): bool => filled(trim((string) $value)))->isEmpty();
    }

    /**
     * @param  array<string, int>  $headersMap
     */
    public function isDataRow(array|Collection $row, array $headersMap): bool
    {
        $row = $row instanceof Collection ? $row : collect($row);

        if ($this->isEmptyRow($row) || $this->isNoDetailsRow($row)) {
            return false;
        }

        foreach (self::REQUIRED_DATA_FIELDS as $field) {
            if (! isset($headersMap[$field]) || blank(trim((string) $row->get($headersMap[$field])))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Collection<int, mixed>
     *
     * @throws HeadersRequiredException
     */
    public function extractRowsFromValidHeader(Collection $rows): Collection
    {
        [$headerRowIndex] = $this->detect($rows);

        return $rows->slice($headerRowIndex + 1)->values();
    }

    /**
     * @return array<string, int>
     */
    public function mapRow(Collection $row): array
    {
        $headersMap = [];

        foreach ($row as $columnIndex => $cellValue) {
            $normalizedHeader = $this->normalizeHeader($cellValue);

            if (isset(self::HEADER_MAP[$normalizedHeader])) {
                $headersMap[self::HEADER_MAP[$normalizedHeader]] = (int) $columnIndex;
            }
        }

        return $headersMap;
    }

    /**
     * @param  array<string, int>  $headersMap
     */
    public function hasRequiredHeaders(array $headersMap): bool
    {
        return empty(array_diff(self::REQUIRED_FIELDS, array_keys($headersMap)));
    }

    public function normalizeHeader(mixed $value): string
    {
        $value = trim((string) $value);

        $value = Str::of($value)
            ->ascii()
            ->lower()
            ->toString();

        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?: '';

        return trim(preg_replace('/\s+/', ' ', $value) ?: '');
    }
}

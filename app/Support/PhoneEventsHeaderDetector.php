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
        'imei' => 'imei',
        'ubicacion geografica latitud longitud' => 'location',
        'ubicacion geografica' => 'location',
        'latitud longitud' => 'location',
        'azimuth' => 'azimuth',
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

    /**
     * @return array{0:int, 1:array<string, int>}
     *
     * @throws HeadersRequiredException
     */
    public function detect(Collection $rows): array
    {
        foreach ($rows as $rowIndex => $row) {
            $headersMap = $this->mapRow($row instanceof Collection ? $row : collect($row));

            if ($this->hasRequiredHeaders($headersMap)) {
                return [$rowIndex, $headersMap];
            }
        }

        throw new HeadersRequiredException('No se encontraron los encabezados requeridos en el archivo.');
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

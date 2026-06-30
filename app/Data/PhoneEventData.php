<?php

namespace App\Data;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

readonly class PhoneEventData
{
    public function __construct(
        public ?string $phone,
        public ?string $type,
        public ?string $numberA,
        public ?string $numberB,
        public ?string $date,
        public ?string $time,
        public int $duration,
        public ?string $imei,
        public ?float $latitude,
        public ?float $longitude,
        public ?float $azimuth,
        public ?string $occurredTime = null,
    ) {}

    /**
     * @param  array<string, int>  $headersMap
     */
    public static function fromExcelRow(array|Collection $row, array $headersMap): ?self
    {
        $row = $row instanceof Collection ? $row : collect($row);

        if (self::isEmptyRow($row)) {
            return null;
        }

        $location = self::cleanString(self::value($row, $headersMap, 'location'));
        [$latitude, $longitude] = self::parseLocation($location);

        [$time, $occurredTime] = self::parseTime(self::value($row, $headersMap, 'time'));

        $event = new self(
            phone: self::cleanString(self::value($row, $headersMap, 'phone')),
            type: self::normalizeType(self::value($row, $headersMap, 'type')),
            numberA: self::cleanString(self::value($row, $headersMap, 'number_a')),
            numberB: self::cleanString(self::value($row, $headersMap, 'number_b')),
            date: self::parseDate(self::value($row, $headersMap, 'date')),
            time: $time,
            duration: self::parseInteger(self::value($row, $headersMap, 'duration')),
            imei: self::cleanString(self::value($row, $headersMap, 'imei')),
            latitude: $latitude,
            longitude: $longitude,
            azimuth: self::parseDecimal(self::value($row, $headersMap, 'azimuth')),
            occurredTime: $occurredTime,
        );

        if (blank($event->phone) && blank($event->numberA) && blank($event->numberB) && blank($event->type)) {
            return null;
        }

        return $event;
    }

    /**
     * @param  array<string, int>  $headersMap
     */
    private static function value(Collection $row, array $headersMap, string $field): mixed
    {
        return isset($headersMap[$field]) ? $row->get($headersMap[$field]) : null;
    }

    private static function isEmptyRow(Collection $row): bool
    {
        return $row->filter(fn (mixed $value): bool => filled($value))->isEmpty();
    }

    private static function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', (string) $value) ?: '');

        return $value === '' ? null : $value;
    }

    private static function normalizeType(mixed $value): ?string
    {
        $value = self::cleanString($value);

        return $value ? mb_strtoupper($value) : null;
    }

    private static function parseInteger(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $value = self::cleanString($value);

        if ($value === null) {
            return 0;
        }

        preg_match('/-?\d+/', $value, $matches);

        return isset($matches[0]) ? (int) $matches[0] : 0;
    }

    private static function parseDecimal(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = self::cleanString($value);

        if ($value === null) {
            return null;
        }

        preg_match('/-?\d+(?:[.,]\d+)?/', $value, $matches);

        return isset($matches[0]) ? (float) str_replace(',', '.', $matches[0]) : null;
    }

    private static function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return self::cleanString($value);
        }
    }

    /**
     * @return array{0:?string, 1:?string}
     */
    private static function parseTime(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [null, null];
        }

        try {
            $time = is_numeric($value)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))
                : Carbon::parse((string) $value);

            return [$time->format('H:00'), $time->format('H:i:s')];
        } catch (Throwable) {
            $value = self::cleanString($value);

            return [$value, $value];
        }
    }

    /**
     * @return array{0:?float, 1:?float}
     */
    private static function parseLocation(?string $value): array
    {
        if ($value === null) {
            return [null, null];
        }

        $parts = preg_split('/\s*\/\s*/', $value) ?: [];

        if (count($parts) >= 2) {
            return [
                self::parseCoordinate($parts[0]),
                self::parseCoordinate($parts[1]),
            ];
        }

        preg_match_all('/-?\d+(?:[.,]\d+)?/', $value, $matches);

        if (count($matches[0]) >= 2) {
            return [
                (float) str_replace(',', '.', $matches[0][0]),
                (float) str_replace(',', '.', $matches[0][1]),
            ];
        }

        return [null, null];
    }

    private static function parseCoordinate(string $value): ?float
    {
        $value = trim($value);

        preg_match_all('/\d+(?:[.,]\d+)?/', $value, $matches);

        if ($matches[0] === []) {
            return null;
        }

        $degrees = (float) str_replace(',', '.', $matches[0][0]);
        $minutes = isset($matches[0][1]) ? (float) str_replace(',', '.', $matches[0][1]) : 0.0;
        $seconds = isset($matches[0][2]) ? (float) str_replace(',', '.', $matches[0][2]) : 0.0;

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if (preg_match('/[SWO]/iu', $value)) {
            $decimal *= -1;
        }

        return round($decimal, 7);
    }
}

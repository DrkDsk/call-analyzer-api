<?php

namespace App\Actions;

use App\Data\PhoneEventData;
use App\Models\Import;
use App\Models\PhoneEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class PersistPhoneEventsFromRowsAction
{
    /**
     * @param  iterable<PhoneEventData>  $rows
     */
    public function execute(Import $import, iterable $rows): void
    {
        $groupedRows = [];

        foreach ($rows as $row) {
            if (! $row instanceof PhoneEventData || blank($row->numberA) || blank($row->numberB)) {
                continue;
            }

            $key = $row->numberA.'|'.$row->numberB;
            $occurredAt = $this->occurredAt($row);

            $groupedRows[$key] ??= [
                'import_id' => $import->id,
                'contact' => $row->numberA,
                'number' => $row->numberB,
                'first_seen_at' => $occurredAt,
                'last_seen_at' => $occurredAt,
                'calls_count' => 0,
                'messages_count' => 0,
                'data_count' => 0,
            ];

            if ($occurredAt !== null) {
                $firstSeenAt = $groupedRows[$key]['first_seen_at'];
                $lastSeenAt = $groupedRows[$key]['last_seen_at'];

                if ($firstSeenAt === null || $occurredAt->lt($firstSeenAt)) {
                    $groupedRows[$key]['first_seen_at'] = $occurredAt;
                }

                if ($lastSeenAt === null || $occurredAt->gt($lastSeenAt)) {
                    $groupedRows[$key]['last_seen_at'] = $occurredAt;
                }
            }

            match ($this->classifyType($row->type)) {
                'call' => $groupedRows[$key]['calls_count']++,
                'message' => $groupedRows[$key]['messages_count']++,
                'data' => $groupedRows[$key]['data_count']++,
                default => null,
            };
        }

        $now = now();

        collect($groupedRows)
            ->map(static fn (array $row): array => [
                ...$row,
                'first_seen_at' => $row['first_seen_at']?->toDateTimeString(),
                'last_seen_at' => $row['last_seen_at']?->toDateTimeString(),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(1000)
            ->each(static function ($chunk): void {
                PhoneEvent::query()->upsert(
                    $chunk->values()->all(),
                    ['import_id', 'contact', 'number'],
                    [
                        'first_seen_at',
                        'last_seen_at',
                        'calls_count',
                        'messages_count',
                        'data_count',
                        'updated_at',
                    ]
                );
            });
    }

    private function occurredAt(PhoneEventData $row): ?Carbon
    {
        if (blank($row->date)) {
            return null;
        }

        $date = trim((string) $row->date);
        $timeValue = $row->occurredTime ?? $row->time;
        $time = filled($timeValue) ? trim((string) $timeValue) : '00:00:00';

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/y H:i:s', 'd/m/y H:i', 'd/m/Y H:i:s', 'd/m/Y H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, "{$date} {$time}");
            } catch (Throwable) {
                //
            }
        }

        try {
            return Carbon::parse("{$date} {$time}");
        } catch (Throwable) {
            return null;
        }
    }

    private function classifyType(?string $type): ?string
    {
        $type = Str::of((string) $type)->ascii()->upper()->trim()->toString();

        return match (true) {
            in_array($type, ['DATOS', 'DATO', 'DATA'], true) => 'data',
            in_array($type, ['SMS', 'MENSAJE', 'MENSAJES'], true) => 'message',
            in_array($type, ['LLAMADA', 'LLAMADAS', 'VOZ', 'CALL'], true) => 'call',
            default => null,
        };
    }
}

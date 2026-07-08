<?php

namespace App\Actions;

use App\Constants\PhoneEventsConstants;
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
                'call_direction' => null,
                'has_mixed_call_directions' => false,
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
                'call' => $this->addCall($groupedRows[$key], $this->classifyCallDirection($row->type)),
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
            ->map(static function (array $row): array {
                unset($row['has_mixed_call_directions']);

                return $row;
            })
            ->chunk(1000)
            ->each(static function ($chunk): void {
                PhoneEvent::query()->upsert(
                    $chunk->values()->all(),
                    ['import_id', 'contact', 'number'],
                    [
                        'first_seen_at',
                        'last_seen_at',
                        'calls_count',
                        'call_direction',
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
                return Carbon::createFromFormat($format, "$date $time");
            } catch (Throwable) {
                //
            }
        }

        try {
            return Carbon::parse("$date $time");
        } catch (Throwable) {
            return null;
        }
    }

    private function classifyType(?string $type): ?string
    {
        $type = Str::of((string) $type)->ascii()->upper()->trim()->toString();

        return match (true) {
            Str::contains($type, PhoneEventsConstants::IS_DATA_ARRAY, true) => 'data',
            Str::contains($type, PhoneEventsConstants::IS_MESSAGE_ARRAY, true) => 'message',
            Str::contains($type, PhoneEventsConstants::IS_CALL_ARRAY, true) => 'call',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function addCall(array &$row, ?string $callDirection): void
    {
        $row['calls_count']++;

        if ($callDirection === null || $row['has_mixed_call_directions']) {
            return;
        }

        if ($row['call_direction'] === null) {
            $row['call_direction'] = $callDirection;

            return;
        }

        if ($row['call_direction'] !== $callDirection) {
            $row['call_direction'] = null;
            $row['has_mixed_call_directions'] = true;
        }
    }

    private function classifyCallDirection(?string $type): ?string
    {
        $type = Str::of((string) $type)->ascii()->upper()->trim()->toString();

        return match (true) {
            Str::contains($type, PhoneEventsConstants::IS_INCOMING_CALL_ARRAY, true) => PhoneEventsConstants::CALL_DIRECTION_INCOMING,
            Str::contains($type, PhoneEventsConstants::IS_OUTGOING_CALL_ARRAY, true) => PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
            default => null,
        };
    }
}

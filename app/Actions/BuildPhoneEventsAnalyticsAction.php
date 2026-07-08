<?php

namespace App\Actions;

use App\Constants\PhoneEventsConstants;
use App\Models\Import;
use Illuminate\Support\Facades\DB;

class BuildPhoneEventsAnalyticsAction
{
    /**
     * @return array{
     *     data: list<array{date: string, label: string, incoming: int, outgoing: int, unknown: int, total: int}>,
     *     meta: array{type: string, group_by: string, metric: string, direction?: string, total: int, incoming: int, outgoing: int, unknown: int}
     * }
     */
    public function execute(
        Import $import,
        string $type,
        string $groupBy,
        string $metric = 'count',
        ?string $direction = null,
    ): array {
        if ($type !== 'call' || $groupBy !== 'date' || $metric !== 'count') {
            return $this->emptyResponse($type, $groupBy, $metric, $direction);
        }

        $query = $import->phoneEvents()
            ->where('calls_count', '>', 0)
            ->whereNotNull('first_seen_at');

        $this->applyDirectionFilter($query, $direction);

        $rows = $query
            ->selectRaw('DATE(first_seen_at) as event_date')
            ->selectRaw(
                'SUM(CASE WHEN call_direction = ? THEN calls_count ELSE 0 END) as incoming',
                [PhoneEventsConstants::CALL_DIRECTION_INCOMING],
            )
            ->selectRaw(
                'SUM(CASE WHEN call_direction = ? THEN calls_count ELSE 0 END) as outgoing',
                [PhoneEventsConstants::CALL_DIRECTION_OUTGOING],
            )
            ->selectRaw(
                'SUM(CASE WHEN call_direction IS NULL OR call_direction = ? OR call_direction NOT IN (?, ?) THEN calls_count ELSE 0 END) as unknown',
                [
                    '',
                    PhoneEventsConstants::CALL_DIRECTION_INCOMING,
                    PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
                ],
            )
            ->selectRaw('SUM(calls_count) as total')
            ->groupBy(DB::raw('DATE(first_seen_at)'))
            ->orderBy('event_date')
            ->get();

        $data = $rows
            ->map(fn ($row): array => [
                'date' => (string) $row->event_date,
                'label' => $this->labelForDate((string) $row->event_date),
                'incoming' => (int) $row->incoming,
                'outgoing' => (int) $row->outgoing,
                'unknown' => (int) $row->unknown,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return [
            'data' => $data,
            'meta' => [
                'type' => $type,
                'group_by' => $groupBy,
                'metric' => $metric,
                ...($direction !== null ? ['direction' => $direction] : []),
                'total' => array_sum(array_column($data, 'total')),
                'incoming' => array_sum(array_column($data, 'incoming')),
                'outgoing' => array_sum(array_column($data, 'outgoing')),
                'unknown' => array_sum(array_column($data, 'unknown')),
            ],
        ];
    }

    /**
     * @return array{
     *     data: list<array{date: string, label: string, incoming: int, outgoing: int, unknown: int, total: int}>,
     *     meta: array{type: string, group_by: string, metric: string, direction?: string, total: int, incoming: int, outgoing: int, unknown: int}
     * }
     */
    private function emptyResponse(string $type, string $groupBy, string $metric, ?string $direction = null): array
    {
        return [
            'data' => [],
            'meta' => [
                'type' => $type,
                'group_by' => $groupBy,
                'metric' => $metric,
                ...($direction !== null ? ['direction' => $direction] : []),
                'total' => 0,
                'incoming' => 0,
                'outgoing' => 0,
                'unknown' => 0,
            ],
        ];
    }

    private function applyDirectionFilter($query, ?string $direction): void
    {
        if ($direction === PhoneEventsConstants::CALL_DIRECTION_INCOMING) {
            $query->where('call_direction', PhoneEventsConstants::CALL_DIRECTION_INCOMING);
        }

        if ($direction === PhoneEventsConstants::CALL_DIRECTION_OUTGOING) {
            $query->where('call_direction', PhoneEventsConstants::CALL_DIRECTION_OUTGOING);
        }

        if ($direction === PhoneEventsConstants::CALL_DIRECTION_UNKNOWN) {
            $query->where(function ($query): void {
                $query
                    ->whereNull('call_direction')
                    ->orWhere('call_direction', '')
                    ->orWhereNotIn('call_direction', [
                        PhoneEventsConstants::CALL_DIRECTION_INCOMING,
                        PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
                    ]);
            });
        }
    }

    private function labelForDate(string $date): string
    {
        $months = [
            1 => 'ene',
            2 => 'feb',
            3 => 'mar',
            4 => 'abr',
            5 => 'may',
            6 => 'jun',
            7 => 'jul',
            8 => 'ago',
            9 => 'sep',
            10 => 'oct',
            11 => 'nov',
            12 => 'dic',
        ];

        [, $month, $day] = array_map('intval', explode('-', $date));

        return sprintf('%02d %s', $day, $months[$month]);
    }
}

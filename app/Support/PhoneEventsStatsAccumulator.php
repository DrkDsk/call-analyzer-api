<?php

namespace App\Support;

use App\Data\PhoneEventData;
use Illuminate\Support\Str;

class PhoneEventsStatsAccumulator
{
    public int $totalEvents = 0;

    public int $totalCalls = 0;

    public int $totalData = 0;

    public int $totalDuration = 0;

    public array $contacts = [];

    public array $hours = [];

    public array $days = [];

    public function add(PhoneEventData $event): void
    {
        $this->totalEvents++;
        $this->totalDuration += $event->duration;

        if ($this->isCall($event->type)) {
            $this->totalCalls++;
        }

        if ($this->isData($event->type)) {
            $this->totalData++;
        }

        if (filled($event->numberB) && ! $this->isDataContact($event->numberB)) {
            $this->contacts[$event->numberB] = ($this->contacts[$event->numberB] ?? 0) + 1;
        }

        if (filled($event->time)) {
            $this->hours[$event->time] = ($this->hours[$event->time] ?? 0) + 1;
        }

        if (filled($event->date)) {
            $this->days[$event->date] = ($this->days[$event->date] ?? 0) + 1;
        }
    }

    public function result(): array
    {
        arsort($this->contacts);
        arsort($this->hours);
        arsort($this->days);

        return [
            'total_events' => $this->totalEvents,
            'total_calls' => $this->totalCalls,
            'total_data' => $this->totalData,
            'total_duration' => $this->totalDuration,
            'average_duration' => $this->totalEvents > 0
                ? round($this->totalDuration / $this->totalEvents, 2)
                : 0,
            'unique_contacts' => count($this->contacts),
            'top_contact' => array_key_first($this->contacts),
            'peak_hour' => array_key_first($this->hours),
            'active_days' => count($this->days),
        ];
    }

    private function isCall(?string $type): bool
    {
        $normalized = $this->normalize($type);

        return Str::of($normalized)->contains([
            'VOZ',
            'LLAMADA',
            'CALL',
        ]);
    }

    private function isData(?string $type): bool
    {
        $normalized = $this->normalize($type);

        return Str::of($normalized)->contains(['DATOS', 'DATA', 'INTERNET']);
    }

    private function isDataContact(string $contact): bool
    {
        $normalized = $this->normalize($contact);

        return Str::of($normalized)->contains(['DATOS', 'DATA', 'INTERNET']);
    }

    private function normalize(?string $value): string
    {
        return Str::of((string) $value)->ascii()->upper()->trim()->toString();
    }
}

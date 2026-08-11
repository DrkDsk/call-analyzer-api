<?php

namespace App\Support;

use App\Constants\PhoneEventsConstants;
use App\Data\PhoneEventData;
use Illuminate\Support\Str;

class PhoneEventsStatsAccumulator
{
    public function __construct(
        private readonly PhoneEventTypeClassifier $typeClassifier = new PhoneEventTypeClassifier,
    ) {}

    public int $totalEvents = 0;

    public int $totalCalls = 0;

    public int $incomingCallsCount = 0;

    public int $outgoingCallsCount = 0;

    public int $totalMessages = 0;

    public int $totalData = 0;

    public int $totalDuration = 0;

    public array $contacts = [];

    public array $hours = [];

    public array $days = [];

    public function add(PhoneEventData $event): void
    {
        $this->totalEvents++;
        $this->totalDuration += $event->duration;

        if ($this->typeClassifier->isCall($event->type)) {
            $this->totalCalls++;

            if ($this->typeClassifier->isIncomingCall($event->type)) {
                $this->incomingCallsCount++;
            }

            if ($this->typeClassifier->isOutgoingCall($event->type)) {
                $this->outgoingCallsCount++;
            }
        }

        if ($this->isMessage($event->type)) {
            $this->totalMessages++;
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
            'total_messages' => $this->totalMessages,
            'total_data' => $this->totalData,
            'incoming_calls_count' => $this->incomingCallsCount,
            'outgoing_calls_count' => $this->outgoingCallsCount,
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

    public function isMessage(?string $type): bool
    {
        $normalized = $this->typeClassifier->normalize($type);

        return Str::of($normalized)->contains(PhoneEventsConstants::IS_MESSAGE_ARRAY);
    }

    private function isData(?string $type): bool
    {
        $normalized = $this->typeClassifier->normalize($type);

        return Str::of($normalized)->contains(PhoneEventsConstants::IS_DATA_ARRAY);
    }

    private function isDataContact(string $contact): bool
    {
        $normalized = $this->typeClassifier->normalize($contact);

        return Str::of($normalized)->contains(PhoneEventsConstants::IS_DATA_ARRAY);
    }
}

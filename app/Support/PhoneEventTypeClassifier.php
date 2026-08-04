<?php

namespace App\Support;

use App\Constants\PhoneEventsConstants;
use Illuminate\Support\Str;

class PhoneEventTypeClassifier
{
    public function normalize(?string $type): string
    {
        return Str::of((string) $type)->ascii()->upper()->trim()->toString();
    }

    public function callDirection(?string $type): ?string
    {
        $normalized = $this->normalize($type);

        return match (true) {
            Str::contains($normalized, PhoneEventsConstants::IS_INCOMING_CALL_ARRAY, true) => PhoneEventsConstants::CALL_DIRECTION_INCOMING,
            Str::contains($normalized, PhoneEventsConstants::IS_OUTGOING_CALL_ARRAY, true) => PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
            default => null,
        };
    }

    public function isIncomingCall(?string $type): bool
    {
        return Str::contains(
            $this->normalize($type),
            PhoneEventsConstants::IS_INCOMING_CALL_ARRAY,
            true,
        );
    }

    public function isOutgoingCall(?string $type): bool
    {
        return Str::contains(
            $this->normalize($type),
            PhoneEventsConstants::IS_OUTGOING_CALL_ARRAY,
            true,
        );
    }

    public function isCall(?string $type): bool
    {
        return Str::contains(
            $this->normalize($type),
            PhoneEventsConstants::IS_CALL_ARRAY,
            true,
        );
    }
}

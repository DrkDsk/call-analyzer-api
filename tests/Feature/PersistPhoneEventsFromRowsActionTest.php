<?php

use App\Actions\PersistPhoneEventsFromRowsAction;
use App\Data\PhoneEventData;
use App\Models\Import;
use App\Models\PhoneEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists grouped phone event analysis by import contact and number', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    (new PersistPhoneEventsFromRowsAction)->execute($import, [
        new PhoneEventData('9611', 'VOZ', '9611', '9612', '2026-06-25', '10:00', 30, null, null, null, null),
        new PhoneEventData('9611', 'sms', '9611', '9612', '2026-06-24', '09:30:00', 0, null, null, null, null),
        new PhoneEventData('9611', 'DATOS', '9611', 'internet.itelcel.com', '07/11/20', '10:56:38', 3191, null, null, null, null),
        new PhoneEventData('9611', 'dato', '9611', 'internet.itelcel.com', '08/11/20', '11:00:00', 300, null, null, null, null),
    ]);

    expect(PhoneEvent::query()->count())->toBe(2);

    $voice = PhoneEvent::query()
        ->where('import_id', $import->id)
        ->where('contact', '9611')
        ->where('number', '9612')
        ->firstOrFail();

    expect($voice->first_seen_at->toDateTimeString())->toBe('2026-06-24 09:30:00')
        ->and($voice->last_seen_at->toDateTimeString())->toBe('2026-06-25 10:00:00')
        ->and($voice->calls_count)->toBe(1)
        ->and($voice->messages_count)->toBe(1)
        ->and($voice->data_count)->toBe(0);

    $data = PhoneEvent::query()
        ->where('import_id', $import->id)
        ->where('contact', '9611')
        ->where('number', 'internet.itelcel.com')
        ->firstOrFail();

    expect($data->first_seen_at->toDateTimeString())->toBe('2020-11-07 10:56:38')
        ->and($data->last_seen_at->toDateTimeString())->toBe('2020-11-08 11:00:00')
        ->and($data->calls_count)->toBe(0)
        ->and($data->messages_count)->toBe(0)
        ->and($data->data_count)->toBe(2);
});

it('updates existing grouped phone event analysis with upsert', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    $action = new PersistPhoneEventsFromRowsAction;

    $action->execute($import, [
        new PhoneEventData('9611', 'CALL', '9611', '9612', '2026-06-25', '10:00', 30, null, null, null, null),
    ]);

    $action->execute($import, [
        new PhoneEventData('9611', 'MENSAJE', '9611', '9612', '2026-06-26', '11:00', 30, null, null, null, null),
    ]);

    $phoneEvent = PhoneEvent::query()->firstOrFail();

    expect(PhoneEvent::query()->count())->toBe(1)
        ->and($phoneEvent->first_seen_at->toDateTimeString())->toBe('2026-06-26 11:00:00')
        ->and($phoneEvent->last_seen_at->toDateTimeString())->toBe('2026-06-26 11:00:00')
        ->and($phoneEvent->calls_count)->toBe(0)
        ->and($phoneEvent->messages_count)->toBe(1);
});

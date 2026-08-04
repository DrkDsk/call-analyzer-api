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
        new PhoneEventData('9611', 'VOZ ENTRANTE', '9611', '9612', '2026-06-25', '10:00', 30, null, null, null, null),
        new PhoneEventData('9611', 'sms', '9611', '9612', '2026-06-24', '09:30:00', 0, null, null, null, null),
        new PhoneEventData('9611', 'DATOS', '9611', 'internet.itelcel.com', '07/11/20', '10:56:38', 3191, null, null, null, null),
        new PhoneEventData('9611', 'dato', '9611', 'internet.itelcel.com', '08/11/20', '11:00:00', 300, null, null, null, null),
    ]);

    expect(PhoneEvent::query()->count())->toBe(3);

    $voice = PhoneEvent::query()
        ->where('import_id', $import->id)
        ->where('contact', '9611')
        ->where('number', '9612')
        ->where('call_direction', 'incoming')
        ->firstOrFail();

    expect($voice->first_seen_at->toDateTimeString())->toBe('2026-06-25 10:00:00')
        ->and($voice->last_seen_at->toDateTimeString())->toBe('2026-06-25 10:00:00')
        ->and($voice->calls_count)->toBe(1)
        ->and($voice->call_direction)->toBe('incoming')
        ->and($voice->messages_count)->toBe(0)
        ->and($voice->data_count)->toBe(0);

    $message = PhoneEvent::query()
        ->where('import_id', $import->id)
        ->where('contact', '9611')
        ->where('number', '9612')
        ->whereNull('call_direction')
        ->firstOrFail();

    expect($message->calls_count)->toBe(0)
        ->and($message->messages_count)->toBe(1)
        ->and($message->data_count)->toBe(0);

    $data = PhoneEvent::query()
        ->where('import_id', $import->id)
        ->where('contact', '9611')
        ->where('number', 'internet.itelcel.com')
        ->firstOrFail();

    expect($data->first_seen_at->toDateTimeString())->toBe('2020-11-07 10:56:38')
        ->and($data->last_seen_at->toDateTimeString())->toBe('2020-11-08 11:00:00')
        ->and($data->calls_count)->toBe(0)
        ->and($data->call_direction)->toBeNull()
        ->and($data->messages_count)->toBe(0)
        ->and($data->data_count)->toBe(2);
});

it('persists outgoing call direction for grouped phone event analysis', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    (new PersistPhoneEventsFromRowsAction)->execute($import, [
        new PhoneEventData('9611', 'VOZ SALIENTE', '9611', '9614', '2026-06-25', '10:00', 30, null, null, null, null),
    ]);

    $phoneEvent = PhoneEvent::query()->firstOrFail();

    expect($phoneEvent->calls_count)->toBe(1)
        ->and($phoneEvent->call_direction)->toBe('outgoing')
        ->and($phoneEvent->messages_count)->toBe(0)
        ->and($phoneEvent->data_count)->toBe(0);
});

it('persists incoming and outgoing calls between the same numbers as separate records', function () {
    $import = Import::query()->create([
        'original_filename' => 'directions.xlsx',
        'stored_path' => 'imports/phone-events/directions.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    (new PersistPhoneEventsFromRowsAction)->execute($import, [
        new PhoneEventData('9611762625', 'VOZ ENTRANTE', '9611762625', '9621298813', '2020-11-07', '12:10:00', 30, null, null, null, null),
        new PhoneEventData('9611762625', 'VOZ SALIENTE', '9611762625', '9621298813', '2020-11-07', '12:11:01', 45, null, null, null, null),
    ]);

    expect(PhoneEvent::query()->count())->toBe(2);

    $this->assertDatabaseHas('phone_events', [
        'import_id' => $import->id,
        'contact' => '9611762625',
        'number' => '9621298813',
        'calls_count' => 1,
        'call_direction' => 'incoming',
        'messages_count' => 0,
        'data_count' => 0,
    ]);

    $this->assertDatabaseHas('phone_events', [
        'import_id' => $import->id,
        'contact' => '9611762625',
        'number' => '9621298813',
        'calls_count' => 1,
        'call_direction' => 'outgoing',
        'messages_count' => 0,
        'data_count' => 0,
    ]);

    $incoming = PhoneEvent::query()->where('call_direction', 'incoming')->firstOrFail();
    $outgoing = PhoneEvent::query()->where('call_direction', 'outgoing')->firstOrFail();

    expect($incoming->first_seen_at->toDateTimeString())->toBe('2020-11-07 12:10:00')
        ->and($incoming->last_seen_at->toDateTimeString())->toBe('2020-11-07 12:10:00')
        ->and($outgoing->first_seen_at->toDateTimeString())->toBe('2020-11-07 12:11:01')
        ->and($outgoing->last_seen_at->toDateTimeString())->toBe('2020-11-07 12:11:01');
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
        new PhoneEventData('9611', 'VOZ SALIENTE', '9611', '9612', '2026-06-25', '10:00', 30, null, null, null, null),
    ]);

    $action->execute($import, [
        new PhoneEventData('9611', 'VOZ SALIENTE', '9611', '9612', '2026-06-26', '11:00', 30, null, null, null, null),
    ]);

    $phoneEvent = PhoneEvent::query()->firstOrFail();

    expect(PhoneEvent::query()->count())->toBe(1)
        ->and($phoneEvent->first_seen_at->toDateTimeString())->toBe('2026-06-26 11:00:00')
        ->and($phoneEvent->last_seen_at->toDateTimeString())->toBe('2026-06-26 11:00:00')
        ->and($phoneEvent->calls_count)->toBe(1)
        ->and($phoneEvent->call_direction)->toBe('outgoing')
        ->and($phoneEvent->messages_count)->toBe(0);
});

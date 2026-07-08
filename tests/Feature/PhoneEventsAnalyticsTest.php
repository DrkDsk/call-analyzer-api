<?php

use App\Constants\PhoneEventsConstants;
use App\Models\Import;
use App\Models\PhoneEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns call analytics grouped by date and call direction for all import events', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    $otherImport = Import::query()->create([
        'original_filename' => 'other.xlsx',
        'stored_path' => 'imports/phone-events/other.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    PhoneEvent::query()->insert([
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9611111111',
            'first_seen_at' => '2020-11-07 10:56:38',
            'last_seen_at' => '2020-11-07 10:56:38',
            'calls_count' => 5,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_INCOMING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9612222222',
            'first_seen_at' => '2020-11-07 11:56:38',
            'last_seen_at' => '2020-11-07 11:56:38',
            'calls_count' => 8,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9613333333',
            'first_seen_at' => '2020-11-07 12:56:38',
            'last_seen_at' => '2020-11-07 12:56:38',
            'calls_count' => 1,
            'call_direction' => null,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9614444444',
            'first_seen_at' => '2020-11-08 10:56:38',
            'last_seen_at' => '2020-11-08 10:56:38',
            'calls_count' => 3,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_INCOMING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9615555555',
            'first_seen_at' => '2020-11-08 11:56:38',
            'last_seen_at' => '2020-11-08 11:56:38',
            'calls_count' => 4,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9616666666',
            'first_seen_at' => '2020-11-08 12:56:38',
            'last_seen_at' => '2020-11-08 12:56:38',
            'calls_count' => 2,
            'call_direction' => 'unexpected',
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9618888888',
            'first_seen_at' => '2020-11-08 12:57:38',
            'last_seen_at' => '2020-11-08 12:57:38',
            'calls_count' => 1,
            'call_direction' => '',
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => 'internet.itelcel.com',
            'first_seen_at' => '2020-11-08 13:56:38',
            'last_seen_at' => '2020-11-08 13:56:38',
            'calls_count' => 0,
            'call_direction' => null,
            'messages_count' => 0,
            'data_count' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $otherImport->id,
            'contact' => '9611762625',
            'number' => '9617777777',
            'first_seen_at' => '2020-11-08 10:56:38',
            'last_seen_at' => '2020-11-08 10:56:38',
            'calls_count' => 99,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_INCOMING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $response = $this->getJson("/api/process/{$import->id}/events/analytics?type=call&group_by=date&metric=count");

    $response->assertOk()
        ->assertExactJson([
            'data' => [
                [
                    'date' => '2020-11-07',
                    'label' => '07 nov',
                    'incoming' => 5,
                    'outgoing' => 8,
                    'unknown' => 1,
                    'total' => 14,
                ],
                [
                    'date' => '2020-11-08',
                    'label' => '08 nov',
                    'incoming' => 3,
                    'outgoing' => 4,
                    'unknown' => 3,
                    'total' => 10,
                ],
            ],
            'meta' => [
                'type' => 'call',
                'group_by' => 'date',
                'metric' => 'count',
                'total' => 24,
                'incoming' => 8,
                'outgoing' => 12,
                'unknown' => 4,
            ],
        ]);
});

it('filters call analytics by incoming direction', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    PhoneEvent::query()->insert([
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9611111111',
            'first_seen_at' => '2020-11-07 10:56:38',
            'last_seen_at' => '2020-11-07 10:56:38',
            'calls_count' => 5,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_INCOMING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9612222222',
            'first_seen_at' => '2020-11-07 11:56:38',
            'last_seen_at' => '2020-11-07 11:56:38',
            'calls_count' => 8,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->getJson("/api/process/{$import->id}/events/analytics?type=call&group_by=date&metric=count&direction=incoming")
        ->assertOk()
        ->assertExactJson([
            'data' => [
                [
                    'date' => '2020-11-07',
                    'label' => '07 nov',
                    'incoming' => 5,
                    'outgoing' => 0,
                    'unknown' => 0,
                    'total' => 5,
                ],
            ],
            'meta' => [
                'type' => 'call',
                'group_by' => 'date',
                'metric' => 'count',
                'direction' => 'incoming',
                'total' => 5,
                'incoming' => 5,
                'outgoing' => 0,
                'unknown' => 0,
            ],
        ]);
});

it('filters call analytics by outgoing direction', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    PhoneEvent::query()->insert([
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9611111111',
            'first_seen_at' => '2020-11-07 10:56:38',
            'last_seen_at' => '2020-11-07 10:56:38',
            'calls_count' => 5,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_INCOMING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9612222222',
            'first_seen_at' => '2020-11-07 11:56:38',
            'last_seen_at' => '2020-11-07 11:56:38',
            'calls_count' => 8,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_OUTGOING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->getJson("/api/process/{$import->id}/events/analytics?type=call&group_by=date&metric=count&direction=outgoing")
        ->assertOk()
        ->assertJsonPath('data.0.outgoing', 8)
        ->assertJsonPath('data.0.total', 8)
        ->assertJsonPath('meta.direction', 'outgoing')
        ->assertJsonPath('meta.incoming', 0)
        ->assertJsonPath('meta.outgoing', 8)
        ->assertJsonPath('meta.unknown', 0)
        ->assertJsonPath('meta.total', 8);
});

it('filters call analytics by unknown direction', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    PhoneEvent::query()->insert([
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9611111111',
            'first_seen_at' => '2020-11-07 10:56:38',
            'last_seen_at' => '2020-11-07 10:56:38',
            'calls_count' => 5,
            'call_direction' => PhoneEventsConstants::CALL_DIRECTION_INCOMING,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9612222222',
            'first_seen_at' => '2020-11-07 11:56:38',
            'last_seen_at' => '2020-11-07 11:56:38',
            'calls_count' => 1,
            'call_direction' => null,
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9613333333',
            'first_seen_at' => '2020-11-07 12:56:38',
            'last_seen_at' => '2020-11-07 12:56:38',
            'calls_count' => 2,
            'call_direction' => '',
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'import_id' => $import->id,
            'contact' => '9611762625',
            'number' => '9614444444',
            'first_seen_at' => '2020-11-08 10:56:38',
            'last_seen_at' => '2020-11-08 10:56:38',
            'calls_count' => 3,
            'call_direction' => 'unexpected',
            'messages_count' => 0,
            'data_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->getJson("/api/process/{$import->id}/events/analytics?type=call&group_by=date&metric=count&direction=unknown")
        ->assertOk()
        ->assertExactJson([
            'data' => [
                [
                    'date' => '2020-11-07',
                    'label' => '07 nov',
                    'incoming' => 0,
                    'outgoing' => 0,
                    'unknown' => 3,
                    'total' => 3,
                ],
                [
                    'date' => '2020-11-08',
                    'label' => '08 nov',
                    'incoming' => 0,
                    'outgoing' => 0,
                    'unknown' => 3,
                    'total' => 3,
                ],
            ],
            'meta' => [
                'type' => 'call',
                'group_by' => 'date',
                'metric' => 'count',
                'direction' => 'unknown',
                'total' => 6,
                'incoming' => 0,
                'outgoing' => 0,
                'unknown' => 6,
            ],
        ]);
});

it('defaults the analytics metric to count', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    $this->getJson("/api/process/{$import->id}/events/analytics?type=call&group_by=date")
        ->assertOk()
        ->assertJsonPath('meta.metric', 'count')
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('data', []);
});

it('validates analytics query parameters', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    $this->getJson("/api/process/{$import->id}/events/analytics?type=sms&group_by=month&metric=sum&direction=invalid")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'group_by', 'metric', 'direction']);
});

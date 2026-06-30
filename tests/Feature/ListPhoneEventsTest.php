<?php

use App\Models\Import;
use App\Models\PhoneEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns paginated phone events for the requested import only', function () {
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

    PhoneEvent::query()->create([
        'import_id' => $import->id,
        'contact' => '9611762625',
        'number' => 'internet.itelcel.com',
        'first_seen_at' => '2020-11-07 10:56:38',
        'last_seen_at' => '2020-11-07 10:56:38',
        'calls_count' => 0,
        'messages_count' => 0,
        'data_count' => 2,
    ]);

    PhoneEvent::query()->create([
        'import_id' => $otherImport->id,
        'contact' => '9611762625',
        'number' => '9611111111',
        'first_seen_at' => '2020-11-08 10:56:38',
        'last_seen_at' => '2020-11-08 10:56:38',
        'calls_count' => 1,
        'messages_count' => 0,
        'data_count' => 0,
    ]);

    $response = $this->getJson("/api/process/{$import->id}/events");

    $response->assertOk()
        ->assertJsonPath('per_page', 15)
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.import_id', $import->id)
        ->assertJsonPath('data.0.number', 'internet.itelcel.com');
});

it('returns an empty paginator when an import has no phone events', function () {
    $import = Import::query()->create([
        'original_filename' => 'events.xlsx',
        'stored_path' => 'imports/phone-events/events.xlsx',
        'file_size' => 100,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'status' => 'completed',
    ]);

    $this->getJson("/api/process/{$import->id}/events")
        ->assertOk()
        ->assertJsonPath('per_page', 15)
        ->assertJsonPath('total', 0)
        ->assertJsonPath('data', []);
});

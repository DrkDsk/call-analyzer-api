<?php

use App\Data\PhoneEventData;
use App\Exceptions\HeadersRequiredException;
use App\Support\PhoneEventsHeaderDetector;
use App\Support\PhoneEventsStatsAccumulator;

it('detects headers when they are not in the first row', function () {
    $detector = new PhoneEventsHeaderDetector;

    [$rowIndex, $headersMap] = $detector->detect(collect([
        collect(['metadata']),
        collect(['another', 'empty', 'row']),
        collect([
            ' Teléfono ',
            'Tipo',
            'Número A',
            'Número B',
            'Fecha',
            'Hora',
            'Duracion',
            'IMEI',
            'Ubicación Geográfica (LATITUD / LONGITUD)',
            'Azimuth',
        ]),
    ]));

    expect($rowIndex)->toBe(2)
        ->and($headersMap)->toMatchArray([
            'phone' => 0,
            'type' => 1,
            'number_a' => 2,
            'number_b' => 3,
            'date' => 4,
            'time' => 5,
            'duration' => 6,
            'imei' => 7,
            'location' => 8,
            'azimuth' => 9,
        ]);
});

it('throws a controlled exception when required headers are missing', function () {
    (new PhoneEventsHeaderDetector)->detect(collect([
        collect(['Telefono', 'Tipo']),
    ]));
})->throws(HeadersRequiredException::class, 'No se encontraron los encabezados requeridos en el archivo.');

it('normalizes phone event rows and extracts coordinates', function () {
    $event = PhoneEventData::fromExcelRow([
        ' 9611234567 ',
        ' voz ',
        '9611111111',
        '9612222222',
        '2026-06-25',
        '10:32:15',
        '50°',
        '123456789012345',
        '16°45\'22"N / 93°8\'35"W',
        '50°',
    ], [
        'phone' => 0,
        'type' => 1,
        'number_a' => 2,
        'number_b' => 3,
        'date' => 4,
        'time' => 5,
        'duration' => 6,
        'imei' => 7,
        'location' => 8,
        'azimuth' => 9,
    ]);

    expect($event)->not->toBeNull()
        ->and($event->phone)->toBe('9611234567')
        ->and($event->type)->toBe('VOZ')
        ->and($event->duration)->toBe(50)
        ->and($event->time)->toBe('10:00')
        ->and($event->latitude)->toBe(16.7561111)
        ->and($event->longitude)->toBe(-93.1430556)
        ->and($event->azimuth)->toBe(50.0);
});

it('ignores empty rows', function () {
    $event = PhoneEventData::fromExcelRow(['', null, '   '], [
        'phone' => 0,
        'type' => 1,
        'number_b' => 2,
    ]);

    expect($event)->toBeNull();
});

it('calculates phone event statistics', function () {
    $accumulator = new PhoneEventsStatsAccumulator;

    foreach ([
        new PhoneEventData('9611', 'VOZ', '9611', '9612', '2026-06-25', '10:00', 30, null, null, null, null),
        new PhoneEventData('9611', 'CALL', '9611', '9612', '2026-06-25', '10:00', 60, null, null, null, null),
        new PhoneEventData('9611', 'DATOS', '9611', 'INTERNET', '2026-06-26', '11:00', 0, null, null, null, null),
        new PhoneEventData('9611', 'INTERNET', '9611', '', '2026-06-26', '11:00', 10, null, null, null, null),
    ] as $event) {
        $accumulator->add($event);
    }

    expect($accumulator->result())->toMatchArray([
        'total_events' => 4,
        'total_calls' => 2,
        'total_data' => 2,
        'total_duration' => 100,
        'average_duration' => 25.0,
        'unique_contacts' => 1,
        'top_contact' => '9612',
        'peak_hour' => '10:00',
        'active_days' => 2,
    ]);
});

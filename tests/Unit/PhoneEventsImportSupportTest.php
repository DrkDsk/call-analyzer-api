<?php

use App\Data\PhoneEventData;
use App\Exceptions\HeadersRequiredException;
use App\Imports\PhoneEventsPreviewImport;
use App\Support\PhoneEventsHeaderDetector;
use App\Support\PhoneEventsStatsAccumulator;

function phoneEventsHeaderRow(): array
{
    return [
        'Teléfono',
        'Tipo',
        'Número A',
        'Número B',
        'Fecha',
        'Hora',
        'Duracion',
        'IMEI',
        'Ubicación Geográfica (LATITUD / LONGITUD)',
        'Azimuth',
    ];
}

function phoneEventsDataRow(): array
{
    return [
        '9611762625',
        'DATOS',
        '9611762625',
        'internet.itelcel.com',
        '07/11/20',
        '10:56:38',
        '3191',
        '3.52436E+14',
        '16°45\'22"N / 93°8\'35"W',
        '50°',
    ];
}

it('accepts headers with accents', function () {
    [, $headersMap] = (new PhoneEventsHeaderDetector)->detect(collect([
        collect([
            'Teléfono',
            'Tipo',
            'Número A',
            'Número B',
            'Fecha',
            'Hora',
            'Duración',
            'IMEI',
            'Ubicación Geográfica',
            'Azimuth',
        ]),
        collect(phoneEventsDataRow()),
    ]));

    expect($headersMap)->toMatchArray([
        'phone' => 0,
        'number_a' => 2,
        'number_b' => 3,
        'duration' => 6,
        'location' => 8,
        'azimuth' => 9,
    ]);
});

it('accepts headers without accents', function () {
    [, $headersMap] = (new PhoneEventsHeaderDetector)->detect(collect([
        collect([
            'Telefono',
            'Tipo',
            'Numero A',
            'Numero B',
            'Fecha',
            'Hora',
            'Duracion',
            'IMEI',
            'Ubicacion Geografica',
            'Azimuth',
        ]),
        collect(phoneEventsDataRow()),
    ]));

    expect($headersMap)->toMatchArray([
        'phone' => 0,
        'number_a' => 2,
        'number_b' => 3,
        'duration' => 6,
        'location' => 8,
    ]);
});

it('accepts duracion as duration header', function () {
    [, $headersMap] = (new PhoneEventsHeaderDetector)->detect(collect([
        collect(phoneEventsHeaderRow()),
        collect(phoneEventsDataRow()),
    ]));

    expect($headersMap['duration'])->toBe(6);
});

it('accepts durac seg as duration header', function () {
    $header = phoneEventsHeaderRow();
    $header[6] = 'Durac. Seg.';

    [, $headersMap] = (new PhoneEventsHeaderDetector)->detect(collect([
        collect($header),
        collect(phoneEventsDataRow()),
    ]));

    expect($headersMap['duration'])->toBe(6);
});

it('accepts mixed known header aliases', function () {
    [, $headersMap] = (new PhoneEventsHeaderDetector)->detect(collect([
        collect([
            'Telefono',
            'Tipo',
            'Numero A',
            'Numero B',
            'Fecha',
            'Hora',
            'Durac. Seg.',
            'IMEI',
            'Ubicacion Geografica (LATITUD / LONGITUD)',
            'Azimut',
        ]),
        collect(phoneEventsDataRow()),
    ]));

    expect($headersMap)->toMatchArray([
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

it('detects headers when they are not in the first row', function () {
    $detector = new PhoneEventsHeaderDetector;

    [$rowIndex, $headersMap] = $detector->detect(collect([
        collect(['metadata']),
        collect(['another', 'empty', 'row']),
        collect(phoneEventsHeaderRow()),
        collect(phoneEventsDataRow()),
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
})->throws(HeadersRequiredException::class, 'No se encontró una sección válida de eventos telefónicos en el archivo.');

it('ignores a header followed by no details text', function () {
    (new PhoneEventsHeaderDetector)->detect(collect([
        collect(phoneEventsHeaderRow()),
        collect(['Sin detalle para el periodo seleccionado']),
    ]));
})->throws(HeadersRequiredException::class, 'No se encontró una sección válida de eventos telefónicos en el archivo.');

it('detects a valid header followed by real event rows', function () {
    [$rowIndex, $headersMap] = (new PhoneEventsHeaderDetector)->detect(collect([
        collect(phoneEventsHeaderRow()),
        collect(phoneEventsDataRow()),
        collect(phoneEventsDataRow()),
    ]));

    expect($rowIndex)->toBe(0)
        ->and($headersMap['phone'])->toBe(0)
        ->and($headersMap['time'])->toBe(5);
});

it('uses the first header that has valid event data after invalid blocks', function () {
    [$rowIndex, $headersMap] = (new PhoneEventsHeaderDetector)->detect(collect([
        collect(phoneEventsHeaderRow()),
        collect(['Sin detalle para el periodo seleccionado']),
        collect(['']),
        collect(['separador']),
        collect(phoneEventsHeaderRow()),
        collect(phoneEventsDataRow()),
    ]));

    expect($rowIndex)->toBe(4)
        ->and($headersMap['number_b'])->toBe(3);
});

it('throws when no valid phone events section exists', function () {
    (new PhoneEventsHeaderDetector)->detect(collect([
        collect(['metadata']),
        collect(phoneEventsHeaderRow()),
        collect(['']),
        collect(['---']),
    ]));
})->throws(HeadersRequiredException::class, 'No se encontró una sección válida de eventos telefónicos en el archivo.');

it('detects a valid header after empty rows', function () {
    [$rowIndex] = (new PhoneEventsHeaderDetector)->detect(collect([
        collect(['']),
        collect(['   ']),
        collect([null]),
        collect(phoneEventsHeaderRow()),
        collect(phoneEventsDataRow()),
    ]));

    expect($rowIndex)->toBe(3);
});

it('extracts rows after a valid header and keeps trailing empty rows for the existing flow', function () {
    $rows = (new PhoneEventsHeaderDetector)->extractRowsFromValidHeader(collect([
        collect(['']),
        collect(phoneEventsHeaderRow()),
        collect(phoneEventsDataRow()),
        collect(['   ']),
    ]));

    expect($rows)->toHaveCount(2)
        ->and($rows->first()->all())->toBe(phoneEventsDataRow())
        ->and($rows->last()->all())->toBe(['   ']);
});

it('detects a valid section when the header and first data row arrive in different chunks', function () {
    $accumulator = new PhoneEventsStatsAccumulator;
    $import = new PhoneEventsPreviewImport($accumulator);

    $import->collection(collect([
        collect(phoneEventsHeaderRow()),
    ]));

    expect($import->hasDetectedHeaders())->toBeFalse();

    $import->collection(collect([
        collect(phoneEventsDataRow()),
    ]));

    expect($import->hasDetectedHeaders())->toBeTrue()
        ->and($import->events())->toHaveCount(1)
        ->and($accumulator->result()['total_events'])->toBe(1);
});

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

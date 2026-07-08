<?php

namespace App\Constants;

final class PhoneEventsConstants
{
    public const string CALL_DIRECTION_INCOMING = 'incoming';

    public const string CALL_DIRECTION_OUTGOING = 'outgoing';

    public const string CALL_DIRECTION_UNKNOWN = 'unknown';

    public const array IS_CALL_ARRAY = ['LLAMADA', 'LLAMADAS', 'VOZ', 'CALL', 'VOZ ENTRANTE', 'VOZ SALIENTE', 'VOZ TRANSITO'];

    public const array IS_INCOMING_CALL_ARRAY = ['VOZ ENTRANTE'];

    public const array IS_OUTGOING_CALL_ARRAY = ['VOZ SALIENTE'];

    public const array IS_MESSAGE_ARRAY = ['SMS', 'MENSAJE', 'MENSAJES', 'MENSAJES 2 VIAS'];

    public const array IS_DATA_ARRAY = ['DATOS', 'DATO', 'DATA', 'INTERNET'];
}

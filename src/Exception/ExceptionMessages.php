<?php

namespace DarrynTen\XeroOauth\Exception;

/**
 * Exception message strings for the Exception objects that get thrown.
 */
class ExceptionMessages
{
    // Config codes 110xx
    public static $configErrorMessages = [
        // Methods
        11000 => 'Undefined config exception',
        11001 => 'Missing key',
    ];

    // Validation codes 111xx
    public static $validationMessages = [
        11100 => 'Unknown validation error',
    ];

    // Maps to standard HTTP error codes
    public static $strings = [
        400 => '400',
        401 => '401',
        402 => '402',
        404 => '404',
        405 => '405',
        409 => '409',
        415 => '415',
        429 => '429',
        500 => '500',
        503 => '503',
    ];
}

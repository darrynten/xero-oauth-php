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
        11002 => 'Missing application type',
        11003 => 'Unknown application type',
        11004 => 'Unknown signature method',
        11005 => 'Private key not found',
        11006 => 'Private key invalid',
    ];

    // Validation codes 111xx
    public static $validationMessages = [
        11100 => 'Unknown validation error',
    ];

    // Auth codes 112xx
    public static $authMessages = [
        11200 => 'User must authorize oauth_token before it can be used',
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

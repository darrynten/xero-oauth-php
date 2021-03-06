<?php
/**
 * XeroOauth API Exception
 *
 * @category Exception
 * @package  XeroOauth
 * @author   Darryn Ten <darrynten@github.com>
 * @license  MIT <https://github.com/darrynten/xero-oauth-php/blob/master/LICENSE>
 * @link     https://github.com/darrynten/xero-oauth-php
 */

namespace DarrynTen\XeroOauth\Exception;

use Exception;

/**
 * Validation exception for XeroOauth
 *
 * @package XeroOauth
 */
class ValidationException extends Exception
{
    const UNDEFINED_VALIDATION_EXCEPTION = 11100;
    // number constants up from this number - 11101 is next

    /**
     * Custom validation exception handler
     *
     * @var string $endpoint The name of the model
     * @var integer $code The error code (as per above) [11100 is Generic code]
     * @var string $extra Any additional information to be included
     */
    public function __construct($code = 11100, $extra = '')
    {
        $message = sprintf(
            'Validation error %s %s',
            $extra,
            ExceptionMessages::$validationMessages[$code]
        );

        parent::__construct($message, $code);
    }
}

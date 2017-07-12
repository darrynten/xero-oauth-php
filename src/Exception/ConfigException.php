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
use DarrynTen\XeroOauth\Exception\ExceptionMessages;

/**
 * Validation exception for XeroOauth
 *
 * @package XeroOauth
 */
class ValidationException extends Exception
{
    const UNDEFINED_CONFIG_EXCEPTION = 11000;
    const MISSING_KEY = 11001;
    // number constants up from this number - 11002 is next

    /**
     * Custom validation exception handler
     *
     * @var string $endpoint The name of the model
     * @var integer $code The error code (as per above) [11000 is Generic code]
     * @var string $extra Any additional information to be included
     */
    public function __construct($code = 11000, $extra = '')
    {
        $message = sprintf(
            'Config error %s %s',
            $extra,
            ExceptionMessages::$validationMessages[$code]
        );

        parent::__construct($message, $code);
    }
}

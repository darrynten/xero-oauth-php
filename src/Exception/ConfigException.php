<?php
/**
 * XeroOauth Config Exception
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
 * Config exception for XeroOauth
 *
 * @package XeroOauth
 */
class ConfigException extends Exception
{
    const UNDEFINED_CONFIG_EXCEPTION = 11000;
    const MISSING_KEY = 11001;
    const MISSING_APPLICATION_TYPE = 11002;
    const UNKNOWN_APPLICATION_TYPE = 11003;
    // number constants up from this number - 11004 is next

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
            ExceptionMessages::$configErrorMessages[$code]
        );

        parent::__construct($message, $code);
    }
}

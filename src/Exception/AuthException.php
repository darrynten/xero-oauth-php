<?php
/**
 * XeroOauth Auth Exception
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
 * Auth exception for XeroOauth
 *
 * @package XeroOauth
 */
class AuthException extends Exception
{
    const OAUTH_TOKEN_AUTHORIZATION_EXPECTED = 11200;

    /**
     * Custom validation exception handler
     *
     * @var string $endpoint The name of the model
     * @var integer $code The error code (as per above)
     * @var string $extra Any additional information to be included
     */
    public function __construct($code = 11200, $extra = '')
    {
        $message = sprintf(
            'Auth error %s %s',
            $extra,
            ExceptionMessages::$authMessages[$code]
        );

        parent::__construct($message, $code);
    }
}

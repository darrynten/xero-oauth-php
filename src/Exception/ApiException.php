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
 * API exception for XeroOauth
 *
 * @package XeroOauth
 */
class ApiException extends Exception
{
    /**
     * @inheritdoc
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        // Construct message from JSON if required.
        if (preg_match('/^[\[\{]\"/', $message)) {
            $messageObject = json_decode($message);
            $message = sprintf(
                '%s: %s - %s',
                $messageObject->status,
                $messageObject->title,
                $messageObject->detail
            );
            if (!empty($messageObject->errors)) {
                $message .= ' - errors: ' . json_encode($messageObject->errors);
            }
        }

        parent::__construct($message, $code, $previous);
    }
}

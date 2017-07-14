<?php
/**
 * XeroOauth Library
 *
 * @category Library
 * @package  XeroOauth
 * @author   Darryn Ten <darrynten@github.com>
 * @license  MIT <https://github.com/darrynten/xero-oauth-php/blob/master/LICENSE>
 * @link     https://github.com/darrynten/xero-oauth-php
 */

namespace DarrynTen\XeroOauth\Config;

/**
 * Public Application Configuration
 *
 * Public applications use the standard 3 legged OAuth process where a user
 * can authorise your application to have access to their Xero organisation.
 * Public applications can either be web based or desktop/mobile installed.
 * Access tokens for public applications expire after 30 minutes.
 * https://developer.xero.com/documentation/auth-and-limits/public-applications
 */
class PublicApplicationConfig extends BaseConfig
{
    public $applicationType = 'public';

    // This is *not* the oauth callback url
    public $callbackDomain;

    public $signWith = 'HMAC-SHA1';
}

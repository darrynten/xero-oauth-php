<?php

namespace DarrynTen\XeroOauth\Config;

use DarrynTen\XeroOauth\Exception\ConfigException;

/**
 * XeroOauth Config
 *
 * TODO Make public attributes protected and put them under getters/setters/__magic
 *
 * @category Configuration
 * @package  XeroOauthPHP
 * @author   Darryn Ten <darrynten@github.com>
 * @license  MIT <https://github.com/darrynten/xero-oauth-php/LICENSE>
 * @link     https://github.com/darrynten/xero-oauth-php
 */
abstract class BaseConfig
{
    /**
     * XeroOauth key
     *
     * "Consumer Key"
     *
     * You will need to generate a public/private key-pair, of which the
     * public part will be uploaded to XeroOauth during application registration.
     *
     * @var string $key
     */
    private $key = null;

    /**
     * Shared secret
     *
     * "Consumer Secret"
     *
     * The consumer secret is not used for private/partner apps.
     *
     * @var string $secret
     */
    private $secret = null;

    /**
     * The api versions
     *
     * API version to connect to, depending on service
     *
     * @var array $versions
     */
    public $versions = [
        'accounting' => '2.0',
        'payroll' => '1.0',
        'files' => '1.0',
    ];

    /**
     * The project ID
     *
     * @var string $projectId
     */
    public $endpoint = '//api.xero.com/api.xro';

    /**
     * Whether or not to use caching.
     *
     * The default is true as this is a good idea.
     *
     * @var boolean $cache
     */
    public $cache = true;

    /**
     * Rate Limit
     *
     * Minute Limit: 60 calls in a rolling 60 second window
     * Daily Limit: 5000 calls in a rolling 24 hour window
     *
     * NB this is only checking the day one, we need to handle the
     * minute one too TODO
     *
     * https://developer.xero.com/documentation/auth-and-limits/xero-api-limits
     *
     * @var int $rateLimit
     */
    public $rateLimit = 5000;

    /**
     * Rate Limit Period (in seconds)
     *
     * 1 day = 60 * 60 * 24 = 86400
     *
     * @var int $rateLimitPeriod
     */
    public $rateLimitPeriod = 86400;

    /**
     * The number of times to retry failed calls
     *
     * @var integer $retries
     */
    public $retries = 3;

    /**
     * The request size limit for POST in Accounting and Payroll
     * in kilobytes
     *
     * Note: When posting form-encoded xml to the API, the encoded data
     * will be approx 50% larger than the original xml message.
     *
     * You can batch elements in bundles up to 50
     *
     * 3.5MB
     *
     * @var integer
     */
    public $maxPostSize = 35000;

    /**
     * The request size limit for POST to Files API
     * in kilobytes
     *
     * 10MB
     *
     * @var integer $maxFileSize
     */
    public $maxFileSize = 100000;

    /**
     * Application type
     *
     * https://developer.xero.com/documentation/getting-started/api-application-types
     *
     * Xero offers 3 types of application
     *
     * Private, Public, and Partner
     *
     * Private applications use 2 legged OAuth and bypass the user authorization
     * workflow in the standard OAuth process. Private applications are linked
     * to a single Xero organisation which is chosen when you register your
     * application. Access tokens for private applications don’t expire unless
     * the application is deleted or disconnected from within the Xero
     * organisation.
     * https://developer.xero.com/documentation/auth-and-limits/private-applications
     *
     * Public applications use the standard 3 legged OAuth process where a user
     * can authorise your application to have access to their Xero organisation.
     * Public applications can either be web based or desktop/mobile installed.
     * Access tokens for public applications expire after 30 minutes.
     * https://developer.xero.com/documentation/auth-and-limits/public-applications
     *
     * Partner applications are public applications that have been upgraded to
     * support long term access tokens.
     *
     * Partner applications use the same 3-legged authorization process as
     * public applications, but the 30-minute access tokens can be renewed.
     * Access tokens can be renewed without further user authorization. This
     * process of token renewal can occur indefinitely, while the partner
     * application is in active use.
     *
     * Partner applications also use a different signature method to public
     * apps. You need to sign your requests using the RSA-SHA1 method. More
     * details:
     * https://developer.xero.com/documentation/auth-and-limits/partner-applications
     *
     * They all have different authentication rules.
     *
     * See their individual config classes for more info
     *
     * TODO perhaps call this authenticationType?
     *
     * @var string $applicationType
     */
    public $applicationType;

    // Gets sent through as user-agent apparently
    // https://github.com/XeroAPI/XeroOAuth-PHP/blob/master/_config.php#L24
    public $applicationName;

    /**
     * Callback URL for auth
     *
     * https://developer.xero.com/documentation/auth-and-limits/oauth-callback-domains-explained
     *
     * @var string $callbackUrl
     */
    public $callbackUrl;

    /**
     * Signature method for requests
     *
     * @var string $signWith
     */
    public $signWith;

    /**
     * Any additional options required for RequestHandler
     *
     * @var array $options
     */
    public $options;

    /**
     * Valid options keys
     *
     * @var array $optionsKeys
     */
    public $optionsKeys;

    /**
     * Construct the config object
     *
     * @param array $config An array of configuration options
     */
    public function __construct($config)
    {
        $this->optionsKeys = [
            'token', 'token_secret',
            'token_expires_in', 'verifier',
            'private_key', 'oauth_endpoint'
        ];

        // Throw exceptions on essentials
        $this->checkAndSetEssentials($config);

        // optionals
        $this->checkAndSetOverrides($config);
    }

    /**
     * Check and set essential configuration elements.
     *
     * Required:
     *
     *   - API Key
     *   - etc
     *
     * @param array $config An array of configuration options
     */
    private function checkAndSetEssentials($config)
    {
        // Validate and throw
        if (!isset($config['key']) || empty($config['key'])) {
            throw new ConfigException(ConfigException::MISSING_KEY);
        }

        // etc etc

        // Set
        $this->key = (string)$config['key'];

        // etc etc
    }

    /**
     * Check and set any overriding elements.
     *
     * Optionals:
     *
     *   - Endpoint
     *   - etc etc
     *
     * @param array $config An array of configuration options
     */
    private function checkAndSetOverrides($config)
    {
        if (isset($config['endpoint']) && !empty($config['endpoint'])) {
            $this->endpoint = (string)$config['endpoint'];
        }

        $this->callbackUrl = '';

        if (isset($config['callback_url']) && !empty($config['callback_url'])) {
            $this->callbackUrl = (string)$config['callback_url'];
        }

        $this->options = [];
        foreach ($this->optionsKeys as $key) {
            if (isset($config[$key])) {
                $this->options[$key] = $config[$key];
            }
        }

        // etc etc
    }

    /**
     * Retrieves the expected config for the API
     *
     * @return array
     */
    public function getRequestHandlerConfig()
    {
        $config = [
            'key' => $this->key,
            'endpoint' => $this->endpoint,
            'secret' => $this->secret, // we need it to sign requests
            'callback_url' => $this->callbackUrl,
            'sign_with' => $this->signWith,
        ];

        foreach ($this->optionsKeys as $key) {
            $config[$key] = isset($this->options[$key]) ? $this->options[$key] : '';
        }

        if ($this->applicationType === ConfigFactory::APPLICATION_TYPE_PRIVATE) {
            $config['token'] = $this->key;
            $config['token_secret'] = $this->secret;
            $config['token_verified'] = true;
        }

        return $config;
    }
}

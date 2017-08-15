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

namespace DarrynTen\XeroOauth\Request;

use DarrynTen\XeroOauth\Exception\ApiException;
use DarrynTen\XeroOauth\Exception\ExceptionMessages;
use DarrynTen\XeroOauth\Exception\ConfigException;
use DarrynTen\XeroOauth\Exception\AuthException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * RequestHandler Class
 *
 * @category Library
 * @package  XeroOauth
 * @author   Darryn Ten <darrynten@github.com>
 * @license  MIT <https://github.com/darrynten/xero-oauth-php/blob/master/LICENSE>
 * @link     https://github.com/darrynten/xero-oauth-php
 */
class RequestHandler
{
    /**
     * Current OAuth version
     */
    const OAUTH_VERSION = '1.0';

    /**
     * If we need a temporary Token
     */
    const REQUEST_TOKEN = 'RequestToken';

    /**
     * If we need a real token
     */
    const ACCESS_TOKEN = 'AccessToken';

    /**
     * GuzzleHttp Client
     *
     * @var Client $client
     */
    private $client;

    /**
     * The key that gets passed in from config
     *
     * @var string $key
     */
    private $key;

    /**
     * The consumer secret for the key
     *
     * @var string $secret
     */
    private $secret;

    /**
     * The endpoint that gets passed in from config
     *
     * @var string $endpoint
     */
    private $endpoint;

    /**
     * The oauth endpoint that gets passed in from config
     *
     * @var string $endpoint
     */
    private $oauthEndpoint;

    /**
     * Authorization token
     *
     * @var string $token
     */
    private $token;

    /**
     * Authorization token secret
     *
     * @var string $tokenSecret
     */
    private $tokenSecret;

    /**
     * Authorization token expires time
     * If NULL then Token least forever
     *
     * @var \DateTime|null $tokenExpireTime
     */
    private $tokenExpireTime;

    /**
     * Verifier for Auth token
     *
     * @var string $tokenVerifier
     */
    private $tokenVerifier;

    /**
     * The callback for an Authorization process
     *
     * @var string $callbackUrl
     */
    private $callbackUrl;

    /**
     * Request signature method
     *
     * @var string $signatureMethod
     */
    private $signatureMethod;

    /**
     * Private key path
     *
     * @var string $privateKey
     */
    private $privateKey;

    /**
     * Valid HTTP Verbs for this API
     *
     * @var array $verbs
     */
    private $verbs = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
    ];

    /**
     * Request handler constructor
     *
     * @param array $config The connection config
     */
    public function __construct(array $config)
    {
        $this->key = $config['key'];
        $this->endpoint = $config['endpoint'];
        if (isset($config['oauth_endpoint'])) {
            $this->oauthEndpoint = $config['oauth_endpoint'];
        }
        $this->secret = $config['secret'];
        $this->callbackUrl = $config['callback_url'];

        $this->token = $config['token'];
        $this->tokenSecret = $config['token_secret'];
        $this->tokenExpireTime = $config['token_expires_in'];
        $this->tokenVerifier = $config['verifier'];
        $this->signatureMethod = $config['sign_with'];

        $this->privateKey = null;
        if (isset($config['private_key'])) {
            $this->privateKey = $config['private_key'];
        }

        $this->tokenVerified = false;
        if (isset($config['token_verified'])) {
            $this->tokenVerified = true;
        }

        $this->client = new Client();
    }

    /**
     * Makes a request using Guzzle
     *
     * @param string $method The HTTP request verb (GET/POST/etc)
     * @param string $uri Service method uri
     * @param array $options Request options
     * @param array $parameters Request parameters
     * @return array
     * @throws ApiException
     * @see RequestHandler::request()
     *
     */
    public function handleRequest(string $method, string $uri, array $options, array $parameters = [], $contentMethod = 'json_decode')
    {
        if (!in_array($method, $this->verbs)) {
            throw new ApiException('405 Bad HTTP Verb', 405);
        }

        if (!empty($parameters)) {
            if ($method === 'GET') {
                // Send as get params
                foreach ($parameters as $key => $value) {
                    $options['query'][$key] = $value;
                }
            } elseif ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
                // Otherwise send JSON in the body
                $options['json'] = (object)$parameters;
            }
        }

        // Let's go
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (RequestException $exception) {
            $this->handleException($exception);
        }

        $contents = $response->getBody()->getContents();

        if ($contentMethod) {
            return $contentMethod($contents);
        }
        return $contents;
    }

    /**
     * Handles all API exceptions, and adds the official exception terms
     * to the message.
     *
     * @param RequestException the original exception
     *
     * @throws ApiException
     */
    private function handleException($exception)
    {
        /** @var \Exception $exception */
        $code = $exception->getCode();
        $message = $exception->getMessage();

        $title = sprintf(
            '%s: %s - %s',
            $code,
            ExceptionMessages::$strings[$code],
            $message
        );

        throw new ApiException($title, $exception->getCode(), $exception);
    }

    /**
     * Get token for Xero API requests
     *
     * @throws ApiException
     */
    private function getAuthToken()
    {
        $parts = [
            'oauth_consumer_key' => $this->key,
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => time(),
            'oauth_nonce' => uniqid('xero', true),
            'oauth_callback' => $this->callbackUrl,
            'oauth_version' => static::OAUTH_VERSION,
        ];

        if (!$this->token) {
            $this->getRequestToken($parts);
        }

        $parts['oauth_token'] = $this->token;

        if ($this->tokenVerifier) {
            $parts['oauth_verifier'] = $this->tokenVerifier;
        }

        if (
            ($this->tokenExpireTime && $this->tokenExpireTime < new \DateTime())
            ||
            !($this->tokenVerified)
        ) {
            $this->getRequestToken($parts);
            $parts['oauth_token'] = $this->token;
        }

        return $parts;
    }

    /**
     * Returns authorization data
     */
    public function getAuthData()
    {
        return [
            'oauth_token' => $this->token,
            'oauth_token_secret' => $this->tokenSecret,
            'oauth_expires_in' => $this->tokenExpireTime,
            'oauth_verifier' => $this->tokenVerifier,
            'token_verified' => $this->tokenVerified,
        ];
    }

    /**
     * Make request to Xero API for the new token
     *
     * @param array $parts
     */
    private function getRequestToken(array $parts = [])
    {
        $mode = $this->token ? static::ACCESS_TOKEN : static::REQUEST_TOKEN;

        $serviceUrl = sprintf(
            '%s/%s',
            $this->oauthEndpoint,
            $mode
        );

        $oauthSignature = $this->generateOauthSignature('GET', $serviceUrl, $parts);
        $parts['oauth_signature'] = $oauthSignature;

        $options = [
            'headers' => [
                'Authorization' => 'OAuth ' . join(',', array_map(function ($key, $value) {
                    return sprintf('%s=%s', $key, $value);
                }, array_keys($parts), $parts)),
                'Accept' => 'application/json'
            ]
        ];

        $parameters = [];
        $tokenData = $this->handleRequest(
            'GET',
            $serviceUrl,
            $options,
            $parameters,
            null
        );

        $decodedData = [];
        parse_str($tokenData, $decodedData);

        $this->token = $decodedData['oauth_token'];
        $this->tokenSecret = $decodedData['oauth_token_secret'];

        if (isset($decodedData['oauth_expires_in'])) {
            $this->tokenExpireTime = new \DateTime();
            $this->tokenExpireTime->modify(
                sprintf('%s seconds', $decodedData['oauth_expires_in'])
            );
        }

        if ($mode === static::REQUEST_TOKEN) {
            throw new AuthException(AuthException::OAUTH_TOKEN_AUTHORIZATION_EXPECTED, $this->token);
        }

        if ($mode === static::ACCESS_TOKEN) {
            $this->tokenVerified = true;
        }
    }

    /**
     * Makes a request to Xero with a token
     *
     * @param $httpMethod
     * @param $service
     * @param array $parameters
     * @return array
     */
    public function request($httpMethod, $service, $parameters = [ ])
    {
        $authToken = $this->getAuthToken();

        $signParameters = $authToken;

        if ($httpMethod === 'GET') {
            foreach ($parameters as $key => $value) {
                $signParameters[$key] = $value;
            }
        }

        $fullUrl = sprintf(
            '%s/%s',
            $this->endpoint,
            $service
        );

        $oauthSignature = $this->generateOauthSignature($httpMethod, $fullUrl, $signParameters);
        $authToken['oauth_signature'] = $oauthSignature;

        $options = [
            'headers' => [
                'Authorization' => 'OAuth ' . join(',', array_map(function ($key, $value) {
                    return sprintf('%s=%s', $key, $value);
                }, array_keys($authToken), $authToken)),
                'Accept' => 'application/json'
            ]
        ];

        return $this->handleRequest(
            $httpMethod,
            $fullUrl,
            $options,
            $parameters
        );
    }

    /**
     * Generates oauth signature
     */
    public function generateOauthSignature(string $method, string $path, array $parameters = [])
    {
        switch ($this->signatureMethod) {
            case 'RSA-SHA1':
                return $this->generateRSASHA1Signature($method, $path, $parameters);
                break;
            case 'HMAC-SHA1':
                return $this->generateHMACSHA1Signature($method, $path, $parameters);
                break;
            default:
                throw new ConfigException(ConfigException::UNKNOWN_SIGNATURE_METHOD, $this->signatureMethod);
        }
    }

    /**
     * Generates oauth signature for RSA-SHA1 method
     * @param string $method
     * @param string $path
     * @param array $parameters
     */
    protected function generateRSASHA1Signature(string $method, string $path, array $parameters)
    {
        if (!file_exists($this->privateKey)) {
            throw new ConfigException(ConfigException::PRIVATE_KEY_NOT_FOUND, $this->privateKey);
        }

        $file = fopen($this->privateKey, 'r');
        $contents = fread($file, 8192);
        fclose($file);

        $privateKey = openssl_pkey_get_private($contents);
        if ($privateKey === false) {
            throw new ConfigException(ConfigException::PRIVATE_KEY_INVALID, $this->privateKey);
        }

        $sbs = sprintf(
            '%s&%s&%s',
            $method,
            $this->oauthEscape($path),
            $this->oauthEscape($this->sortParameters($parameters))
        );

        openssl_sign($sbs, $signature, $privateKey);

        openssl_free_key($privateKey);

        return base64_encode($signature);
    }

    /**
     * Generates oauth signature for HMAC-SHA1 method
     * @param string $method
     * @param string $path
     * @param array $parameters
     */
    protected function generateHMACSHA1Signature(string $method, string $path, array $parameters)
    {
        $secretKey = '';
        $secretKey = $this->secret;
        $secretKey .= '&';
        if (!empty($this->tokenSecret)) {
            $secretKey .= $this->tokenSecret;
        }

        $sbs = sprintf(
            '%s&%s&%s',
            $method,
            $this->oauthEscape($path),
            $this->oauthEscape($this->sortParameters($parameters))
        );

        return base64_encode(
            hash_hmac('sha1', $sbs, $secretKey, true)
        );
    }

    /**
     * Sorts query parameters (https://oauth.net/core/1.0a/#anchor13)
     * The request parameters are collected, sorted and concatenated into a normalized string:
     * Parameters in the OAuth HTTP Authorization header excluding the realm parameter.
     * Parameters in the HTTP POST request body (with a content-type of application/x-www-form-urlencoded).
     * HTTP GET parameters added to the URLs in the query part
     * The oauth_signature parameter MUST be excluded.
     * Parameters are sorted by name, using lexicographical byte value ordering
     * If two or more parameters share the same name, they are sorted by their value.
     * For each parameter, the name is separated from the corresponding value by an '=' character
     * even if the value is empty.
     * Each name-value pair is separated by an '&' character
     * @param array $parameters
     */
    protected function sortParameters(array $parameters)
    {
        $elements = [];
        ksort($parameters);
        foreach ($parameters as $name => $value) {
            if (is_array($value)) {
                sort($value);
                foreach ($value as $element) {
                    array_push(
                        $elements,
                        sprintf('%s=%s', $this->oauthEscape($name), $this->oauthEscape($element))
                    );
                }
                continue;
            }
            array_push(
                $elements,
                sprintf('%s=%s', $this->oauthEscape($name), $this->oauthEscape($value))
            );
        }
        return join('&', $elements);
    }

    /**
     * Escapes all special symbols for query
     * All parameter names and values are escaped using the percent-encoding (%xx) mechanism.
     * Characters not in the unreserved character set ([RFC3986] section 2.3) MUST be encoded.
     * Characters in the unreserved character set MUST NOT be encoded.
     * @param string $string
     */
    protected function oauthEscape(string $string)
    {
        return rawurlencode($string);
    }
}

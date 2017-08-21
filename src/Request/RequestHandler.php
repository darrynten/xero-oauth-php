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
    use SignatureSigner;

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
     * Session Handle used to renew the access token
     * @var string|null $oauthSessionHandle
     */
    private $oauthSessionHandle;

    /**
     * When session handle expires
     * @var \DateTime|null $oauthAuthorizationExpiresIn
     */
    private $oauthAuthorizationExpiresIn;

    /**
     * Verifier for Auth token
     *
     * @var string $tokenVerifier
     */
    private $tokenVerifier;

    /**
     * Indicates if token was verified
     *
     * @var bool|null $tokenVerified
     */
    private $tokenVerified;

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

        if (isset($config['session_handle'])) {
            $this->oauthSessionHandle = $config['session_handle'];
        }

        if (isset($config['authorization_expires_in'])) {
            $this->oauthAuthorizationExpiresIn = $config['authorization_expires_in'];
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
    public function handleRequest(
        string $method,
        string $uri,
        array $options,
        array $parameters = [],
        $contentMethod = null
    ) {
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

        $result = $contentMethod ? $contentMethod($contents) : $contents;

        return $result;
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

        if ($this->oauthSessionHandle) {
            $parts['oauth_session_handle'] = $this->oauthSessionHandle;
        }

        $newToken = $this->fetchNewToken($parts);
        if ($newToken) {
            $parts['oauth_token'] = $newToken;
        }

        return $parts;
    }

    /**
     * Get new token for Xero API requests
     *
     * @param array $parts
     * @return string|null
     * @throws ApiException
     */
    private function fetchNewToken(array $parts)
    {
        $fetchNewToken = false;
        // We should receive new RequestToken when old access token expires for public apps
        if ($this->tokenExpireTime && $this->tokenExpireTime < new \DateTime()) {
            $this->token = '';
            $this->tokenSecret = '';
            unset($parts['oauth_token']);
            $fetchNewToken = true;
        }

        if (!$this->tokenVerified) {
            $fetchNewToken = true;
        }

        if ($this->oauthAuthorizationExpiresIn && $this->oauthAuthorizationExpiresIn < new \DateTime()) {
            $fetchNewToken = true;
        }

        if ($fetchNewToken) {
            return $this->getRequestToken($parts);
        }

        return null;
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
            'oauth_session_handle' => $this->oauthSessionHandle,
            'oauth_authorization_expires_in' => $this->oauthAuthorizationExpiresIn,
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
            $parameters
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

        if (isset($decodedData['oauth_session_handle'])) {
            $this->oauthSessionHandle = $decodedData['oauth_session_handle'];
        }

        if (isset($decodedData['oauth_authorization_expires_in'])) {
            $this->oauthAuthorizationExpiresIn = new \DateTime();
            $this->oauthAuthorizationExpiresIn->modify(
                sprintf('%s seconds', $decodedData['oauth_authorization_expires_in'])
            );
        }

        if ($mode === static::REQUEST_TOKEN) {
            $this->tokenVerified = false;
            $this->tokenExpireTime = '';
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

        $signParameters = $this->getSignParameters($httpMethod, $parameters, $authToken);

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
            $parameters,
            'json_decode'
        );
    }

    /**
     * Generates array of parameters to sign
     * @param $httpMethod string
     * @param array $parameters
     * @param array $authToken
     */
    private function getSignParameters($httpMethod, $parameters, $authToken)
    {
        $signParameters = $authToken;

        if ($httpMethod === 'GET') {
            $signParameters = array_merge($signParameters, $parameters);
        }

        return $signParameters;
    }
}

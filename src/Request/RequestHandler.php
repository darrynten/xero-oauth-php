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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

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
     * The endpoint that gets passed in from config
     *
     * @var string $endpoint
     */
    private $endpoint;

    /**
     * Valid HTTP Verbs for this API
     *
     * @var array $verbs
     */
    private $verbs = [
        'GET',
        'POST',
        'PUT',
        'DELETE'
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

        $this->client = new Client();
    }

    /**
     * Makes a request using Guzzle
     *
     * @param string $verb The HTTP request verb (GET/POST/etc)
     * @param string $service The api service
     * @param string $method The services method
     * @param array $options Request options
     * @param array $parameters Request parameters
     *
     * @see RequestHandler::request()
     *
     * @return array
     * @throws ApiException
     */
    public function handleRequest(string $method, string $uri, array $options, array $parameters = [])
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

        return json_decode($response->getBody());
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
        // etc
    }

    /**
     * Make request to Xero API for the new token
     *
     * @throws ApiException
     */
    private function getRequestToken()
    {
        // etc
    }

    /**
     * Makes a request to Xero with a token
     *
     * @throws ApiException
     */
    public function request()
    {
        // etc
    }
}

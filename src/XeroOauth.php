<?php
/**
 * XeroOauth
 *
 * @category Base
 * @package  XeroOauth
 * @author   Darryn Ten <darrynten@github.com>
 * @license  MIT <https://github.com/darrynten/xero-oauth-php/blob/master/LICENSE>
 * @link     https://github.com/darrynten/xero-oauth-php
 */

namespace DarrynTen\XeroOauth;

use DarrynTen\XeroOauth\Config\BaseConfig;
use DarrynTen\XeroOauth\Config\ConfigFactory;
use DarrynTen\XeroOauth\Request\RequestHandler;

/**
 * Base class for XeroOauth manipulation
 *
 * @package XeroOauth
 */
class XeroOauth
{
    /**
     * Configuration
     *
     * @var BaseConfig $config
     */
    public $config;

    /**
     * Oauth Request Handler
     *
     * @var RequestHandler $request
     */
    private $request;

    /**
     * XeroOauth constructor
     *
     * @param array $config The API client config details
     */
    public function __construct(array $config)
    {
        $factory = new ConfigFactory();
        $this->config = $factory->getConfig($config);
        $this->request = new RequestHandler($this->config->getRequestHandlerConfig());
    }

    /**
     * Sends actual request to xero api
     * @param string $method
     * @param string $uri
     * @param array $parameters
     */
    public function request($method, $uri, $parameters = [ ])
    {
        return $this->request->request($method, $uri, $parameters);
    }

    /**
     * Returns authentication data
     * @return array
     */
    public function getAuthData()
    {
        return $this->request->getAuthData();
    }
}

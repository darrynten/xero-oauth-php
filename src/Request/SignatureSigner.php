<?php
/**
 * XeroOauth Library
 *
 * @category Library
 * @package  XeroOauth
 * @author   make.it.git <make.it.git@gmail.com>
 * @license  MIT <https://github.com/darrynten/xero-oauth-php/blob/master/LICENSE>
 * @link     https://github.com/darrynten/xero-oauth-php
 */

namespace DarrynTen\XeroOauth\Request;

use DarrynTen\XeroOauth\Exception\ConfigException;

/**
 * SignatureSigner trait
 *
 * @category Library
 * @package  XeroOauth
 * @author   Darryn Ten <darrynten@github.com>
 * @license  MIT <https://github.com/darrynten/xero-oauth-php/blob/master/LICENSE>
 * @link     https://github.com/darrynten/xero-oauth-php
 */
trait SignatureSigner
{
    /**
     * Generates oauth signature
     * @param string $method HTTP method
     * @param string $path URI
     * @param array $parameters any additional parameters passed in request
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

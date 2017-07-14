<?php
/**
 * XeroOauth Library
 *
 * @category Library
 * @package  XeroOauth
 * @author   Mikhail Levanov <leor.thesweetvoice@gmail.com>
 * @license  MIT <https://github.com/darrynten/xero-oauth-php/blob/master/LICENSE>
 * @link     https://github.com/darrynten/xero-oauth-php
 */

namespace DarrynTen\XeroOauth\Config;

use DarrynTen\XeroOauth\Exception\ConfigException;

class ConfigFactory
{
    /**
     * Value for Public Application type
     */
    const APPLICATION_TYPE_PUBLIC = 'public';

    /**
     * Value for Private Application type
     */
    const APPLICATION_TYPE_PRIVATE = 'private';

    /**
     * Value for Partner Application type
     */
    const APPLICATION_TYPE_PARTNER = 'partner';

    /**
     * Mar of dependencies between type values and classes
     * @var array
     */
    static private $map = [
        self::APPLICATION_TYPE_PUBLIC => PublicApplicationConfig::class,
        self::APPLICATION_TYPE_PRIVATE => PrivateApplicationConfig::class,
        self::APPLICATION_TYPE_PARTNER => PartnerApplicationConfig::class
    ];

    /**
     * Creates an instance of config class
     * @param array $config
     * @return BaseConfig
     * @throws ConfigException
     */
    public function getConfig(array $config)
    {
        if (!isset($config['applicationType']) || empty($config['applicationType'])) {
            throw new ConfigException(ConfigException::MISSING_APPLICATION_TYPE);
        }

        if (!array_key_exists($config['applicationType'], static::$map)) {
            throw new ConfigException(ConfigException::UNKNOWN_APPLICATION_TYPE, $config['applicationType']);
        }

        return new static::$map[ $config[ 'applicationType' ] ]($config);
    }
}

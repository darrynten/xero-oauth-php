<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth;

use DarrynTen\XeroOauth\Config\ConfigFactory;
use DarrynTen\XeroOauth\Config\PartnerApplicationConfig;
use DarrynTen\XeroOauth\Config\PrivateApplicationConfig;
use DarrynTen\XeroOauth\Config\PublicApplicationConfig;
use DarrynTen\XeroOauth\XeroOauth;

class XeroOauthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Dummy key for testing
     */
    const TEST_KEY = 'test';

    /**
     * Testing object instance
     * @var XeroOauth
     */
    private $client;

    /**
     * Checks if constructor works fine
     * @dataProvider dataProvider
     * @param $applicationType
     * @param $expected
     */
    public function testConstructor($applicationType, $expected)
    {
        $this->client = new XeroOauth([
            'applicationType' => $applicationType,
            'key' => self::TEST_KEY,
        ]);

        $this->assertInstanceOf(XeroOauth::class, $this->client);
        $this->assertInstanceOf($expected, $this->client->config);
    }

    /**
     * Provides test wit data
     * $array = [
     *   $applicationType,
     *   $expected
     * ]
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                ConfigFactory::APPLICATION_TYPE_PUBLIC,
                PublicApplicationConfig::class,
            ],
            [
                ConfigFactory::APPLICATION_TYPE_PRIVATE,
                PrivateApplicationConfig::class,
            ],
            [
                ConfigFactory::APPLICATION_TYPE_PARTNER,
                PartnerApplicationConfig::class,
            ],
        ];
    }
}

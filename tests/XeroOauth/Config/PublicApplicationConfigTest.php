<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth\Config;

use DarrynTen\XeroOauth\Config\PublicApplicationConfig;
use DarrynTen\XeroOauth\Exception\ConfigException;
use DarrynTen\XeroOauth\Exception\ExceptionMessages;

class PublicApplicationConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test key value
     */
    const TEST_KEY = 'testKey';

    /**
     * Test dummy endpoint value
     */
    const TEST_ENDPOINT = 'http://localhost:8082';

    /**
     * @var PublicApplicationConfig
     */
    private $configObject;

    /**
     * Creates an instance of a test object
     */
    public function setUp()
    {
        $this->configObject = new PublicApplicationConfig([
            'key' => static::TEST_KEY,
            'endpoint' => static::TEST_ENDPOINT
        ]);
    }

    /**
     * Checks that we have an instance of right class
     */
    public function testInstanceOf()
    {
        $this->assertInstanceOf(
            PublicApplicationConfig::class,
            $this->configObject,
            sprintf('Config must be an instance of %s', PublicApplicationConfig::class)
        );
    }

    /**
     * Checks that getRequestHandlerConfig method returns right values
     */
    public function testGetRequestHandlerConfig()
    {
        $handlerConfig = $this->configObject->getRequestHandlerConfig();

        $this->assertTrue(is_array($handlerConfig), 'Config is not an array');
        $this->assertArrayHasKey('key', $handlerConfig, 'Config does not contain key `key`');
        $this->assertEquals(static::TEST_KEY, $handlerConfig['key'], 'Key is wrong');
        $this->assertArrayHasKey('endpoint', $handlerConfig, 'Config does not contain key `endpoint`');
        $this->assertEquals(static::TEST_ENDPOINT, $handlerConfig['endpoint'], 'Endpoint is wrong');
    }

    /**
     * Checks that constructor init methods throws Exception
     */
    public function testConstructorException()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode(ConfigException::MISSING_KEY);
        $this->expectExceptionMessage(ExceptionMessages::$configErrorMessages[ConfigException::MISSING_KEY]);

        $configObject = new PublicApplicationConfig([ ]);
    }
}

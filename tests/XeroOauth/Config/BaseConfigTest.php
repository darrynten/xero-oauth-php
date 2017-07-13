<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth\Config;

use DarrynTen\XeroOauth\Config\BaseConfig;
use DarrynTen\XeroOauth\Exception\ConfigException;
use DarrynTen\XeroOauth\Exception\ExceptionMessages;
use ReflectionClass;

class BaseConfigTest extends \PHPUnit_Framework_TestCase
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
     * @var BaseConfig
     */
    private $configMock;

    /**
     * Creates mock for an abstract class
     */
    public function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder(BaseConfig::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * Checks that constructor works well and getRequestHandlerConfig method returns right values
     */
    public function testGetRequestHandlerConfig()
    {
        $reflectedClass = new ReflectionClass(BaseConfig::class);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($this->configMock, [
            'key' => static::TEST_KEY,
            'endpoint' => static::TEST_ENDPOINT
        ]);

        $handlerConfig = $this->configMock->getRequestHandlerConfig();

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

        $reflectedClass = new ReflectionClass(BaseConfig::class);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($this->configMock, [ ]);
    }
}

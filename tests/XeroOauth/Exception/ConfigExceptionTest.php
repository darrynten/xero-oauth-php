<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth\Exception;

use DarrynTen\XeroOauth\Exception\ConfigException;
use DarrynTen\XeroOauth\Exception\ExceptionMessages;

class ConfigExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Extra string in Config message
     */
    const EXTRA = 'extra';

    /**
     * Tests if exception is valid: class, code, message
     * @dataProvider dataProvider
     * @param $code
     * @param $message
     * @throws ConfigException
     */
    public function testConstructor($code, $message)
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode($code);
        $this->expectExceptionMessageRegExp(
            sprintf("/%s$/", $message)
        );

        throw new ConfigException($code);
    }

    /**
     * Tests if exception is valid with extra argument: class, code, message
     * @dataProvider dataProvider
     * @param $code
     * @param $message
     * @throws ConfigException
     */
    public function testConstructorWithExtra($code, $message)
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode($code);
        $this->expectExceptionMessageRegExp(
            sprintf(
                "/%s %s$/",
                static::EXTRA,
                $message
            )
        );

        throw new ConfigException($code, static::EXTRA);
    }

    /**
     * Provides data for tests
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                ConfigException::MISSING_KEY,
                ExceptionMessages::$configErrorMessages[ ConfigException::MISSING_KEY ]
            ],
            [
                ConfigException::UNDEFINED_CONFIG_EXCEPTION,
                ExceptionMessages::$configErrorMessages[ ConfigException::UNDEFINED_CONFIG_EXCEPTION ]
            ]
        ];
    }
}

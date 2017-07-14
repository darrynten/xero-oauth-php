<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth\Exception;

use DarrynTen\XeroOauth\Exception\ApiException;

class ApiExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test message or title
     */
    const TEST_MESSAGE = 'Test error';

    /**
     * Test status for Exception
     */
    const TEST_STATUS = 'Error';

    /**
     * Test details for Exception
     */
    const TEST_DETAIL = 'Something went wrong';

    /**
     * Test additional information for Exception
     */
    const TEST_ADDITIONAL = 'But that`s not certain';

    /**
     * Tests if exception is valid: class, code, message
     * @dataProvider dataProvider
     * @param $code
     * @param $message
     * @param $expected
     * @throws ApiException
     */
    public function testConstructor($code, $message, $expected)
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionCode($code);
        $this->expectExceptionMessage($expected);

        throw new ApiException($message, $code);
    }

    /**
     * Provides data for tests
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                1,
                static::TEST_MESSAGE,
                static::TEST_MESSAGE,
            ],
            [
                2,
                json_encode([
                    'status' => static::TEST_STATUS,
                    'title' => static::TEST_MESSAGE,
                    'detail' => static::TEST_DETAIL,
                ]),
                sprintf(
                    "%s: %s - %s",
                    static::TEST_STATUS,
                    static::TEST_MESSAGE,
                    static::TEST_DETAIL
                )
            ],
            [
                3,
                json_encode([
                    'status' => static::TEST_STATUS,
                    'title' => static::TEST_MESSAGE,
                    'detail' => static::TEST_DETAIL,
                    'errors' => [
                        static::TEST_ADDITIONAL,
                    ]
                ]),
                sprintf(
                    "%s: %s - %s - errors: %s",
                    static::TEST_STATUS,
                    static::TEST_MESSAGE,
                    static::TEST_DETAIL,
                    json_encode([
                        static::TEST_ADDITIONAL,
                    ])
                )
            ],
        ];
    }
}

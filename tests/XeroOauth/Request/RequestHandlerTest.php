<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth\Request;

use DarrynTen\XeroOauth\Request\RequestHandler;
use DarrynTen\XeroOauth\Exception\ConfigException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use ReflectionClass;

class RequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A dummy uri for testing purposes
     */
    const TEST_URI = '/test-uri';

    /**
     * A config for a RequestHandler
     * @var array
     */
    private $config = [
        'key' => 'testKey',
        'endpoint' => 'http://localhost:8082',
        'secret' => '', // we need it to sign requests
        'token' => '', // todo: we need it
        'token_secret' => '', // todo: we need it to sign requests
        'token_expires_in' => '', // todo: we need it
        'verifier' => '', // todo: need it to getAccessToken
        'callback_url' => 'http://localhost:8082',
        'sign_with' => 'HMAC-SHA1',
    ];

    /**
     * @var RequestHandler
     */
    private $handler;

    /**
     * Sets up the handler object for tests
     */
    public function setUp()
    {
        $this->handler = new RequestHandler($this->config);
    }

    /**
     * Checks if current handler object is an instance of the right class
     */
    public function testInstanceOf()
    {
        $this->assertInstanceOf(
            RequestHandler::class,
            $this->handler,
            sprintf('Handler must be an instance of %s', RequestHandler::class)
        );
    }

    /**
     * Checks if current handler throws right exception in case of bad HTTP method
     *
     * @expectedException \DarrynTen\XeroOauth\Exception\ApiException
     */
    public function testWrongMethodHandleRequest()
    {
        $this->handler->handleRequest('WRONG', '/testUri', [ ]);
    }

    /**
     * Checks if current handler throws right exception in case of Client error
     *
     * @expectedException \DarrynTen\XeroOauth\Exception\ApiException
     */
    public function testGetHandlerRequestWithException()
    {
        $this->setUpMockClient([
            new RequestException(
                'Wrong request',
                new Request('GET', 'Bad one'),
                new Response(500)
            ),
        ]);
        $this->handler->handleRequest('GET', static::TEST_URI, [ ]);
    }

    /**
     * Checks if current handler throws right exception in case of wrong signature method
     *
     * @expectedException \DarrynTen\XeroOauth\Exception\ConfigException
     * @expectedExceptionCode 11004
     * @expectedExceptionMessage Config error MD5 Unknown signature method
     */
    public function testWrongSignature()
    {
        $this->config['sign_with'] = 'MD5';
        $this->handler = new RequestHandler($this->config);
        $this->handler->generateOAuthSignature('GET', '/');
    }

    /**
     * Checks if current handler throws right exception in case of wrong private key contents
     *
     * @expectedException \DarrynTen\XeroOauth\Exception\ConfigException
     * @expectedExceptionCode 11006
     * @expectedExceptionMessage ../../mocks/Oauth/Private/privatekey_invalid.pem Private key invalid
     */
    public function testInvalidPrivateKey()
    {
        $this->config['sign_with'] = 'RSA-SHA1';
        $this->config['private_key'] = __DIR__ . '/../../mocks/Oauth/Private/privatekey_invalid.pem';
        $this->handler = new RequestHandler($this->config);
        $this->handler->generateOAuthSignature('GET', '/');
    }

    /**
     * Checks if current handler throws right exception in case of wrong private key path
     *
     * @expectedException \DarrynTen\XeroOauth\Exception\ConfigException
     * @expectedExceptionCode 11005
     * @expectedExceptionMessage Config error /tmp/some-file-does-not-exist Private key not found
     */
    public function testWrongPrivateKeyPath()
    {
        $this->config['sign_with'] = 'RSA-SHA1';
        $this->config['private_key'] = '/tmp/some-file-does-not-exist';
        $this->handler = new RequestHandler($this->config);
        $this->handler->generateOAuthSignature('GET', '/');
    }

    /**
     * Checks if we can open private key and generate signature
     */
    public function testCorrectPrivateKey()
    {
        $this->config['sign_with'] = 'RSA-SHA1';
        $this->config['private_key'] = __DIR__ . '/../../mocks/Oauth/Private/privatekey.pem';
        $this->handler = new RequestHandler($this->config);
        $sign = $this->handler->generateOAuthSignature('GET', $this->config['endpoint'] . '/', [
            'key' => 'value'
        ]);
        $this->assertEquals('sMhNGvRFVu1jjg8KgE1Loz3NHeQ0SVizSC/gJppMHpqlQrzYuHWCp+BXOHsNfcjwWK6rhxmhwu026m5OlcWO/W8RbQPh8x6kxl/HDU2mn31xXk1rqcNhWSkI2qp3WpZ+3F/BVWIIdR9iT/3tziaRMTL7MmB8ZeMPiV87wbhI/8M=', $sign);
        $sign = $this->handler->generateOAuthSignature('GET', $this->config['endpoint'] . '/', [
            'key' => [
                'value1', 'value2'
            ]
        ]);
        $this->assertEquals('HOW+ZmwzjC1rXVXLzyBVxUePRITTRxgpU1ZpSgGEGFPhkZZC1a1KZnZ2MVe23+0BSH9hDfLhFloF6P7NJljRaZmQIs+e4P0iNQCZ0t7n7dImJqEQi+dtvvhmVVH6a8r/0AdL4Ckkie20OCSQ0zotzC5zoMtbv6dYLq0ChDmAdK0=', $sign);
        $sign = $this->handler->generateOAuthSignature('GET', $this->config['endpoint'] . '/', [
            'key' => 'some+value_with(many){special} symbols<>!*\''
        ]);
        $this->assertEquals('MPRY2wIf2lONqd9D0atkUFHB8FZBvvFjUHWe4Cp8DTz0ltvJsixyKYsnYGtYT1UhRdKuRWnzqEbUwM0oJcSZiMO/jhFsUIlwOt1PCY0ZY1T1s6xE0ZMxE6b5/cgZI8dmGWMKL6ZuFIsq+T7JzPXfr5ZV8hyAHT0S3UZwM8Byd0g=', $sign);
    }

    /**
     * Checks if we can send right request
     */
    public function testCorrectPrivateKeyRequest()
    {
        $this->config['sign_with'] = 'RSA-SHA1';
        $this->config['private_key'] = __DIR__ . '/../../mocks/Oauth/Private/privatekey.pem';

        $result = \GuzzleHttp\json_encode(['OK' => true]);
        $method = 'GET';
        $uri = 'test-uri';
        $parameters = ['key' => 'value'];

        $this->setUpMockClient([
            new Response(
                200,
                [ 'ContentType: application/json' ],
                $result
            ),
        ]);

        $this->assertEquals(
            \GuzzleHttp\json_decode($result),
            $this->handler->handleRequest($method, $uri, $parameters)
        );
    }

    /**
     * Checks signature for HMAC-SHA1 signing
     */
    public function testHMACSHA1Signature()
    {
        $this->config['sign_with'] = 'HMAC-SHA1';
        $this->handler = new RequestHandler($this->config);
        $sign = $this->handler->generateOAuthSignature('GET', $this->config['endpoint'] . '/', [
            'key' => 'value'
        ]);
        $this->assertEquals('lVbOBzouFcQRiEojE7q3ZsNoEto=', $sign);
        $sign = $this->handler->generateOAuthSignature('GET', $this->config['endpoint'] . '/', [
            'key' => [
                'value1', 'value2'
            ]
        ]);
        $this->assertEquals('/CCpy/zVTYPrYQJIb/nT+imlQdw=', $sign);
        $sign = $this->handler->generateOAuthSignature('GET', $this->config['endpoint'] . '/', [
            'key' => 'some+value_with(many){special} symbols<>!*\''
        ]);
        $this->assertEquals('6q4LKua/vKE7AuSFqoU26SQ3ovg=', $sign);
    }

    /**
     * Tests if current handler reacts right on different HTTP method requests
     *
     * @dataProvider dataProvider
     * @param $method
     * @param $uri
     * @param $result
     */
    public function testHandleRequest($method, $uri, $result)
    {
        $this->setUpMockClient([
            new Response(
                200,
                [ 'ContentType: application/json' ],
                $result
            ),
        ]);

        $this->assertEquals(
            \GuzzleHttp\json_decode($result),
            $this->handler->handleRequest($method, $uri, [ ])
        );
    }

    /**
     * Checks request method of current handler
     * Checks only case with getting Access Token
     */
    public function testRequest()
    {
        $this->assertTrue(
            method_exists($this->handler, 'request'),
            'Method not found'
        );

        $expectedResult = \GuzzleHttp\json_encode([
            'status' => 'ready',
        ]);

        $this->setUpMockClient([
            new Response(
                200,
                [ 'ContentType: application/json' ],
                'oauth_token=FT24XKBIJMGNWRBDCSWXTRHUYS3BZA&oauth_token_secret=MX9WR46QZAVCIQGA4EIM1RITMZARMT&oauth_callback_confirmed=true'
            ),
            new Response(
                200,
                [ 'ContentType: application/json' ],
                $expectedResult
            ),
        ]);

        $result = $this->handler->request(
            'GET',
            self::TEST_URI,
            [ ]
        );
        $this->assertEquals(\GuzzleHttp\json_decode($expectedResult), $result);
    }

    /**
     * Provides data for testing
     *
     * @return array
     */
    public function dataProvider()
    {
        $testResponse = \GuzzleHttp\json_encode([
            'status' => 'OK',
        ]);

        return [
            [
                'GET',
                static::TEST_URI,
                $testResponse,
            ],
            [
                'POST',
                static::TEST_URI,
                $testResponse,
            ],
            [
                'PUT',
                static::TEST_URI,
                $testResponse,
            ],
            [
                'DELETE',
                static::TEST_URI,
                $testResponse,
            ],
        ];
    }

    /**
     * Utility method for GuzzleHttp\MockHandler setup
     *
     * @param $results
     */
    private function setUpMockClient($results)
    {
        $mockHandler = new MockHandler($results);

        $handler = HandlerStack::create($mockHandler);
        $mockClient = new Client([
            'handler' => $handler,
        ]);

        $reflection = new ReflectionClass($this->handler);
        $reflectedClient = $reflection->getProperty('client');
        $reflectedClient->setAccessible(true);
        $reflectedClient->setValue($this->handler, $mockClient);
    }
}

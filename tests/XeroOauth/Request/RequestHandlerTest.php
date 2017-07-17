<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth\Request;

use DarrynTen\XeroOauth\Request\RequestHandler;
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
                \GuzzleHttp\json_encode([
                    'oauth_token' => uniqid('token'),
                    'oauth_token_secret' => uniqid('secret'),
                    'oauth_expires_in' => new \DateTime('+2 hours')
                ])
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

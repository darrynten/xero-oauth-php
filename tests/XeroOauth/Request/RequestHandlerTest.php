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
        'endpoint' => 'http://localhost:8082'
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
        $this->assertInstanceOf(RequestHandler::class, $this->handler, 'Handler must be an instance of '.RequestHandler::class);
    }

    /**
     * Checks if current handler throws right exception in case of bad HTTP method
     *
     * @expectedException \DarrynTen\XeroOauth\Exception\ApiException
     */
    public function testWrongMethodHandleRequest()
    {
        $this->handler->handleRequest('WRONG', '/testUri', []);
    }

    /**
     * Checks if current handler throws right exception in case of Client error
     *
     * @expectedException \DarrynTen\XeroOauth\Exception\ApiException
     */
    public function testGetHandlerRequestWithException()
    {
        $this->setUpMockClient(
            new RequestException('Wrong request', new Request('GET', 'Bad one'), new Response(500))
        );
        $this->handler->handleRequest('GET', static::TEST_URI, []);
    }

    /**
     * Tests if current handler reacts right on different HTTP method requests
     *
     * @dataProvider dataProvider
     */
    public function testHandleRequest($method, $uri, $result)
    {
        $this->setUpMockClient(
            new Response(200, ['ContentType: application/json'], $result)
        );

        $this->assertEquals(
            \GuzzleHttp\json_decode($result),
            $this->handler->handleRequest($method, $uri, [])
        );
    }

    /**
     * Checks if current handler has a method
     */
    public function testRequest()
    {
        $this->assertTrue(method_exists($this->handler, 'request'), 'Method not found');
    }

    /**
     * Provides data for testing
     *
     * @return array
     */
    public function dataProvider()
    {
        $testResponse = \GuzzleHttp\json_encode(['status' => 'OK']);

        return [
            [
                'GET',
                static::TEST_URI,
                $testResponse
            ],
            [
                'POST',
                static::TEST_URI,
                $testResponse
            ],
            [
                'PUT',
                static::TEST_URI,
                $testResponse
            ],
            [
                'DELETE',
                static::TEST_URI,
                $testResponse
            ],
        ];
    }

    /**
     * Utility method for GuzzleHttp\MockHandler setup
     *
     * @param $result
     */
    private function setUpMockClient($result)
    {
        $mockHandler = new MockHandler([
            $result
        ]);

        $handler = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handler]);

        $reflection = new ReflectionClass($this->handler);
        $reflectedClient = $reflection->getProperty('client');
        $reflectedClient->setAccessible(true);
        $reflectedClient->setValue($this->handler, $mockClient);
    }
}

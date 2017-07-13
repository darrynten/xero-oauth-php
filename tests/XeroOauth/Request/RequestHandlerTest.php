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
    const TEST_URI = '/test-uri';

    private $config = [
        'key' => 'testKey',
        'endpoint' => 'http://localhost:8082'
    ];

    /**
     * @var RequestHandler
     */
    private $handler;

    public function setUp()
    {
        $this->handler = new RequestHandler($this->config);
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf(RequestHandler::class, $this->handler, 'Handler must be an instance of '.RequestHandler::class);
    }

    /**
     * @expectedException \DarrynTen\XeroOauth\Exception\ApiException
     */
    public function testWrongMethodHandleRequest()
    {
        $this->handler->handleRequest('WRONG', '/testUri', []);
    }

    /**
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

    public function testRequest() {
        $this->assertTrue(method_exists($this->handler, 'request'), 'Method not found');
    }

    public function dataProvider()
    {
        $testResponse = \GuzzleHttp\json_encode(['status' => 'OK']);

        return [
            ['GET', static::TEST_URI, $testResponse],
            ['POST', static::TEST_URI, $testResponse],
            ['PUT', static::TEST_URI, $testResponse],
            ['DELETE', static::TEST_URI, $testResponse]
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

<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth;

use DarrynTen\XeroOauth\Config\ConfigFactory;
use DarrynTen\XeroOauth\Config\PartnerApplicationConfig;
use DarrynTen\XeroOauth\Config\PrivateApplicationConfig;
use DarrynTen\XeroOauth\Config\PublicApplicationConfig;
use DarrynTen\XeroOauth\XeroOauth;
use DarrynTen\XeroOauth\Request\RequestHandler;
use DarrynTen\XeroOauth\Exception\AuthException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ReflectionClass;

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

    public function testPrivateAuth()
    {
        $privateKey = __DIR__ . '/../mocks/Oauth/Private/privatekey.pem';
        $result = \GuzzleHttp\json_encode([
            'ready' => true
        ]);

        $this->setUpMockClient([
            new Response(
                200,
                [ 'ContentType: application/json' ],
                $result
            ),
        ], ConfigFactory::APPLICATION_TYPE_PRIVATE, [
            'private_key' => $privateKey
        ]);

        $this->assertEquals(
            \GuzzleHttp\json_decode($result),
            $this->client->request('GET', 'TEST')
        );
    }

    public function testPublicAuth()
    {
        $expectedResult = \GuzzleHttp\json_encode([
            'status' => 'ready',
        ]);
        $expectedResult2 = \GuzzleHttp\json_encode([
            'status2' => 'ready2',
        ]);

        $this->setUpMockClient([
            new Response(
                200,
                [ 'ContentType: application/json' ],
                'oauth_token=FT24XKBIJMGNWRBDCSWXTRHUYS3BZA&oauth_token_secret=MX9WR46QZAVCIQGA4EIM1RITMZARMT&oauth_callback_confirmed=true'
            ),
        ], ConfigFactory::APPLICATION_TYPE_PUBLIC, [
        ]);

        try {
            $this->client->request('GET', 'TEST');
            throw new \Exception('AuthException was not thrown');
        } catch (AuthException $e) {
        }

        $authData = $this->client->getAuthData();
        $this->assertCount(7, $authData);
        $this->assertEquals('FT24XKBIJMGNWRBDCSWXTRHUYS3BZA', $authData['oauth_token']);
        $this->assertEquals('MX9WR46QZAVCIQGA4EIM1RITMZARMT', $authData['oauth_token_secret']);
        $this->assertEquals('', $authData['oauth_verifier']);
        $this->assertEquals(false, $authData['token_verified']);
        $this->assertEquals('', $authData['oauth_expires_in']);

        // user allowed our access here

        $this->setUpMockClient([
            new Response(
                200,
                [ 'ContentType: application/json' ],
                'oauth_token=VON4JH67XGQBDAL8ASZQCMYVQRMEZY&oauth_token_secret=0XH8NZTF1TS6GB73PTBDXPPVISQKRS&oauth_expires_in=1800&xero_org_muid=bCjSHi%24ODqQhFUHw4EYtvm'
            ),
            new Response(
                200,
                [ 'ContentType: application/json' ],
                $expectedResult
            ),
            new Response(
                200,
                [ 'ContentType: application/json' ],
                $expectedResult2
            )
        ], ConfigFactory::APPLICATION_TYPE_PUBLIC, [
            'token' => 'FT24XKBIJMGNWRBDCSWXTRHUYS3BZA',
            'token_secret' => 'MX9WR46QZAVCIQGA4EIM1RITMZARMT',
            'verifier' => '123456'
        ]);

        $result = $this->client->request('GET', 'TEST');

        $authData = $this->client->getAuthData();
        $this->assertCount(7, $authData);
        $this->assertEquals('VON4JH67XGQBDAL8ASZQCMYVQRMEZY', $authData['oauth_token']);
        $this->assertEquals('0XH8NZTF1TS6GB73PTBDXPPVISQKRS', $authData['oauth_token_secret']);
        $this->assertEquals('123456', $authData['oauth_verifier']);
        $this->assertEquals(true, $authData['token_verified']);
        $this->assertInstanceOf(\DateTime::class, $authData['oauth_expires_in']);

        $this->assertEquals(
            \GuzzleHttp\json_decode($expectedResult),
            $result
        );

        $result2 = $this->client->request('GET', 'TEST');
        $this->assertEquals(
            \GuzzleHttp\json_decode($expectedResult2),
            $result2
        );
    }

    /**
     * @param $results
     * @param $applicationType
     * @param array $options
     */
    private function setUpMockClient($results, $applicationType, $options)
    {
        $mockHandler = new MockHandler($results);

        $handler = HandlerStack::create($mockHandler);
        $mockClient = new Client([
            'handler' => $handler,
        ]);

        $construtor = [
            'applicationType' => $applicationType,
            'key' => self::TEST_KEY
        ];
        $constructor = array_merge($construtor, $options);

        $this->client = new XeroOauth($constructor);

        $reflection = new ReflectionClass($this->client);
        $reflectedRequest = $reflection->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedRequestValue = $reflectedRequest->getValue($this->client);

        $handler = new ReflectionClass($reflectedRequestValue);
        $handlerClient = $handler->getProperty('client');
        $handlerClient->setAccessible(true);
        $handlerClient->setValue($reflectedRequestValue, $mockClient);
    }
}

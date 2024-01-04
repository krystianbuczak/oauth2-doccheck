<?php

namespace krystianbuczak\OAuth2\Client\Test\Provider;

use _PHPStan_b8e553790\RingCentral\Psr7\Stream;
use InvalidArgumentException;
use Mockery as m;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\TestCase;
use krystianbuczak\OAuth2\Client\Provider\DocCheck;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\ClientInterface;

use function _PHPStan_b8e553790\RingCentral\Psr7\parse_query;

class DocCheckTest extends TestCase
{
    /**
     * @var DocCheck
     */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new DocCheck([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testGetAuthorizationUrl(): void
    {
        $options = [
            'dc_language' => 'nl',
            'dc_template' => 'login_xl'
        ];
        $url = $this->provider->getAuthorizationUrl($options);
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertEquals('/code/', $uri['path']);
        $this->assertEquals('nl', $query['dc_language']);
        $this->assertEquals('login_xl', $query['dc_template']);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayNotHasKey('scope', $query);
        $this->assertArrayNotHasKey('response_type', $query);
        $this->assertArrayNotHasKey('approval_prompt', $query);
    }

    public function testGetAuthorizationUrlWithFallbackParams(): void
    {
        $options = [
            'dc_language' => 'fake_lang',
            'dc_template' => 'fake_template'
        ];
        $url = $this->provider->getAuthorizationUrl($options);
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertEquals('com', $query['dc_language']);
        $this->assertEquals('s_mobile', $query['dc_template']);
    }

    public function testGetBaseAccessTokenUrl(): void
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        $this->assertEquals('/service/oauth/access_token', $uri['path']);
    }

    /**
     * @link https://docs.doccheck.com/login-implementation/oauth/endpoints/user_data_endpoint_v2.html
     */
    public function testResourceOwnerDetailsUrl(): void
    {
        $token = new AccessToken([
            'access_token' => 'mocked_access_token',
        ]);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);

        $this->assertEquals('https://login.doccheck.com/service/oauth/user_data/v2/', $url);
    }

    /**
     * @throws IdentityProviderException
     * @throws \JsonException
     */
    public function testGetAccessToken(): void
    {
        $response = m::mock(ResponseInterface::class);
        $json = json_encode([
            'access_token' => 'mocked_access_token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'refresh_token' => 'mocked_refresh_token'
        ], JSON_THROW_ON_ERROR);

        $bodyStream = m::mock(StreamInterface::class);
        $bodyStream->allows('__toString')
            ->andReturns($json);

        $response->allows('getBody')
            ->andReturns($bodyStream);
        $response->allows('getHeader')
            ->andReturns(['content-type' => 'json']);
        $response->allows('getStatusCode')
            ->andReturns(200);

        $client = m::mock(ClientInterface::class);
        $client->expects('send')->times(1)->andReturns($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mocked_authorization_code']);

        $this->assertEquals('mocked_access_token', $token->getToken());
        $this->assertEquals(time() + 3600, $token->getExpires());
        $this->assertEquals('mocked_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }



    /**
     * @throws \JsonException
     */
    public function testExceptionThrownWhenOAuthErrorReceived(): void
    {
        $status = 200;
        $jsonContent = json_encode([
            "error" => "bad_request",
            "error_description" => "Authorization code not found or not valid."
        ], JSON_THROW_ON_ERROR);
        $bodyStream = m::mock(StreamInterface::class);
        $bodyStream->allows('__toString')
            ->andReturns($jsonContent);

        $postResponse = m::mock(ResponseInterface::class);
        $postResponse->allows('getBody')
            ->andReturns($bodyStream);
        $postResponse->allows('getHeader')->andReturns(['content-type' => 'json']);
        $postResponse->allows('getStatusCode')->andReturns($status);

        $client = m::mock(ClientInterface::class);
        $client->expects('send')
            ->times(1)
            ->andReturns($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mocked_authorization_code']);
    }
}

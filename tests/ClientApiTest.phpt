<?php

namespace SizeID\OAuth2\Tests;

use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use Mockery as m;
use SizeID\OAuth2\Api;
use SizeID\OAuth2\ClientApi;
use SizeID\OAuth2\Entities\AccessToken;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class ClientApiTest extends TestCase
{

	public function testSimple()
	{
		$clientApi = new ClientApi(
			'clientId',
			'clientSecret'
		);
		Assert::type('SizeID\OAuth2\ClientApi', $clientApi);
	}

	public function testAcquireToken()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\SessionAccessTokenRepository');
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(FALSE);
		$tokenRepository
			->shouldReceive('saveAccessToken');
		$accessToken = new AccessToken('value');
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn($accessToken);
		$httpClient = m::mock('GuzzleHttp\Client');
		$stream = m::mock('GuzzleHttp\Stream\StreamInterface');
		$stream
			->shouldReceive('getContents')
			->andReturn('{"access_token":"token", "expires_in": 60}');
		$response = new Response(200, [], $stream);
		$httpClient
			->shouldReceive('post')
			->andReturn($response);
		$httpClient
			->shouldReceive('send')
			->andReturn(new Response(200));
		$clientApi = new ClientApi(
			'clientId',
			'clientSecret',
			$tokenRepository,
			NULL,
			NULL,
			$httpClient
		);
		Assert::type('SizeID\OAuth2\ClientApi', $clientApi);
		$clientApi->send(new Request('get', 'client'));
	}

	public function testRefreshToken()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\SessionAccessTokenRepository');
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(TRUE);
		$accessToken = new AccessToken('value');
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn($accessToken);
		$tokenRepository
			->shouldReceive('saveAccessToken');
		$httpClient = m::mock('GuzzleHttp\Client');
		$response = new Response(401, [Api::SIZEID_ERROR_CODE_HEADER => "109"]);
		$httpClient
			->shouldReceive('send')
			->andReturn($response);
		$stream = m::mock('GuzzleHttp\Stream\StreamInterface');
		$stream
			->shouldReceive('getContents')
			->andReturn('{"access_token":"token", "expires_in": 60}');
		$response = new Response(200, [], $stream);
		$httpClient
			->shouldReceive('post')
			->once()->andReturn($response);
		$httpClient
			->shouldReceive('send')
			->once()->andReturn(new Response(200));
		$clientApi = new ClientApi(
			'clientId',
			'clientSecret',
			$tokenRepository,
			NULL,
			NULL,
			$httpClient
		);
		Assert::type('SizeID\OAuth2\ClientApi', $clientApi);
		Assert::type('GuzzleHttp\Message\Response', $clientApi->send(new Request('POST', 'client')));
	}
}

$test = new ClientApiTest();
$test->run();

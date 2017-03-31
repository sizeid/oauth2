<?php

namespace SizeID\OAuth2\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use SizeID\OAuth2\Api;
use SizeID\OAuth2\ClientApi;
use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Repositories\SessionAccessTokenRepository;
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
		Assert::type(ClientApi::class, $clientApi);
	}

	public function testAcquireToken()
	{
		$tokenRepository = m::mock(SessionAccessTokenRepository::class);
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(false);

		$tokenRepository
			->shouldReceive('saveAccessToken');

		$accessToken = new AccessToken('value');
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn($accessToken);

		$httpClient = m::mock(Client::class);
		$response = new Response(200, [], '{"access_token":"token", "expires_in": 60}');
		$httpClient
			->shouldReceive('request')
			->andReturn($response);

		$httpClient
			->shouldReceive('send');

		$clientApi = new ClientApi(
			'clientId',
			'clientSecret',
			$tokenRepository,
			null,
			null,
			$httpClient
		);
		Assert::type(ClientApi::class, $clientApi);

		$clientApi->send(new Request('get', 'client'));
	}

	public function testRefreshToken()
	{

		$tokenRepository = m::mock(SessionAccessTokenRepository::class);
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(true);

		$accessToken = new AccessToken('value');
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn($accessToken);

		$tokenRepository
			->shouldReceive('saveAccessToken');

		$httpClient = m::mock(Client::class);

		$response = new Response(401, [Api::SIZEID_ERROR_CODE_HEADER => "109"]);

		$e = m::mock(ClientException::class);
		$e->shouldReceive('getResponse')
			->andReturn($response);
		$httpClient
			->shouldReceive('send')
			->once()->andThrow($e);

		$httpClient
			->shouldReceive('send')
			->once()->andReturn(new Response());

		$response = new Response(200, [], '{"access_token":"token", "expires_in": 60}');
		$httpClient
			->shouldReceive('request')
			->andReturn($response);

		$clientApi = new ClientApi(
			'clientId',
			'clientSecret',
			$tokenRepository,
			null,
			null,
			$httpClient
		);
		Assert::type(ClientApi::class, $clientApi);

		Assert::type(Response::class, $clientApi->send(new Request('POST', 'client')));

	}

}

$test = new ClientApiTest();
$test->run();

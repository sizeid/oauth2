<?php

namespace SizeID\OAuth2\Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use SizeID\OAuth2\ClientApi;
use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Exceptions\InvalidStateException;
use SizeID\OAuth2\Repositories\SessionAccessTokenRepository;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class ApiTest extends TestCase
{

	public function testHasAccessToken()
	{
		$tokenRepository = m::mock(SessionAccessTokenRepository::class);
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(NULL);

		$clientApi = new ClientApi(
			'clientId',
			'clientSecret',
			$tokenRepository
		);

		Assert::exception(
			function () use ($clientApi) {
				$clientApi->send(new Request('get', 'client'));
			}
			,
			InvalidStateException::class
		);
	}

	public function testGetAcessToken()
	{
		$tokenRepository = m::mock(SessionAccessTokenRepository::class);
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn(new \stdClass());
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(true);

		$tokenRepository
			->shouldReceive('deleteAccessToken');

		$clientApi = new ClientApi(
			'clientId',
			'clientSecret',
			$tokenRepository
		);

		Assert::exception(
			function () use ($clientApi) {
				$clientApi->send(new Request('get', 'client'));
			}
			,
			InvalidStateException::class
		);
	}

	public function testGetInvalidAccessToken()
	{
		$tokenRepository = m::mock(SessionAccessTokenRepository::class);
		$accessToken = new AccessToken(NULL, 60);
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn($accessToken);
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(true);

		$tokenRepository
			->shouldReceive('deleteAccessToken');

		$clientApi = new ClientApi(
			'clientId',
			'clientSecret',
			$tokenRepository
		);

		Assert::exception(
			function () use ($clientApi) {
				$clientApi->send(new Request('get', 'client'));
			}
			,
			InvalidStateException::class
		);
	}


	public function testClientException()
	{
		$tokenRepository = m::mock(SessionAccessTokenRepository::class);
		$accessToken = new AccessToken("value", 60);
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn($accessToken);
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(true);
		$tokenRepository
			->shouldReceive('deleteAccessToken');

		$httpClient = m::mock(ClientInterface::class);

		$clientException = m::mock(ClientException::class);

		$clientException
			->shouldReceive('getResponse')
			->andReturn(new Response(401));

		$httpClient
			->shouldReceive('send')
			->andThrow($clientException);

		$clientApi = new ClientApi(
			'clientId',
			'clientSecret',
			$tokenRepository,
			null,
			null,
			$httpClient
		);

		Assert::exception(
			function () use ($clientApi) {
				$clientApi->send(new Request('get', 'client'));
			}
			,
			ClientException::class
		);
	}


}

$test = new ApiTest();
$test->run();

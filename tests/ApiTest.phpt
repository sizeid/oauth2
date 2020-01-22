<?php

namespace SizeID\OAuth2\Tests;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use SizeID\OAuth2\ClientApi;
use SizeID\OAuth2\Entities\AccessToken;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class ApiTest extends TestCase
{

	public function testHasAccessToken()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\SessionAccessTokenRepository');
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
			'SizeID\OAuth2\Exceptions\InvalidStateException'
		);
	}

	public function testGetAcessToken()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\SessionAccessTokenRepository');
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn(new \stdClass());
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(TRUE);
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
			'SizeID\OAuth2\Exceptions\InvalidStateException'
		);
	}

	public function testGetInvalidAccessToken()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\SessionAccessTokenRepository');
		$accessToken = new AccessToken(NULL);
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn($accessToken);
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(TRUE);
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
			'SizeID\OAuth2\Exceptions\InvalidStateException'
		);
	}

	public function testClientException()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\SessionAccessTokenRepository');
		$accessToken = new AccessToken("value");
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn($accessToken);
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(TRUE);
		$tokenRepository
			->shouldReceive('deleteAccessToken');
		$httpClient = m::mock('GuzzleHttp\ClientInterface');
		$clientException = m::mock('GuzzleHttp\Exception\ClientException');
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
			NULL,
			NULL,
			$httpClient
		);
		Assert::exception(
			function () use ($clientApi) {
				$clientApi->send(new Request('get', 'client'));
			}
			,
			'GuzzleHttp\Exception\ClientException'
		);
	}
}

$test = new ApiTest();
$test->run();

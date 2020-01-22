<?php

namespace SizeID\OAuth2\Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Psr\Http\Message\StreamInterface;
use SizeID\OAuth2\Api;
use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Exceptions\InvalidCSRFTokenException;
use SizeID\OAuth2\Exceptions\InvalidStateException;
use SizeID\OAuth2\Exceptions\RedirectException;
use SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface;
use SizeID\OAuth2\Repositories\CsrfTokenRepositoryInterface;
use SizeID\OAuth2\UserApi;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class UserApiTest extends TestCase
{

	public function testSimple()
	{
		$clientApi = new UserApi(
			'clientId',
			'clientSecret',
			'http://9gag.com'
		);
		Assert::type(UserApi::class, $clientApi);
	}

	public function testAuthorize()
	{
		$tokenRepository = m::mock(AccessTokenRepositoryInterface::class);
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->once()
			->andReturn(false);
		$tokenRepository
			->shouldReceive('saveAccessToken');
		$stateRepository = m::mock(CsrfTokenRepositoryInterface::class);
		$stateRepository
			->shouldReceive('generateCSRFToken')
			->andReturn('csrfToken');
		$stateRepository
			->shouldReceive('loadTokenCSRFToken')
			->andReturn('csrfToken');
		$httpClient = m::mock(ClientInterface::class);
		$userApi = new UserApi(
			'clientId',
			'clientSecret',
			'redirectUri',
			$tokenRepository,
			'authServer',
			'apiUrl',
			$httpClient,
			$stateRepository
		);
		try {
			$userApi->send(new Request('get', 'user'));
			Assert::fail(RedirectException::class . ' should be thrown');
		} catch (RedirectException $ex) {
			Assert::equal(RedirectException::CODE_MISSING_TOKEN, $ex->getCode());
			Assert::equal(
				'authServer?response_type=code&client_id=clientId&redirect_uri=redirectUri&state=csrfToken',
				(string)$ex->getRedirectUrl()
			);
		}
		$stream = m::mock(StreamInterface::class);
		$stream
			->shouldReceive('getContents')
			->andReturn('{"access_token":"token", "expires_in": 60, "refresh_token": "refresh_token"}');
		$httpClient
			->shouldReceive('post')
			->once()
			->andReturn(new Response(200, [], $stream));
		$userApi->completeAuthorization("authCode", "csrfToken");
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->andReturn(true);
		$tokenRepository
			->shouldReceive('deleteAccessToken');
		$tokenRepository
			->shouldReceive('getAccessToken')
			->once()
			->andReturn(new AccessToken('accessToken'));
		Assert::exception(
			function () use ($userApi) {
				$userApi->send(new Request('get', 'user'));
			},
			InvalidStateException::class
		);
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn(new AccessToken('accessToken', 'refreshToken'));
		$httpClient
			->shouldReceive('send')
			->andReturn(new Response(200));
		Assert::type(Response::class, $userApi->send(new Request('get', 'user')));
	}

	public function testRefreshToken()
	{
		$tokenRepository = m::mock(AccessTokenRepositoryInterface::class);
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn(new AccessToken('accessToken', 'refreshToken'));
		$tokenRepository
			->shouldReceive('saveAccessToken')
			->with(
				m::on(
					function (AccessToken $accessToken) {
						Assert::equal('newRefreshToken', $accessToken->getRefreshToken());
						return true;
					}
				)
			);
		$stream = m::mock(StreamInterface::class);
		$stream
			->shouldReceive('getContents')
			->andReturn('{"access_token":"token", "expires_in": 60, "refresh_token": "newRefreshToken"}');
		$httpClient = m::mock(ClientInterface::class);
		$httpClient
			->shouldReceive('post')
			->andReturn(new Response(200, [], $stream));
		$userApi = new UserApi(
			'clientId',
			'clientSecret',
			'redirectUri',
			$tokenRepository,
			'authServer',
			'apiUrl',
			$httpClient,
			null
		);
		$userApi->refreshAccessToken();
	}

	public function testInvalidRefreshToken()
	{
		$tokenRepository = m::mock(AccessTokenRepositoryInterface::class);
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn(new AccessToken('accessToken', 'refreshToken'));
		$responseException = m::mock(ClientException::class);
		$errorResponse = new Response(
			400,
			[Api::SIZEID_ERROR_CODE_HEADER => "108"]
		);
		$responseException
			->shouldReceive('getResponse')
			->andReturn($errorResponse);
		$httpClient = m::mock(ClientInterface::class);
		$httpClient
			->shouldReceive('post')
			->once()
			->andThrow($responseException);
		$userApi = new UserApi(
			'clientId',
			'clientSecret',
			'redirectUri',
			$tokenRepository,
			'authServer',
			'apiUrl',
			$httpClient,
			null
		);
		Assert::exception(
			function () use ($userApi) {
				$userApi->refreshAccessToken();
			},
			RedirectException::class,
			null,
			RedirectException::CODE_EXPIRED_REFRESH_TOKEN
		);
		$responseException = m::mock(ClientException::class);
		$errorResponse = new Response(
			400
		);
		$responseException
			->shouldReceive('getResponse')
			->andReturn($errorResponse);
		$httpClient
			->shouldReceive('post')
			->once()
			->andThrow($responseException);
		Assert::exception(
			function () use ($userApi) {
				$userApi->refreshAccessToken();
			}
			,
			ClientException::class
		);
	}

	public function testCompleteAuthorizationWithDefault()
	{
		$_GET['code'] = 'code';
		$_GET['state'] = 'state1';
		$csrfTokenRepository = m::mock(CsrfTokenRepositoryInterface::class);
		$csrfTokenRepository
			->shouldReceive('loadTokenCSRFToken')
			->andReturn('state2');
		$userApi = new UserApi(
			'clientId',
			'clientSecret',
			'redirectUri',
			null,
			'authServer',
			'apiUrl',
			null,
			null
		);
		Assert::exception(
			function () use ($userApi) {
				$userApi->completeAuthorization();
			},
			InvalidCSRFTokenException::class,
			'Invalid CSRF token.'
		);
	}
}

$test = new UserApiTest();
$test->run();

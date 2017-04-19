<?php

namespace SizeID\OAuth2\Tests;

use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use Mockery as m;
use SizeID\OAuth2\Api;
use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Exceptions\RedirectException;
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
		Assert::type('SizeID\OAuth2\UserApi', $clientApi);
	}

	public function testAuthorize()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface');
		$tokenRepository
			->shouldReceive('hasAccessToken')
			->once()
			->andReturn(FALSE);
		$tokenRepository
			->shouldReceive('saveAccessToken');
		$stateRepository = m::mock('SizeID\OAuth2\Repositories\CsrfTokenRepositoryInterface');
		$stateRepository
			->shouldReceive('generateCSRFToken')
			->andReturn('csrfToken');
		$stateRepository
			->shouldReceive('loadTokenCSRFToken')
			->andReturn('csrfToken');
		$httpClient = m::mock('GuzzleHttp\ClientInterface');
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
			Assert::fail('SizeID\OAuth2\Exceptions\RedirectException' . ' should be thrown');
		} catch (RedirectException $ex) {
			Assert::equal(RedirectException::CODE_MISSING_TOKEN, $ex->getCode());
			Assert::equal(
				'authServer?response_type=code&client_id=clientId&redirect_uri=redirectUri&state=csrfToken',
				(string)$ex->getRedirectUrl()
			);
		}
		$stream = m::mock('GuzzleHttp\Stream\StreamInterface');
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
			->andReturn(TRUE);
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
			'SizeID\OAuth2\Exceptions\InvalidStateException'
		);
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn(new AccessToken('accessToken', 'refreshToken'));
		$httpClient
			->shouldReceive('send')
			->andReturn(new Response(200));
		Assert::type('GuzzleHttp\Message\Response', $userApi->send(new Request('get', 'user')));
	}

	public function testRefreshToken()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface');
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn(new AccessToken('acessToken', 'refreshToken'));
		$tokenRepository
			->shouldReceive('saveAccessToken')
			->with(
				m::on(
					function (AccessToken $accessToken) {
						Assert::equal('newRefreshToken', $accessToken->getRefreshToken());
						return TRUE;
					}
				)
			);
		$stream = m::mock('GuzzleHttp\Stream\StreamInterface');
		$stream
			->shouldReceive('getContents')
			->andReturn('{"access_token":"token", "expires_in": 60, "refresh_token": "newRefreshToken"}');
		$httpClient = m::mock('GuzzleHttp\ClientInterface');
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
			NULL
		);
		$userApi->refreshAccessToken();
	}

	public function testInvalidRefreshToken()
	{
		$tokenRepository = m::mock('SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface');
		$tokenRepository
			->shouldReceive('getAccessToken')
			->andReturn(new AccessToken('acessToken', 'refreshToken'));
		$responseException = m::mock('GuzzleHttp\Exception\ClientException');
		$errorResponse = new Response(
			400,
			[Api::SIZEID_ERROR_CODE_HEADER => "108"]
		);
		$responseException
			->shouldReceive('getResponse')
			->andReturn($errorResponse);
		$httpClient = m::mock('GuzzleHttp\ClientInterface');
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
			NULL
		);
		Assert::exception(
			function () use ($userApi) {
				$userApi->refreshAccessToken();
			}
			,
			'SizeID\OAuth2\Exceptions\RedirectException',
			NULL,
			RedirectException::CODE_EXPIRED_REFRESH_TOKEN
		);
		$responseException = m::mock('GuzzleHttp\Exception\ClientException');
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
			'GuzzleHttp\Exception\ClientException'
		);
	}

	public function testCompleteAuthorizationWithDefault()
	{
		$_GET['code'] = 'code';
		$_GET['state'] = 'state1';
		$csrfTokenRepository = m::mock('SizeID\OAuth2\Repositories\CsrfTokenRepositoryInterface');
		$csrfTokenRepository
			->shouldReceive('loadTokenCSRFToken')
			->andReturn('state2');
		$userApi = new UserApi(
			'clientId',
			'clientSecret',
			'redirectUri',
			NULL,
			'authServer',
			'apiUrl',
			NULL,
			NULL
		);
		Assert::exception(
			function () use ($userApi) {
				$userApi->completeAuthorization();
			},
			'SizeID\OAuth2\Exceptions\InvalidCSRFTokenException',
			'Invalid CSRF token.'
		);
	}
}

$test = new UserApiTest();
$test->run();

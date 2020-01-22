<?php

namespace SizeID\OAuth2;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Exceptions\InvalidCSRFTokenException;
use SizeID\OAuth2\Exceptions\InvalidStateException;
use SizeID\OAuth2\Exceptions\RedirectException;
use SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface;
use SizeID\OAuth2\Repositories\CsrfTokenRepositoryInterface;
use SizeID\OAuth2\Repositories\SessionAccessTokenRepository;
use SizeID\OAuth2\Repositories\SessionCsrfTokenRepository;

/**
 * Makes authenticated request to user section.
 * Uses authorization code grant according to {@link https://tools.ietf.org/html/rfc6749#section-4.1}.
 * @package SizeID\OAuth2
 */
class UserApi extends Api
{

	/**
	 * @var string
	 */
	private $redirectUri;

	/**
	 * @var CsrfTokenRepositoryInterface
	 */
	private $csrfTokenRepository;

	public function __construct(
		$clientId,
		$clientSecret,
		$redirectUri,
		AccessTokenRepositoryInterface $accessTokenRepository = NULL,
		$authorizationServerUrl = NULL,
		$apiBaseUrl = NULL,
		ClientInterface $httpClient = NULL,
		CsrfTokenRepositoryInterface $csrfTokenRepository = NULL
	)
	{
		if ($accessTokenRepository === NULL) {
			$accessTokenRepository = new SessionAccessTokenRepository('userToken');
		}
		parent::__construct(
			$clientId,
			$clientSecret,
			$accessTokenRepository,
			$authorizationServerUrl,
			$apiBaseUrl,
			$httpClient
		);
		$this->redirectUri = $redirectUri;
		if ($csrfTokenRepository === NULL) {
			$this->csrfTokenRepository = new SessionCsrfTokenRepository();
		} else {
			$this->csrfTokenRepository = $csrfTokenRepository;
		}
	}

	/**
	 * Url for redirection to authorization server. Internally used by UserApi::acquireNewAccessToken()
	 * @return UriInterface
	 */
	public function getAuthorizationUrl()
	{
		$url = new Uri($this->authorizationServerUrl);
		return Uri::withQueryValues($url, [
			'response_type' => 'code',
			'client_id' => $this->clientId,
			'redirect_uri' => $this->redirectUri,
			'state' => $this->csrfTokenRepository->generateCSRFToken(),
		]);
	}

	/**
	 * {@inheritdoc}
	 * @throws RedirectException - redirect is always needed
	 */
	public function acquireNewAccessToken()
	{
		throw RedirectException::create(
			$this->getAuthorizationUrl(),
			RedirectException::CODE_MISSING_TOKEN,
			'Access token is not acquired'
		);
	}

	/**
	 * Complete authorization process and acquire access token.
	 * @param string|null $code - variable code from query string
	 * @param string|null $state - variable state from query string
	 * @throws InvalidStateException - if CSRF token does not match original token
	 */
	public function completeAuthorization($code = NULL, $state = NULL)
	{
		if ($code === NULL && isset($_GET['code'])) {
			$code = $_GET['code'];
		}
		if ($state === NULL && isset($_GET['state'])) {
			$state = $_GET['state'];
		}
		if ($this->csrfTokenRepository->loadTokenCSRFToken() !== $state) {
			throw new InvalidCSRFTokenException("Invalid CSRF token.");
		}
		$response = $this->httpClient->post(
			$this->authorizationServerUrl . '/access-token',
			[
				'body' => [
					'grant_type' => 'authorization_code',
					'client_id' => $this->clientId,
					'client_secret' => $this->clientSecret,
					'redirect_uri' => $this->redirectUri,
					'code' => $code,
				],
			]
		);
		$this->saveTokenFromResponse($response);
	}

	/**
	 * {@inheritdoc}
	 * @throws RedirectException - if refresh token expires
	 */
	public function refreshAccessToken()
	{
		try {
			$refreshToken = $this->accessTokenRepository->getAccessToken()->getRefreshToken();
			$response = $this->httpClient->post(
				$this->authorizationServerUrl . '/access-token',
				[
					'body' => [
						'grant_type' => 'refresh_token',
						'refresh_token' => $refreshToken,
						'client_id' => $this->clientId,
						'client_secret' => $this->clientSecret,
						'redirect_uri' => $this->redirectUri,
					],
				]
			);
			$this->saveTokenFromResponse($response);
		} catch (ClientException $ex) {
			$response = $ex->getResponse();
			$sizeIdErrorCode = (int)$response->getHeaderLine(self::SIZEID_ERROR_CODE_HEADER);
			if ($response->getStatusCode() === 400 && $sizeIdErrorCode === 108) {
				//refresh token expired
				throw RedirectException::create(
					$this->getAuthorizationUrl(),
					RedirectException::CODE_EXPIRED_REFRESH_TOKEN,
					'Refresh token expired'
				);
			}
			throw $ex;
		}
	}

	/**
	 * @return AccessToken
	 * @throws InvalidStateException
	 */
	protected function getAccessToken()
	{
		$accessToken = parent::getAccessToken();
		if (!$accessToken->getRefreshToken()) {
			$this->accessTokenRepository->deleteAccessToken();
			throw new InvalidStateException("Refresh token value is missing");
		}
		return $accessToken;
	}

	/**
	 * @param ResponseInterface $response
	 */
	private function saveTokenFromResponse(ResponseInterface $response)
	{
		$jsonToken = $this->parseToken($response);
		$this->accessTokenRepository->saveAccessToken(
			new AccessToken(
				$jsonToken->access_token, $jsonToken->refresh_token
			)
		);
	}
}
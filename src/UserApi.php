<?php


namespace SizeID\OAuth2;


use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Exceptions\InvalidStateException;
use SizeID\OAuth2\Exceptions\RedirectException;
use SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface;
use SizeID\OAuth2\Repositories\CsrfTokenRepositoryInterface;
use SizeID\OAuth2\Repositories\SessionAccessTokenRepository;
use SizeID\OAuth2\Repositories\SessionCsrfTokenRepository;
use Tracy\Debugger;

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
		AccessTokenRepositoryInterface $accessTokenRepository = null,
		$authorizationServerUrl = null,
		$apiBaseUrl = null,
		$httpClient = null,
		$csrfTokenRepository = null
	)
	{
		if ($accessTokenRepository === null) {
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

		if ($csrfTokenRepository === null) {
			$this->csrfTokenRepository = new SessionCsrfTokenRepository();
		} else {
			$this->csrfTokenRepository = $csrfTokenRepository;
		}
	}

	public function acquireNewAccessToken()
	{
		throw RedirectException::create(
			$this->getAuthorizationUrl(),
			RedirectException::CODE_MISSING_TOKEN,
			'Access token is not acquired'
		);
	}

	public function completeAuthorization($code = null, $state = null)
	{
		if ($code === null && isset($_GET['code'])) {
			$code = $_GET['code'];
		}
		if ($state === null && isset($_GET['state'])) {
			$state = $_GET['state'];
		}
		if ($this->csrfTokenRepository->loadTokenCSRFToken() !== $state) {
			throw new InvalidStateException("Invalid CSRF token.");
		}
		$response = $this->httpClient->request(
			'POST',
			$this->authorizationServerUrl . '/access-token',
			[
				'form_params' => [
					'grant_type' => 'authorization_code',
					'client_id' => $this->clientId,
					'client_secret' => $this->clientSecret,
					'redirect_uri' => $this->redirectUri,
					'code' => $code,
				]
			]
		);
		$this->saveTokenFromResponse($response);
	}

	public function getAuthorizationUrl()
	{
		return $this->authorizationServerUrl . '/?' . http_build_query(
				[
					'response_type' => 'code',
					'client_id' => $this->clientId,
					'redirect_uri' => $this->redirectUri,
					'state' => $this->csrfTokenRepository->generateCSRFToken(),
				]
			);
	}

	public function refreshAccessToken()
	{
		try {
			$refreshToken = $this->accessTokenRepository->getAccessToken()->getRefreshToken();
			$response = $this->httpClient->request(
				'POST',
				$this->authorizationServerUrl . '/access-token',
				[
					'form_params' => [
						'grant_type' => 'refresh_token',
						'refresh_token' => $refreshToken,
						'client_id' => $this->clientId,
						'client_secret' => $this->clientSecret,
						'redirect_uri' => $this->redirectUri,
					]
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

	protected function getAccessToken()
	{
		$accessToken = parent::getAccessToken();
		if (!$accessToken->getRefreshToken()) {
			$this->accessTokenRepository->deleteAccessToken();
			throw new InvalidStateException("Refresh token value is missing");
		}
		return $accessToken;
	}

	private function saveTokenFromResponse(Response $response)
	{
		$jsonToken = $this->parseToken($response);
		$this->accessTokenRepository->saveAccessToken(
			new AccessToken(
				$jsonToken->access_token, $jsonToken->expires_in, $jsonToken->refresh_token
			)
		);
	}


}
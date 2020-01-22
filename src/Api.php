<?php

namespace SizeID\OAuth2;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Exceptions\InvalidStateException;
use SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface;

/**
 * Shared functionality of API calls
 * @package SizeID\OAuth2
 */
abstract class Api
{

	const SIZEID_ERROR_CODE_HEADER = 'SizeID-Error-Code';

	/**
	 * SizeID for Business client identifier
	 * @var string
	 */
	protected $clientId;

	/**
	 * SizeId for Business client secret
	 * @var string
	 */
	protected $clientSecret;

	/**
	 * @var AccessTokenRepositoryInterface
	 */
	protected $accessTokenRepository;

	/**
	 * @var UriInterface
	 */
	protected $authorizationServerUrl;

	/**
	 * @var UriInterface
	 */
	protected $apiBaseUrl;

	/**
	 * @var ClientInterface
	 */
	protected $httpClient;

	public function __construct(
		$clientId,
		$clientSecret,
		AccessTokenRepositoryInterface $accessTokenRepository,
		$authorizationServerUrl,
		$apiBaseUrl,
		$httpClient
	)
	{
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		$this->accessTokenRepository = $accessTokenRepository;
		if ($authorizationServerUrl === NULL) {
			$authorizationServerUrl = Config::AUTHORIZATION_SERVER_URL;
		}
		if ($apiBaseUrl === NULL) {
			$apiBaseUrl = Config::API_URL;
		}
		if ($httpClient === NULL) {
			$httpClient = new Client();
		}
		$this->authorizationServerUrl = $authorizationServerUrl;
		$this->apiBaseUrl = $apiBaseUrl;
		$this->httpClient = $httpClient;
	}

	/**
	 * Acquire access token a send authenticated request to SizeID Business API. If needed, refresh access token.
	 * Request URI should be relative for example `user/measures`.
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 * @throws InvalidStateException
	 */
	public function send(RequestInterface $request)
	{
		$hasToken = $this->hasAccessToken();
		if (!is_bool($hasToken)) {
			throw new InvalidStateException(
				"Method 'AccessTokenRepositoryInterface:hasAccessToken' should return boolean."
			);
		}
		if (!$this->hasAccessToken()) {
			$this->acquireNewAccessToken();
		}
		return $this->createResponse($request);
	}

	/**
	 * Acquire new access token. This method is called internally by Api::send(). Use to force token acquirement.
	 */
	public abstract function acquireNewAccessToken();

	/**
	 * Refresh existing access token. This method is called internally by  Api::send(). Use to force token renewal.
	 */
	public abstract function refreshAccessToken();

	/**
	 * @return bool
	 */
	protected function hasAccessToken()
	{
		return $this->accessTokenRepository->hasAccessToken();
	}

	/**
	 * @return AccessToken
	 * @throws InvalidStateException
	 */
	protected function getAccessToken()
	{
		$accessToken = $this->accessTokenRepository->getAccessToken();
		if (!$accessToken instanceof AccessToken) {
			$this->accessTokenRepository->deleteAccessToken();
			throw new InvalidStateException(
				"Method 'AccessTokenRepositoryInterface:getAccessToken' should return class 'AccessToken'."
			);
		}
		if (!$accessToken->getAccessToken()) {
			$this->accessTokenRepository->deleteAccessToken();
			throw new InvalidStateException("Missing AccessToken value.");
		}
		return $accessToken;
	}

	/**
	 * @param Response $response
	 * @return \stdClass
	 */
	protected function parseToken(ResponseInterface $response)
	{
		return json_decode($response->getBody()->getContents());
	}

	/**
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 * @throws ClientException
	 */
	private function createResponse(RequestInterface $request)
	{
		$response = $this->callApi($this->buildRequest($request));
		if ($response->getStatusCode() === 401 && $response->getHeaderLine(self::SIZEID_ERROR_CODE_HEADER) == 109) {
			$this->refreshAccessToken();
			return $this->callApi($this->buildRequest($request));
		}
		return $response;
	}

	/**
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 */
	private function callApi(RequestInterface $request)
	{
		return $this->httpClient->send($request);
	}

	/**
	 * @param RequestInterface $request
	 * @return RequestInterface
	 */
	private function buildRequest(RequestInterface $request)
	{
		return $request->withAddedHeader('Authorization', 'Bearer ' . $this->getAccessToken()->getAccessToken())
			->withUri(new Uri($this->apiBaseUrl . '/' . $request->getUri()));
	}
}
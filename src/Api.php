<?php


namespace SizeID\OAuth2;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Exceptions\InvalidStateException;
use SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface;

abstract class Api
{

	const SIZEID_ERROR_CODE_HEADER = 'SizeID-Error-Code';

	/**
	 * @var string
	 */
	protected $clientId;

	/**
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

		if ($authorizationServerUrl === null) {
			$authorizationServerUrl = Config::AUTHORIZATION_SERVER_URL;
		}
		if ($apiBaseUrl === null) {
			$apiBaseUrl = Config::API_URL;
		}
		if ($httpClient === null) {
			$httpClient = new Client();
		}
		$this->authorizationServerUrl = new Uri($authorizationServerUrl);
		$this->apiBaseUrl = new Uri($apiBaseUrl);
		$this->httpClient = $httpClient;
	}

	/**
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

	public abstract function acquireNewAccessToken();

	public abstract function refreshAccessToken();

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

	protected function hasAccessToken()
	{
		return $this->accessTokenRepository->hasAccessToken();
	}

	protected function parseToken(Response $response)
	{
		return \GuzzleHttp\json_decode($response->getBody()->getContents());
	}

	/**
	 * @param Request $request
	 * @return ResponseInterface
	 */
	private function createResponse(RequestInterface $request)
	{
		try {
			return $this->callApi($this->buildRequest($request));
		} catch (ClientException $ex) {
			if ($ex->getResponse()->getStatusCode() === 401) {
				$response = $ex->getResponse();
				//access is token expired
				if ($response->getHeaderLine(self::SIZEID_ERROR_CODE_HEADER) == 109) {
					$this->refreshAccessToken();
					return $this->callApi($this->buildRequest($request));
				}
			}
			throw $ex;
		}
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
	 * @param ApiRequest $apiRequest
	 * @return RequestInterface
	 */
	private function buildRequest(RequestInterface $request)
	{
		$request = $request->withHeader('Authorization', 'Bearer ' . $this->getAccessToken()->getAccessToken());
		$requestUri = $request->getUri();
		$baseUri = $this->apiBaseUrl;
		$combinedUri = $baseUri
			->withPath($this->apiBaseUrl->getPath() . '/' . $requestUri->getPath())
			->withQuery($requestUri->getQuery())
			->withFragment($requestUri->getFragment());
		return $request->withUri($combinedUri);
	}


}
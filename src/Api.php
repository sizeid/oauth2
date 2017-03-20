<?php


namespace SizeID\OAuth2;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
	 * @var string
	 */
	protected $authorizationServerUrl;

	/**
	 * @var string
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
		$this->authorizationServerUrl = $authorizationServerUrl ? $authorizationServerUrl : Config::AUTHORIZATION_SERVER_URL;
		$this->apiBaseUrl = $apiBaseUrl ? $apiBaseUrl : Config::API_URL;

		if ($httpClient === null) {
			$this->httpClient = new Client();
		}
		else{
			$this->httpClient = $httpClient;
		}
	}


	/**
	 * @param $endpoint
	 * @param string $method
	 * @param array $headers
	 * @param null $body
	 * @return ResponseInterface
	 */
	public function request($endpoint, $method = ApiRequest::GET, $headers = [], $body = null)
	{
		return $this->send(new ApiRequest($endpoint, $method, $headers, $body));
	}

	/**
	 * @param ApiRequest $apiRequest
	 * @return ResponseInterface
	 */
	public function send(ApiRequest $apiRequest)
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
		return $this->createResponse($apiRequest);
	}

	public abstract function acquireNewAccessToken();

	public abstract function refreshAccessToken();

	protected function hasAccessToken()
	{
		return $this->accessTokenRepository->hasAccessToken();
	}

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

	protected function parseToken(Response $response)
	{
		return \GuzzleHttp\json_decode($response->getBody()->getContents());
	}

	/**
	 * @param Request $request
	 * @return ResponseInterface
	 */
	private function createResponse(ApiRequest $apiRequest)
	{
		try {
			return $this->callApi($this->createRequest($apiRequest));
		} catch (ClientException $ex) {
			if ($ex->getResponse()->getStatusCode() === 401) {
				$response = $ex->getResponse();
				//access is token expired
				if ($response->getHeaderLine(self::SIZEID_ERROR_CODE_HEADER) == 109) {
					$this->refreshAccessToken();
					return $this->callApi($this->createRequest($apiRequest));
				}
			}
			throw $ex;
		}
	}

	/**
	 * @param Request $request
	 * @return ResponseInterface
	 */
	private function callApi(Request $request)
	{
		return $this->httpClient->send($request);
	}

	/**
	 * @param ApiRequest $apiRequest
	 * @return Request
	 */
	private function createRequest(ApiRequest $apiRequest)
	{
		$this->addAuthorizationHeader($apiRequest);
		if ($apiRequest->hasBody()) {
			$apiRequest->setHeader('Content-Type', 'application/json');
		}
		return new Request(
			$apiRequest->getMethod(),
			$this->apiBaseUrl . '/' . $apiRequest->getEndpoint(),
			$apiRequest->getHeaders(),
			$apiRequest->getBody()
		);
	}

	private function addAuthorizationHeader(ApiRequest $apiRequest)
	{
		$apiRequest->setHeader('Authorization', 'Bearer ' . $this->getAccessToken()->getAccessToken());
		return $this;
	}


}
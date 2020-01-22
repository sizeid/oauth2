<?php

namespace SizeID\OAuth2;

use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Entities\ClientAccessToken;
use SizeID\OAuth2\Entities\ClientInterface;
use SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface;
use SizeID\OAuth2\Repositories\SessionAccessTokenRepository;

/**
 * Makes authenticated request to client section.
 * Uses client credentials grant according to {@link https://tools.ietf.org/html/rfc6749#section-4.4}.
 * @package SizeID\OAuth2
 */
class ClientApi extends Api
{

	public function __construct(
		$clientId,
		$clientSecret,
		AccessTokenRepositoryInterface $accessTokenRepository = NULL,
		$authorizationServerUrl = NULL,
		$apiBaseUrl = NULL,
		$httpClient = NULL
	)
	{
		if ($accessTokenRepository === NULL) {
			$accessTokenRepository = new SessionAccessTokenRepository('clientToken');
		}
		parent::__construct(
			$clientId,
			$clientSecret,
			$accessTokenRepository,
			$authorizationServerUrl,
			$apiBaseUrl,
			$httpClient
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function acquireNewAccessToken()
	{
		$response = $this->httpClient->post(
			$this->authorizationServerUrl . '/access-token',
			[
				'form_params' => [
					'grant_type' => 'client_credentials',
					'client_id' => $this->clientId,
					'client_secret' => $this->clientSecret,
				],
			]
		);
		$jsonToken = $this->parseToken($response);
		$clientAccessToken = new AccessToken($jsonToken->access_token);
		$this->accessTokenRepository->saveAccessToken($clientAccessToken);
	}

	/**
	 * {@inheritdoc}
	 */
	public function refreshAccessToken()
	{
		$this->acquireNewAccessToken();
	}
}
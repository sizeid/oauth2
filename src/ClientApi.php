<?php


namespace SizeID\OAuth2;


use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Entities\ClientAccessToken;
use SizeID\OAuth2\Entities\ClientInterface;
use SizeID\OAuth2\Repositories\AccessTokenRepositoryInterface;
use SizeID\OAuth2\Repositories\SessionAccessTokenRepository;

class ClientApi extends Api
{

	public function __construct(
		$clientId,
		$clientSecret,
		AccessTokenRepositoryInterface $accessTokenRepository = null,
		$authorizationServerUrl = null,
		$apiBaseUrl = null,
		$httpClient = null
	)
	{
		if ($accessTokenRepository === null) {
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

	public function acquireNewAccessToken()
	{
		$response = $this->httpClient->request(
			'POST',
			$this->authorizationServerUrl . '/access-token',
			[
				'form_params' => [
					'grant_type' => 'client_credentials',
					'client_id' => $this->clientId,
					'client_secret' => $this->clientSecret,
				]
			]
		);
		$jsonToken = $this->parseToken($response);
		$clientAccessToken = new AccessToken($jsonToken->access_token, $jsonToken->expires_in);
		$this->accessTokenRepository->saveAccessToken($clientAccessToken);
	}

	public function refreshAccessToken()
	{
		$this->acquireNewAccessToken();
	}


}
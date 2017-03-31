<?php


namespace SizeID\OAuth2\Entities;


/**
 * Wrapper for access token according to {@link https://tools.ietf.org/html/rfc6749#section-4.1.4}
 * @package SizeID\OAuth2\Entities
 */
class AccessToken
{

	/**
	 * @var string
	 */
	private $accessToken;

	/**
	 * @var string
	 */
	private $refreshToken;

	public function __construct($accessToken, $refreshToken = null)
	{
		$this->accessToken = $accessToken;
		$this->refreshToken = $refreshToken;
	}

	public function getAccessToken()
	{
		return $this->accessToken;
	}

	public function getRefreshToken()
	{
		return $this->refreshToken;
	}


}
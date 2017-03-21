<?php


namespace SizeID\OAuth2\Entities;

use DateTime;


/**
 * Wrapper for access token according to {@link https://tools.ietf.org/html/rfc6749#section-4.1.4}
 * @package SizeID\OAuth2\Entities
 */
class AccessToken
{

	/**
	 * @var \DateTime
	 */
	private $validTo;

	private $accessToken;

	private $refreshToken;

	public function __construct($accessToken, $expiresIn, $refreshToken = null)
	{
		$validTo = new DateTime();
		$this->validTo = $validTo->modify("+ $expiresIn seconds");
		$this->accessToken = $accessToken;
		$this->refreshToken = $refreshToken;
	}

	public function getValidTo()
	{
		return $this->validTo;
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
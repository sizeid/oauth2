<?php


namespace SizeID\OAuth2\Entities;

use DateTime;


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
		$validTo = new \DateTime();
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
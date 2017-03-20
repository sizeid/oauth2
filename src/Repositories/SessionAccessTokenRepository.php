<?php

namespace SizeID\OAuth2\Repositories;


use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Entities\AccessTokenInterface;
use SizeID\OAuth2\Entities\ClientAccessToken;

class SessionAccessTokenRepository implements AccessTokenRepositoryInterface
{


	/**
	 * @var string
	 */
	private $namespace;

	public function __construct($namespace)
	{
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		$this->namespace = $namespace;
	}

	public function saveAccessToken(AccessToken $accessToken)
	{
		$_SESSION[$this->namespace] = $accessToken;
	}

	public function hasAccessToken()
	{
		return isset($_SESSION[$this->namespace]);
	}

	public function deleteAccessToken()
	{
		unset($_SESSION[$this->namespace]);
	}

	/**
	 * @return AccessToken
	 */
	public function getAccessToken()
	{
		return $_SESSION[$this->namespace];
	}


}
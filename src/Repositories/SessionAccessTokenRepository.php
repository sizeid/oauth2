<?php

namespace SizeID\OAuth2\Repositories;

use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Entities\AccessTokenInterface;
use SizeID\OAuth2\Entities\ClientAccessToken;

/**
 * AccessTokenRepositoryInterface implementation - uses session to store tokens.
 * @package SizeID\OAuth2\Repositories
 */
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
		$_SESSION[$this->namespace] = $this->serialize($accessToken);
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
		return $this->unserialize($_SESSION[$this->namespace]);
	}

	private function serialize(AccessToken $accessToken)
	{
		return serialize(
			[
				$accessToken->getAccessToken(),
				$accessToken->getRefreshToken(),
			]
		);
	}

	/**
	 * @param $serializedToken
	 * @return AccessToken
	 */
	private function unserialize($serializedToken)
	{
		list($accessToken, $refreshToken) = unserialize($serializedToken);
		return new AccessToken($accessToken, $refreshToken);
	}
}
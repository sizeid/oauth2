<?php

namespace SizeID\OAuth2\Repositories;


use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Entities\AccessTokenInterface;
use SizeID\OAuth2\Entities\ClientAccessToken;

/**
 * Interface AccessTokenRepositoryInterface
 * @package SizeID\OAuth2\Repositories
 */
interface AccessTokenRepositoryInterface
{

	/**
	 * Saves AccessToken to repository.
	 * @param AccessToken $clientAccessToken
	 */
	public function saveAccessToken(AccessToken $clientAccessToken);

	/**
	 * Returns AccessToken from repository. Called after getAccessToken returns true.
	 * @return AccessToken
	 */
	public function getAccessToken();

	/**
	 * Repository has AccessToken - determinate initial token retrieval.
	 * @return boolean
	 */
	public function hasAccessToken();

	/**
	 * Remove AccessToken - for invalid token disposal.
	 */
	public function deleteAccessToken();


}
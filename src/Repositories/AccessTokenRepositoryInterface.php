<?php

namespace SizeID\OAuth2\Repositories;


use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Entities\AccessTokenInterface;
use SizeID\OAuth2\Entities\ClientAccessToken;

interface AccessTokenRepositoryInterface
{

	public function saveAccessToken(AccessToken $clientAccessToken);

	/**
	 * @return AccessToken
	 */
	public function getAccessToken();

	public function hasAccessToken();

	public function deleteAccessToken();


}
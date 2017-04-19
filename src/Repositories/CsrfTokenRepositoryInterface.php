<?php

namespace SizeID\OAuth2\Repositories;

/**
 * Interface CsrfTokenRepositoryInterface
 * @package SizeID\OAuth2\Repositories
 */
interface CsrfTokenRepositoryInterface
{

	/**
	 * Generates saves and returns CSRF token.
	 * @return string - CSRF token
	 */
	public function generateCSRFToken();

	/**
	 * Returns token from previous CsrfTokenRepositoryInterface::generateCSRFToken() function call.
	 * @return string - CSRF toknen
	 */
	public function loadTokenCSRFToken();
}
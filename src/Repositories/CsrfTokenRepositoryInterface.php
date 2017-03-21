<?php


namespace SizeID\OAuth2\Repositories;


/**
 * Interface CsrfTokenRepositoryInterface
 * @package SizeID\OAuth2\Repositories
 */
interface CsrfTokenRepositoryInterface
{
	/**
	 * Generate save and return CSRF token
	 * @return string CSRF token
	 */
	public function generateCSRFToken();

	/**
	 * Return token from previous generateCSRFToken function
	 * @return string CSRF tokne
	 */
	public function loadTokenCSRFToken();
}
<?php


namespace SizeID\OAuth2\Repositories;


interface CsrfTokenRepositoryInterface
{
	public function generateCSRFToken();

	public function loadTokenCSRFToken();
}
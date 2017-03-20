<?php


namespace SizeID\OAuth2\Repositories;


class SessionCsrfTokenRepository implements CsrfTokenRepositoryInterface
{

	private $key;

	public function __construct($key = 'sizeidOauht2state')
	{
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		$this->key = $key;
	}

	public function generateCSRFToken()
	{
		return $_SESSION[$this->key] = base64_encode(openssl_random_pseudo_bytes(8));
	}

	public function loadTokenCSRFToken()
	{
		if (isset($_SESSION[$this->key])) {
			return $_SESSION[$this->key];
		}
	}
}
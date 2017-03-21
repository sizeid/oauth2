<?php


namespace SizeID\OAuth2\Exceptions;


/**
 * Redirect to authorization server is required.
 * @package SizeID\OAuth2\Exceptions
 */
class RedirectException extends \Exception
{
	const CODE_MISSING_TOKEN = 1;
	const CODE_EXPIRED_REFRESH_TOKEN = 2;

	private $redirectUrl;

	public function getRedirectUrl()
	{
		return $this->redirectUrl;
	}


	public static function create($redirectUrl, $code, $message)
	{
		$e = new static("{$message}: Redirect to '$redirectUrl' for token acquirement.", $code);
		$e->redirectUrl = $redirectUrl;
		return $e;
	}

}
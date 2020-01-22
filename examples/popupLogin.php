<?php
require __DIR__ . '/bootstrap.php';
use GuzzleHttp\Psr7\Request;
use Latte\Engine;
use SizeID\OAuth2\Exceptions\RedirectException;
use SizeID\OAuth2\Repositories\SessionAccessTokenRepository;
use SizeID\OAuth2\Repositories\SessionCsrfTokenRepository;
use SizeID\OAuth2\UserApi;

$redirectUri = getCurrentUrlWithoutParameters();
// For full list of parameters see UserApi::__construct().
// All parameters can be replaced with custom value or implementation.
// redirectUri - url for token retrieval, in this case this script url - replace with custom url
// don't forget to add (whitelist) redirect uri to SizeID for Business account https://business.sizeid.com/integration.settings/#redirect_uri
$userApi = new UserApi(
	CLIENT_ID,        //clientId from config.php
	CLIENT_SECRET,    //clientSecret from config.php
	$redirectUri,
	// customize session namespace for this example
	new SessionAccessTokenRepository('sizeidPopupLoginAccess'),
	NULL, // use default values
	NULL,
	NULL,
	// customize session namespace for this example
	new SessionCsrfTokenRepository('sizeidPopupLoginCsrf')
);
if (isset($_GET['code'])) {
	// finish authorization process - receive authorization code and call for access token
	// code and state default from $_GET['code'] $_GET['state']
	$userApi->completeAuthorization();
	// close popup window and reload parent window
	echo '<script>window.opener.location.reload(false); window.close();</script>';
	die;
}
try {
	// create request to endpoint 'user' using 'get' method
	$request = new Request('get', 'user');
	// send request
	$response = $userApi->send($request);
	// receive user data
	$user = json_decode($response->getBody()->getContents());
	// render username to template popupLogin.latte
	renderTemplate(['user' => $user]);
} catch (RedirectException $ex) {
	// not logged in - show login button
	renderTemplate(['loginUrl' => $ex->getRedirectUrl()]);
}
function renderTemplate($parameters)
{
	$latte = new Engine();
	$latte->render(__DIR__ . '/popupLogin.latte', $parameters);
	die;
}
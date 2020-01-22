<?php
require __DIR__ . '/bootstrap.php';
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use SizeID\OAuth2\Exceptions\RedirectException;
use SizeID\OAuth2\UserApi;

$redirectUri = getCurrentUrlWithoutParameters();
// For full list of parameters see UserApi::__construct().
// All parameters can be replaced with custom value or implementation.
// redirectUri - url for token retrieval, in this case this script url - replace with custom url
// don't forget to add (whitelist) redirect uri to SizeID for Business account https://business.sizeid.com/integration.settings/#redirect_uri
$userApi = new UserApi(
	CLIENT_ID,        //clientId from config.php
	CLIENT_SECRET,    //clientSecret from config.php
	$redirectUri
);
if (isset($_GET['code'])) {
	// finish authorization process - receive authorization code and call for access token
	// code and state default from $_GET['code'] $_GET['state']
	$userApi->completeAuthorization();
	//redirect to this script url
	redirect($redirectUri);
}
try {
	// create example get request
	$request = createExampleGetRequest();
	// or create example put request - uncomment next line and comment line above
	// $request = createExamplePutRequest();
	// send request
	// if needed, acquire access token using authorization code method
	$response = $userApi->send($request);
	// get response body
	$rawBody = $response->getBody()->getContents();
} catch (RedirectException $ex) {
	// no access token with refresh token found - new authorization process is required
	// or
	// refresh token expired - new authorization process is also required
	redirect($ex->getRedirectUrl());
} catch (BadResponseException $ex) {
	//something went wrong - http response code is not 2xx
	$rawBody = $ex->getResponse()->getBody()->getContents();
}
// dump result
dump(json_decode($rawBody, TRUE));
/**
 * create request to endpoint 'user' using 'get' method
 * @return Request
 */
function createExampleGetRequest()
{
	return new Request('get', 'user');
}

/**
 * Create request to endpoint 'user/measures' using 'put' method and JSON attached to the request's body.
 * this call will change user's bodyHeight to 200.
 * @return Request
 */
function createExamplePutRequest()
{
	return new Request(
		'put',
		'user/measures',
		['Content-Type' => 'application/json'],
		'[{"id": "bodyHeight", "value": 200}]'
	);
}


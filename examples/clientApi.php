<?php

require __DIR__ . '/bootstrap.php';

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use SizeID\OAuth2\ClientApi;


// Initialize communication object.
// For full list of parameters see UserApi::__construct().
// All parameters could be replaced with custom implementation.
$clientApi = new ClientApi(
	CLIENT_ID,
	CLIENT_SECRET
);

try {
	//create request to 'client' endpoint with 'get' method
	//if needed acquire access token with client credentials method
	$request = new Request('get', 'client');
	//send request
	$response = $clientApi->send($request);
	//get response body
	$rawBody = $response->getBody()->getContents();
} catch (BadResponseException $ex) {
	//something went wrong - http response code is not 2xx
	$rawBody = $ex->getResponse()->getBody()->getContents();
}

//dump request content
dump(json_decode($rawBody, true));



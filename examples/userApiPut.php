<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use SizeID\OAuth2\Exceptions\RedirectException;
use SizeID\OAuth2\UserApi;

$redirectUri = getCurrentUrlWithoutParameters();

$clientApi = new UserApi(
	CLIENT_ID,
	CLIENT_SECRET,
	$redirectUri //url for token retrieval,
);

if (isset($_GET['code'])) {
	$clientApi->completeAuthorization();
	redirect($redirectUri);
}

try {
	$request = new Request(
		'put',
		'user/measures',
		['Content-Type' => 'application/json'],
		'[{"id": "bodyHeight", "value": 200}]'
	);
	$rawBody = $clientApi->send($request)->getBody()->getContents();
} catch (RedirectException $ex) {
	bar($redirectUri);
	redirect($ex->getRedirectUrl());
} catch (BadResponseException $ex) {
	bar($ex);
	$rawBody = $ex->getResponse()->getBody()->getContents();
}

dump(json_decode($rawBody, true));



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
	$redirectUri //url for token retrieval
);

if (isset($_GET['code'])) {
	$clientApi->completeAuthorization();
	redirect($redirectUri);
}
try {
	$rawBody = $clientApi->send(new Request('get', 'user'))->getBody()->getContents();
} catch (RedirectException $ex) {
	redirect($ex->getRedirectUrl());
} catch (BadResponseException $ex) {
	$rawBody = $ex->getResponse()->getBody()->getContents();
}

dump(json_decode($rawBody, true));



<?php

require __DIR__ . '/bootstrap.php';

use GuzzleHttp\Exception\BadResponseException;
use SizeID\OAuth2\ClientApi;
use GuzzleHttp\Psr7\Request;

$clientApi = new ClientApi(
	CLIENT_ID,
	CLIENT_SECRET
);

try {
	$rawBody = $clientApi->send(new Request('get', 'client'))->getBody()->getContents();
} catch (BadResponseException $ex) {
	$rawBody = $ex->getResponse()->getBody()->getContents();
}

dump(json_decode($rawBody, true));



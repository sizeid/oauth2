<?php

require __DIR__ . '/bootstrap.php';

use GuzzleHttp\Exception\BadResponseException;
use SizeID\OAuth2\ClientApi;

$clientApi = new ClientApi(
	'{clientId}',
	'{clientSecret}'
);

try {
	$rawBody = $clientApi->request('client')->getBody()->getContents();
} catch (BadResponseException $ex) {
	$rawBody = $ex->getResponse()->getBody()->getContents();
}

dump(json_decode($rawBody, true));



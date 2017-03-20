<?php

namespace SizeID\OAuth2\Tests;

use SizeID\OAuth2\ApiRequest;
use SizeID\OAuth2\Config;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class ApiRequestTest extends TestCase
{

	public function testConstants()
	{
		$apiRequest = new ApiRequest('endpoint');

		$apiRequest
			->setHeaders(['one' => 'two'])
			->setBody('{}')
			->setMethod('PUT');

		Assert::type(ApiRequest::class, $apiRequest);

	}

}

$test = new ApiRequestTest();
$test->run();

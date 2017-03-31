<?php

namespace SizeID\OAuth2\Tests;

use SizeID\OAuth2\Entities\AccessToken;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class AccessTokenTest extends TestCase
{
	public function testConstants()
	{
		$accessToken = new AccessToken('accessToken', 'refreshToken');
		Assert::type(AccessToken::class, $accessToken);
	}

}

$test = new AccessTokenTest();
$test->run();

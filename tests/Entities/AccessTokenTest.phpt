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
		$accessToken = new AccessToken('accessToken', 60, 'refreshToken');
		Assert::type(\DateTime::class, $accessToken->getValidTo());
	}

}

$test = new AccessTokenTest();
$test->run();

<?php

namespace SizeID\OAuth2\Tests\Repositories;

use SizeID\OAuth2\Entities\AccessToken;
use SizeID\OAuth2\Repositories\SessionAccessTokenRepository;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class SessionAccessTokenRepositoryTest extends TestCase
{

	public function testRepository()
	{
		$repository = new SessionAccessTokenRepository('n');
		$token = new AccessToken('ac');
		$repository->saveAccessToken($token);
		Assert::true($repository->hasAccessToken());
		Assert::equal('ac', $repository->getAccessToken()->getAccessToken());
		$repository->deleteAccessToken();
		Assert::false(isset($_SESSION['n']));
	}
}

$test = new SessionAccessTokenRepositoryTest();
$test->run();

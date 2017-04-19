<?php

namespace SizeID\OAuth2\Tests\Repositories;

use SizeID\OAuth2\Repositories\SessionCsrfTokenRepository;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class SessionCsrfRepositoryTest extends TestCase
{

	public function testRepository()
	{
		$repository = new SessionCsrfTokenRepository('n');
		Assert::null($repository->loadTokenCSRFToken());
		$generatedToken = $repository->generateCSRFToken();
		Assert::equal($generatedToken, $repository->loadTokenCSRFToken());
	}
}

$test = new SessionCsrfRepositoryTest();
$test->run();

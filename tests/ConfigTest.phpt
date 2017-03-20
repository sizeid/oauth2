<?php

namespace SizeID\OAuth2\Tests;

use SizeID\OAuth2\Config;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class ConfigTest extends TestCase
{

	public function testConstants()
	{
		Config::AUTHORIZATION_SERVER_URL;
		Config::TOKEN_PATH;
		Config::TOKEN_PATH;
		Assert::true(true);
	}

}

$test = new ConfigTest();
$test->run();

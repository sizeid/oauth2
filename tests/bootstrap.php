<?php

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();

//autoload all classes
$loader = new \Nette\Loaders\RobotLoader();
$loader
	->addDirectory(__DIR__ . '/../src')
	->setTempDirectory(__DIR__ . '/temp')
	->register();




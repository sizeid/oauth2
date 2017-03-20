<?php

require __DIR__ . '/../vendor/autoload.php';
//enable debugger
\Tracy\Debugger::enable();
\Tracy\Debugger::$maxDepth = 10;
//autoload all classes
$loader = new \Nette\Loaders\RobotLoader();
$loader
	->addDirectory(__DIR__ . '/../src')
	->setTempDirectory(__DIR__ . '/temp')
	->register();

function bar($var, $title = NULL, array $options = NULL)
{
	\Tracy\Debugger::barDump($var, $title, $options);
}

function getCurrentUrlWithoutParameters()
{
	$uriParts = explode('?', $_SERVER['REQUEST_URI'], 2);
	return 'http://' . $_SERVER['HTTP_HOST'] . $uriParts[0];
}

function redirect($location)
{
	header('Location: ' . $location);
	die;

}
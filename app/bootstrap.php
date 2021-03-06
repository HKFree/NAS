<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

$configurator->setDebugMode(FALSE); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log', 'bkralik@hkfree.org');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

//Instante\Bootstrap3Renderer\DI\RendererExtension::register($configurator);

$container = $configurator->createContainer();

return $container;

<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$_SERVER['KERNEL_CLASS'] = "Shopware\Core\Kernel";
$_ENV['APP_ENV'] = "test";
$_ENV['APP_DEBUG'] = true;
$_ENV['SYMFONY_DEPRECATIONS_HELPER'] = 'weak';
putenv('TEST_TOKEN=none'); #cannot use $_ENV here because getenv returns an empty string

$projectDir = realpath(__DIR__ . '/../../../../../');
$pluginDir = realpath($projectDir . '/custom/plugins/MolliePayments');

(new TestBootstrapper())
    ->setProjectDir($projectDir)
    ->addCallingPlugin($pluginDir . '/composer.json')
    ->bootstrap();


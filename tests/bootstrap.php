<?php

define('UNIT_TESTING', true);
define('ECOMMERCE_DEALS_BASE_PATH', dirname(__DIR__));
define('BASE_PATH', ECOMMERCE_DEALS_BASE_PATH);

if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
    echo 'You must first install the vendors using composer.' . PHP_EOL;
    exit(1);
}

$loader = require BASE_PATH . '/vendor/autoload.php';

use Symfony\Component\ClassLoader\ClassMapGenerator;

$loader->addClassMap(ClassMapGenerator::createMap(BASE_PATH . '/sapphire'));
$loader->add('Heystack\Deals\Test', __DIR__);

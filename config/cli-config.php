<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;

require_once 'bootstrap.php';

$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."../src"));
$entityManager = EntityManager::create(['url' => $_ENV['DATABASE_URL']], $config);

return ConsoleRunner::createHelperSet($entityManager);

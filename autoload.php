<?php
require_once __DIR__.'/vendor/symfony-classloader/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();

$loader->registerNamespace('Symfony\\Component\\Console', __DIR__.'/vendor/symfony-console');
$loader->registerNamespace('Symfony\\Component\\ClassLoader', __DIR__.'/vendor/symfony-classloader');
$loader->registerNamespace('YProx', __DIR__.'/lib');
$loader->register();


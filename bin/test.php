#!/usr/bin/env php
<?php

require_once(__DIR__.'/../autoload.php');

$application = new YProx\LiveTest\Cli\Application();
$application->addCommands(array(
    new YProx\LiveTest\Cli\Command\ScreenshotCommand,
    new YProx\LiveTest\Cli\Command\ResponseTimeCommand,
));
$application->run();


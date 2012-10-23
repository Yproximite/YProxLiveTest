#!/usr/bin/env php
<?php

require_once(__DIR__.'/../autoload.php');

set_error_handler(
    create_function(
        '$severity, $message, $file, $line',
        'throw new ErrorException($message, $severity, $severity, $file, $line);'
    )
);


$application = new YProx\LiveTest\Cli\Application();
$application->addCommands(array(
    new YProx\LiveTest\Cli\Command\ScreenshotCommand,
    new YProx\LiveTest\Cli\Command\ResponseTimeCommand,
    new YProx\LiveTest\Cli\Command\GenerateSiteLinkFileCommand,
    new YProx\LiveTest\Cli\Command\SiteDiffCommand,
    new YProx\LiveTest\Cli\Command\SiteRTCompCommand,
));
$application->run();


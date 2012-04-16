<?php

namespace YProx\LiveTest\Cli;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('YProx Live Test Suite', 'v1.0');
    }
}


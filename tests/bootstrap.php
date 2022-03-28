<?php

define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../../../lib/base.php';
require_once __DIR__.'/../vendor/autoload.php';

\OC::$composerAutoloader->addPsr4('Tests\\', OC::$SERVERROOT . '/tests/unit/', true);

\OC_App::loadApp('circles');

OC_Hook::clear();

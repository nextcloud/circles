<?php


if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}

require_once __DIR__ . '/../../../lib/base.php';

// Fix for "Autoload path not allowed: .../tests/lib/testcase.php"
\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');

// Fix for "Autoload path not allowed: .../activity/tests/testcase.php"
\OC_App::loadApp('circles');

// Fix for "Autoload path not allowed: .../files/lib/activity.php"
//\OC_App::loadApp('files');

// Fix for "Autoload path not allowed: .../files_sharing/lib/activity.php"
//\OC_App::loadApp('files_sharing');


if (!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../../../lib/base.php';
require_once __DIR__.'/../vendor/autoload.php';

\OC::$composerAutoloader->addPsr4('Tests\\', OC::$SERVERROOT . '/tests/unit/', true);

\OC_App::loadApp('circles');

OC_Hook::clear();

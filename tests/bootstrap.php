<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCP\App\IAppManager;
use OCP\Server;

if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}

$basePhpCandidates = [];
if (($nextcloudRoot = getenv('NEXTCLOUD_ROOT')) !== false && $nextcloudRoot !== '') {
	$basePhpCandidates[] = rtrim($nextcloudRoot, '/') . '/lib/base.php';
}
$basePhpCandidates = array_merge($basePhpCandidates, [
	// Standard layout: server/apps/circles/tests
	__DIR__ . '/../../../lib/base.php',
	// Sibling layout: nextcloud/circles + nextcloud/server
	__DIR__ . '/../../server/lib/base.php',
]);

$basePhp = null;
foreach ($basePhpCandidates as $candidate) {
	if (is_file($candidate)) {
		$basePhp = $candidate;
		break;
	}
}

if ($basePhp === null) {
	throw new RuntimeException(
		'Could not find Nextcloud lib/base.php. Expected server under apps/circles or as sibling nextcloud/server.'
	);
}

require_once $basePhp;
require_once __DIR__ . '/../vendor/autoload.php';

$testsAutoloadCandidates = [];
if (($nextcloudRoot = getenv('NEXTCLOUD_ROOT')) !== false && $nextcloudRoot !== '') {
	$testsAutoloadCandidates[] = rtrim($nextcloudRoot, '/') . '/tests/autoload.php';
}
$testsAutoloadCandidates = array_merge($testsAutoloadCandidates, [
	__DIR__ . '/../../../tests/autoload.php',
	__DIR__ . '/../../server/tests/autoload.php',
]);
foreach ($testsAutoloadCandidates as $candidate) {
	if (is_file($candidate)) {
		require_once $candidate;
		break;
	}
}

Server::get(IAppManager::class)->loadApp('circles');

OC_Hook::clear();

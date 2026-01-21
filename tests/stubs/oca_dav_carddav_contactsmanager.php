<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\ConfigLexicon;
use OCA\DAV\Db\PropertyMapper;
use OCP\Contacts\IManager;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class ContactsManager {
	/**
	 * ContactsManager constructor.
	 *
	 * @param CardDavBackend $backend
	 * @param IL10N $l10n
	 */
	public function __construct(
		private CardDavBackend $backend,
		private IL10N $l10n,
		private PropertyMapper $propertyMapper,
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param IManager $cm
	 * @param string $userId
	 * @param IURLGenerator $urlGenerator
	 */
	public function setupContactsProvider(IManager $cm, $userId, IURLGenerator $urlGenerator)
 {
 }

	/**
	 * @param IManager $cm
	 * @param ?string $userId
	 * @param IURLGenerator $urlGenerator
	 */
	public function setupSystemContactsProvider(IManager $cm, ?string $userId, IURLGenerator $urlGenerator)
 {
 }
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Settings;

use OCA\Circles\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {
	public function __construct(
		private readonly IL10N $l,
		private readonly IURLGenerator $url,
	) {
	}

	#[\Override]
	public function getID(): string {
		return 'teams';
	}

	#[\Override]
	public function getName(): string {
		return $this->l->t('Teams');
	}

	#[\Override]
	public function getPriority(): int {
		return 85;
	}

	#[\Override]
	public function getIcon(): string {
		return $this->url->imagePath(Application::APP_ID, 'circles.svg');
	}
}

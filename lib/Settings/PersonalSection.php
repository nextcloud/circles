<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection {
	public function __construct(
		protected IURLGenerator $url,
		protected IL10N $l,
	) {
	}

	#[\Override]
	public function getID(): string {
		return 'circles';
	}

	#[\Override]
	public function getName(): string {
		return $this->l->t('Teams');
	}

	#[\Override]
	public function getPriority(): int {
		return 80;
	}

	#[\Override]
	public function getIcon(): string {
		return $this->url->imagePath('core', 'apps/circles.svg');
	}
}

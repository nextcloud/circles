<?php

declare(strict_types=1);

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2023
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Circles\Activity;

use OCA\Circles\AppInfo\Application;
use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class Filter implements IFilter {
	public function __construct(
		protected IL10N $l10n,
		protected IURLGenerator $url
	) {
	}

	/**
	 * @return string Lowercase a-z only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier() {
		return Application::APP_ID;
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->l10n->t('Circles');
	}

	/**
	 * @return int
	 * @since 11.0.0
	 */
	public function getPriority() {
		return 10;
	}

	/**
	 * @return string Full URL to an icon, empty string when none is given
	 * @since 11.0.0
	 */
	public function getIcon() {
		return $this->url->getAbsoluteURL(
			$this->url->imagePath(Application::APP_ID, 'circles.svg')
		);
	}

	/**
	 * @param string[] $types
	 *
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function filterTypes(array $types) {
		return $types;
	}

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function allowedApps() {
		return [Application::APP_ID];
	}
}

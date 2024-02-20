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

use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\Circle;
use OCP\Activity\IEvent;

class ProviderSubjectCircle extends ProviderParser {
	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 *
	 * @throws FakeException
	 */
	public function parseSubjectCircleCreate(IEvent $event, Circle $circle): void {
		if ($event->getSubject() !== 'circle_create') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle,
			$this->l10n->t('You created the circle {circle}'),
			$this->l10n->t('{author} created the circle {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 *
	 * @throws FakeException
	 */
	public function parseSubjectCircleDelete(IEvent $event, Circle $circle): void {
		if ($event->getSubject() !== 'circle_delete') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle,
			$this->l10n->t('You deleted {circle}'),
			$this->l10n->t('{author} deleted {circle}')
		);

		throw new FakeException();
	}
}

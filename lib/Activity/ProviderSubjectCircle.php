<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			$this->l10n->t('You created the team {circle}'),
			$this->l10n->t('{author} created the team {circle}')
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

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Activity;

use OCA\Circles\Exceptions\FakeException;
use OCP\Activity\IEvent;

class ProviderSubjectCircle extends ProviderParser {
	public function parseSubjectCircleCreate(IEvent $event, array $params): void {
		if ($event->getSubject() !== 'circle_create') {
			return;
		}

		$this->parseCircleEvent(
			$event, $params,
			$this->l10n->t('You created the team {circle}'),
			$this->l10n->t('{author} created the team {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseSubjectCircleDelete(IEvent $event, array $params): void {
		if ($event->getSubject() !== 'circle_delete') {
			return;
		}

		$this->parseCircleEvent(
			$event, $params,
			$this->l10n->t('You deleted {circle}'),
			$this->l10n->t('{author} deleted {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseSubjectCircleLeavingParentCircles(IEvent $event, array $params): void {
		if ($event->getSubject() !== 'circle_leaving_parent_circles') {
			return;
		}

		$this->parseCircleEvent(
			$event, $params,
			$this->l10n->t('You removed {circle} from all teams it belonged to'),
			$this->l10n->t('{author} removed {circle} from all teams it belonged to')
		);

		throw new FakeException();
	}
}

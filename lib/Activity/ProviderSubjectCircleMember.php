<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Activity;

use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\Activity\IEvent;

class ProviderSubjectCircleMember extends ProviderParser {
	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @param string $ownEvent
	 * @param string $othersEvent
	 * @return void
	 */
	protected function parseMemberCircleEvent(
		IEvent $event,
		Circle $circle,
		Member $member,
		string $ownEvent,
		string $othersEvent,
	): void {
		$data = [
			'author' => $this->generateViewerParameter($circle),
			'circle' => $this->generateCircleParameter($circle),
			'member' => $this->generateUserParameter($member),
			'external' => $this->generateExternalMemberParameter($member),
			'group' => $this->generateGroupParameter($member),
		];

		if ($this->isViewerTheAuthor($circle, $this->activityManager->getCurrentUserId())) {
			$this->setSubject($event, $ownEvent, $data);

			return;
		}

		$this->setSubject($event, $othersEvent, $data);
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @throws FakeException
	 */
	public function parseSubjectCircleMemberJoin(
		IEvent $event,
		Circle $circle,
		Member $member,
	): void {
		if ($event->getSubject() !== 'member_circle_joined') {
			return;
		}

		$this->parseMemberCircleEvent(
			$event, $circle, $member,
			$this->l10n->t('You made {member} join {circle}'),
			$this->l10n->t('{author} made {member} join {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @return void
	 * @throws FakeException
	 */
	public function parseSubjectCircleMemberAdd(
		IEvent $event,
		Circle $circle,
		Member $member,
	): void {
		if ($event->getSubject() !== 'member_circle_added') {
			return;
		}

		$this->parseMemberCircleEvent(
			$event, $circle, $member,
			$this->l10n->t('You added team {member} as member to {circle}'),
			$this->l10n->t('{author} added team {member} as member to {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @return void
	 * @throws FakeException
	 */
	public function parseSubjectCircleMemberLeft(
		IEvent $event,
		Circle $circle,
		Member $member,
	): void {
		if ($event->getSubject() !== 'member_circle_left') {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You made {member} leave {circle}'),
			$this->l10n->t('{author} made {member} leave {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @return void
	 * @throws FakeException
	 */
	public function parseSubjectCircleMemberRemove(
		IEvent $event,
		Circle $circle,
		Member $member,
	): void {
		if ($event->getSubject() !== 'member_circle_removed') {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You removed {member} from {circle}'),
			$this->l10n->t('{author} removed {member} from {circle}')
		);

		throw new FakeException();
	}
}

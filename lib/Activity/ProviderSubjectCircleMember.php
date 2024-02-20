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
		string $othersEvent
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
		Member $member
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
		Member $member
	): void {
		if ($event->getSubject() !== 'member_circle_added') {
			return;
		}

		$this->parseMemberCircleEvent(
			$event, $circle, $member,
			$this->l10n->t('You added circle {member} as member to {circle}'),
			$this->l10n->t('{author} added circle {member} has been added as member to {circle}')
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
		Member $member
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
		Member $member
	): void {
		if ($event->getSubject() !== 'member_circle_removed') {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You removed {member} leave {circle}'),
			$this->l10n->t('{author} made {member} leave {circle}')
		);

		throw new FakeException();
	}
}

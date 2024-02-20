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

class ProviderSubjectMember extends ProviderParser {
	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberJoin(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($event->getSubject() !== 'member_join') {
			return;
		}

		$this->parseSubjectMemberJoinOnInvite($event, $circle, $member);
		$this->parseCircleMemberEvent(
			$event, $circle, $member, $this->l10n->t('You joined {circle}'),
			$this->l10n->t('{member} joined {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberJoinOnInvite(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if (!$circle->isConfig(Circle::CFG_INVITE)) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You accepted the invitation to join {circle}'),
			$this->l10n->t('{member} accepted the invitation to join {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberAdd(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($event->getSubject() !== 'member_added') {
			return;
		}

		$this->parseSubjectMemberAddNotLocalMember($event, $circle, $member);
		$this->parseSubjectMemberAddClosedCircle($event, $circle, $member);
		$this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t('You added {member} as member to {circle}'),
			$this->l10n->t('You have been added as member to {circle} by {author}'),
			$this->l10n->t('{member} has been added as member to {circle} by {author}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberAddNotLocalMember(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($member->getUserType() === Member::TYPE_USER) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You added {external} to {circle}'),
			$this->l10n->t('{external} has been added to {circle} by {author}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberAddClosedCircle(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if (!$circle->isConfig(Circle::CFG_REQUEST)) {
			return;
		}

		$this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t("You accepted {member}'s request to join {circle}"),
			$this->l10n->t('Your request to join {circle} has been accepted by {author}'),
			$this->l10n->t("{member}'s request to join {circle} has been accepted by {author}")
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberLeft(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($event->getSubject() !== 'member_left') {
			return;
		}

		$this->parseSubjectNonMemberLeftInvite($event, $circle, $member);
		$this->parseSubjectNonMemberLeftRequest($event, $circle, $member);
		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You left {circle}'),
			$this->l10n->t('{member} left {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectNonMemberLeftInvite(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if (!$circle->isConfig(Circle::CFG_INVITE)
			|| $member->getLevel() > Member::LEVEL_NONE) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t("You declined the invitation to join {circle}"),
			$this->l10n->t("{member} declined an invitation to join {circle}")
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectNonMemberLeftRequest(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if (!$circle->isConfig(Circle::CFG_REQUEST)
			|| $member->getLevel() > Member::LEVEL_NONE) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t("You cancelled your request to join {circle}"),
			$this->l10n->t("{member} cancelled a request to join {circle}")
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberRemove(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($event->getSubject() !== 'member_remove') {
			return;
		}

		$this->parseSubjectMemberRemoveNotLocalMember($event, $circle, $member);
		$this->parseSubjectMemberRemoveNotYetMember($event, $circle, $member);
		$this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t('You removed {member} from {circle}'),
			$this->l10n->t('You have been removed from {circle} by {author}'),
			$this->l10n->t('{member} has been removed from {circle} by {author}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberRemoveNotLocalMember(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($member->getUserType() === Member::TYPE_USER) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You removed {external} from {circle}'),
			$this->l10n->t('{external} has been removed from {circle} by {author}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberRemoveNotYetMember(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if (!$circle->isConfig(Circle::CFG_INVITE)
			|| $member->getLevel() > Member::LEVEL_NONE) {
			return;
		}

		$this->parseSubjectMemberRemoveNotYetMemberRequesting($event, $circle, $member);
		$this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t("You cancelled {member}'s invitation to join {circle}"),
			$this->l10n->t('Your invitation to join {circle} has been cancelled by {author}'),
			$this->l10n->t("{author} cancelled {member}'s invitation to join {circle}")
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @throws FakeException
	 */
	private function parseSubjectMemberRemoveNotYetMemberRequesting(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($member->getStatus() !== Member::STATUS_REQUEST) {
			return;
		}

		$this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t("You dismissed {member}'s request to join {circle}"),
			$this->l10n->t('Your request to join {circle} has been dismissed by {author}'),
			$this->l10n->t("{member}'s request to join {circle} has been dismissed by {author}")
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @throws FakeException
	 */
	public function parseMemberInvited(IEvent $event, Circle $circle, Member $member): void {
		if ($event->getSubject() !== 'member_invited') {
			return;
		}

		$this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t('You invited {member} to join {circle}'),
			$this->l10n->t('You have been invited to join {circle} by {author}'),
			$this->l10n->t('{member} has been invited to join {circle} by {author}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @param int $level
	 * @throws FakeException
	 */
	public function parseMemberLevel(
		IEvent $event,
		Circle $circle,
		Member $member,
		int $level
	): void {
		if ($event->getSubject() !== 'member_level') {
			return;
		}

		$levelString = $this->l10n->t(Member::$DEF_LEVEL[$level] ?? '');
		$this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t('You changed {member}\'s level in {circle} to %1$s', [$levelString]),
			$this->l10n->t('{author} changed your level in {circle} to %1$s', [$levelString]),
			$this->l10n->t('{author} changed {member}\'s level in {circle} to %1$s', [$levelString])
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseMemberRequestInvitation(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($event->getSubject() !== 'member_request_invitation') {
			return;
		}

		$this->parseMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You sent a request to join {circle}'),
			$this->l10n->t('{member} sent a request to join {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseMemberOwner(
		IEvent $event,
		Circle $circle,
		Member $member
	): void {
		if ($event->getSubject() !== 'member_owner') {
			return;
		}

		$this->parseMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You are the new owner of {circle}'),
			$this->l10n->t('{member} is the new owner of {circle}')
		);
		throw new FakeException();
	}
}

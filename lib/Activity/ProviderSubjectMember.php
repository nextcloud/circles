<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCP\Activity\IEvent;

class ProviderSubjectMember extends ProviderParser {
	/**
	 * Parse on Subject 'member_join'.
	 * If circle is closed, we say that user accepted his invitation.
	 *
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberJoin(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($event->getSubject() !== 'member_join') {
			return;
		}

		$this->parseSubjectMemberJoinClosedCircle($event, $circle, $member);
		$this->parseCircleMemberEvent(
			$event, $circle, $member, $this->l10n->t('You joined {circle}'),
			$this->l10n->t('{member} joined {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberJoinClosedCircle(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($circle->getType() !== DeprecatedCircle::CIRCLES_CLOSED) {
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
	 * Parse on Subject 'member_add'.
	 * If circle is closed, we say that user's invitation was accepted.
	 *
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberAdd(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($event->getSubject() !== 'member_add') {
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
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberAddNotLocalMember(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member
	) {
		if ($member->getType() === DeprecatedMember::TYPE_USER) {
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
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberAddClosedCircle(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($circle->getType() !== DeprecatedCircle::CIRCLES_CLOSED) {
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
	 * Parse on Subject 'member_left'.
	 * If circle is closed and member was not a real member, we send him to
	 * parseSubjectNonMemberLeftClosedCircle();
	 *
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberLeft(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($event->getSubject() !== 'member_left') {
			return;
		}

		$this->parseSubjectNonMemberLeftClosedCircle($event, $circle, $member);
		$this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t('You left {circle}'),
			$this->l10n->t('{member} left {circle}')
		);

		throw new FakeException();
	}


	/**
	 * Parse on Subject 'member_left' on a closed circle when user were not yet a member.
	 * If status is Invited we say that member rejected his invitation.
	 * If status is Requested we say he dismissed his request.
	 *
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectNonMemberLeftClosedCircle(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member
	) {
		if ($circle->getType() !== DeprecatedCircle::CIRCLES_CLOSED
			|| $member->getLevel() !== DeprecatedMember::LEVEL_NONE) {
			return;
		}

		if ($member->getStatus() === DeprecatedMember::STATUS_INVITED) {
			$this->parseCircleMemberEvent(
				$event, $circle, $member,
				$this->l10n->t("You declined the invitation to join {circle}"),
				$this->l10n->t("{member} declined an invitation to join {circle}")
			);
		} else {
			$this->parseCircleMemberEvent(
				$event, $circle, $member,
				$this->l10n->t("You cancelled your request to join {circle}"),
				$this->l10n->t("{member} cancelled his request to join {circle}")
			);
		}

		throw new FakeException();
	}


	/**
	 * Parse on Subject 'member_remove'.
	 * If circle is closed and member was not a real member, we send him to
	 * parseSubjectNonMemberRemoveClosedCircle();
	 *
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberRemove(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
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
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberRemoveNotLocalMember(
		IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member
	) {
		if ($member->getType() === DeprecatedMember::TYPE_USER) {
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
	 * Parse on Subject 'member_remove' on a closed circle when user were not yet a member.
	 * If status is Invited we say that author cancelled his invitation.
	 * If status is Requested we say that his invitation was rejected.
	 *
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberRemoveNotYetMember(
		IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member
	) {
		if ($circle->getType() !== DeprecatedCircle::CIRCLES_CLOSED
			|| $member->getLevel() !== DeprecatedMember::LEVEL_NONE) {
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


	private function parseSubjectMemberRemoveNotYetMemberRequesting(
		IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member
	) {
		if ($member->getStatus() !== DeprecatedMember::STATUS_REQUEST) {
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
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	public function parseMemberInvited(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
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
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	public function parseMemberLevel(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($event->getSubject() !== 'member_level') {
			return;
		}

		$level = [$this->l10n->t($member->getLevelString())];
		$this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t('You changed {member}\'s level in {circle} to %1$s', $level),
			$this->l10n->t('{author} changed your level in {circle} to %1$s', $level),
			$this->l10n->t('{author} changed {member}\'s level in {circle} to %1$s', $level)
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	public function parseMemberRequestInvitation(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
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
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws FakeException
	 */
	public function parseMemberOwner(IEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
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

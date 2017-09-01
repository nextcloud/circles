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
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCP\Activity\IEvent;

class SubjectProvider extends BaseProvider {

	/**
	 * Parse on Subject 'member_join'.
	 * If circle is closed, we say that user accepted his invitation.
	 *
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberJoin(IEvent &$event, Circle $circle, Member $member) {
		if ($event->getSubject() !== 'member_request_invitation') {
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
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberJoinClosedCircle(IEvent &$event, Circle $circle, Member $member) {
		if ($circle->getType() !== Circle::CIRCLES_CLOSED) {
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
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberAdd(IEvent &$event, Circle $circle, Member $member) {
		if ($event->getSubject() !== 'member_add') {
			return;
		}

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
	public function parseSubjectMemberAddClosedCircle(IEvent &$event, Circle $circle, Member $member) {
		if ($circle->getType() !== Circle::CIRCLES_CLOSED) {
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
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberLeft(IEvent &$event, Circle $circle, Member $member) {

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
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectNonMemberLeftClosedCircle(IEvent &$event, Circle $circle, Member $member
	) {
		if ($circle->getType() !== Circle::CIRCLES_CLOSED
			|| $member->getLevel() !== Member::LEVEL_NONE) {
			return;
		}

		if ($member->getStatus() === Member::STATUS_INVITED) {
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
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberRemove(IEvent &$event, Circle $circle, Member $member) {

		if ($event->getSubject() !== 'member_remove') {
			return;
		}

		if ($circle->getType() === Circle::CIRCLES_CLOSED
			&& $member->getLevel() === Member::LEVEL_NONE) {
			$this->parseSubjectNonMemberRemoveClosedCircle($event, $circle, $member);

		} else {
			$this->parseCircleMemberAdvancedEvent(
				$event, $circle, $member,
				$this->l10n->t('You removed {member} from {circle}'),
				$this->l10n->t('You have been removed from {circle} by {author}'),
				$this->l10n->t('{member} has been removed from {circle} by {author}')
			);
		}

		throw new FakeException();
	}


	/**
	 * Parse on Subject 'member_remove' on a closed circle when user were not yet a member.
	 * If status is Invited we say that author cancelled his invitation.
	 * If status is Requested we say that his invitation was rejected.
	 *
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @return IEvent
	 */
	public function parseSubjectNonMemberRemoveClosedCircle(
		IEvent &$event, Circle $circle, Member $member
	) {
		if ($member->getStatus() === Member::STATUS_REQUEST) {
			return $this->parseCircleMemberAdvancedEvent(
				$event, $circle, $member,
				$this->l10n->t("You dismissed {member}'s request to join {circle}"),
				$this->l10n->t('Your request to join {circle} has been dismissed by {author}'),
				$this->l10n->t("{member}'s request to join {circle} has been dismissed by {author}")
			);
		}

		return $this->parseCircleMemberAdvancedEvent(
			$event, $circle, $member,
			$this->l10n->t("You cancelled {member}'s invitation to join {circle}"),
			$this->l10n->t('Your invitation to join {circle} has been cancelled by {author}'),
			$this->l10n->t("{author} cancelled {member}'s invitation to join {circle}")
		);
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $group
	 *
	 * @throws FakeException
	 */
	public function parseGroupLink(IEvent &$event, Circle $circle, Member $group) {
		if ($event->getSubject() !== 'group_link') {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $circle, $group,
			$this->l10n->t('You linked {group} to {circle}'),
			$this->l10n->t('{group} has been linked to {circle} by {author}')
		);
		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $group
	 *
	 * @throws FakeException
	 */
	public function parseGroupUnlink(IEvent &$event, Circle $circle, Member $group) {
		if ($event->getSubject() !== 'group_unlink') {
			return;
		}
		$this->parseCircleMemberEvent(
			$event, $circle, $group,
			$this->l10n->t('You unlinked {group} from {circle}'),
			$this->l10n->t('{group} has been unlinked from {circle} by {author}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $group
	 *
	 * @throws FakeException
	 */
	public function parseGroupLevel(IEvent &$event, Circle $circle, Member $group) {
		if ($event->getSubject() !== 'group_level') {
			return;
		}

		$l = $this->l10n;

		$level = [$l->t($group->getLevelString())];
		$this->parseCircleMemberEvent(
			$event, $circle, $group,
			$l->t('You changed the level of the linked group {group} in {circle} to %1$s', $level),
			$l->t('{author} changed the level of the linked group {group} in {circle} to %1$s', $level)
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
	public function parseMemberInvited(IEvent &$event, Circle $circle, Member $member) {
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
	 *
	 * @throws FakeException
	 */
	public function parseMemberLevel(IEvent &$event, Circle $circle, Member $member) {
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
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @throws FakeException
	 */
	public function parseMemberRequestInvitation(IEvent &$event, Circle $circle, Member $member) {
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
	public function parseMemberOwner(IEvent &$event, Circle $circle, Member $member) {
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


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestSent(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_sent') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You sent a request to link {circle} with {remote}'),
			$this->l10n->t('{author} sent a request to link {circle} with {remote}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestReceived(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_received') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote, $this->l10n->t('{remote} requested a link with {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestRejected(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_rejected') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t('The request to link {circle} with {remote} has been rejected')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestCanceled(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_canceled') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t(
				'The request to link {remote} with {circle} has been canceled remotely'
			)
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestAccepted(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_accepted') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t('The request to link {circle} with {remote} has been accepted')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestRemoved(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_removed') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You dismissed the request to link {remote} with {circle}'),
			$this->l10n->t('{author} dismissed the request to link {remote} with {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestCanceling(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_canceling') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You canceled the request to link {circle} with {remote}'),
			$this->l10n->t('{author} canceled the request to link {circle} with {remote}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRequestAccepting(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_request_accepting') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You accepted the request to link {remote} with {circle}'),
			$this->l10n->t('{author} accepted the request to link {remote} with {circle}')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkUp(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_up') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t('A link between {circle} and {remote} is now up and running')
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkDown(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_down') {
			return;
		}

		$this->parseLinkEvent(
			$event, $circle, $remote,
			$this->l10n->t(
				'The link between {circle} and {remote} has been shutdown remotely'
			)
		);

		throw new FakeException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param FederatedLink $remote
	 *
	 * @throws FakeException
	 */
	public function parseLinkRemove(IEvent &$event, Circle $circle, FederatedLink $remote) {
		if ($event->getSubject() !== 'link_remove') {
			return;
		}

		$this->parseCircleEvent(
			$event, $circle, $remote,
			$this->l10n->t('You closed the link between {circle} and {remote}'),
			$this->l10n->t('{author} closed the link between {circle} and {remote}')
		);

		throw new FakeException();
	}
}
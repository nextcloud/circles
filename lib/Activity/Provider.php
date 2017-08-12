<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

use Exception;
use InvalidArgumentException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CirclesService;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OpenCloud\Common\Exceptions\InvalidArgumentError;

class Provider extends BaseProvider implements IProvider {


	/**
	 * @param string $lang
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 *
	 * @return IEvent
	 */
	public function parse($lang, IEvent $event, IEvent $previousEvent = null) {

		if ($event->getApp() !== 'circles') {
			throw new \InvalidArgumentException();
		}

		try {

			$params = $event->getSubjectParameters();
			$circle = Circle::fromJSON($this->l10n, $params['circle']);

			$this->setIcon($event, $circle);
			$this->parseAsMember($event, $circle, $params);
			$this->parseAsModerator($event, $circle, $params);
			$this->generateParsedSubject($event);

			return $event;
		} catch (\Exception $e) {
			throw new \InvalidArgumentException();
		}
	}


	private function setIcon(IEvent &$event, Circle $circle) {
		$event->setIcon(
			CirclesService::getCircleIcon(
				$circle->getType(),
				(method_exists($this->activityManager, 'getRequirePNG')
				 && $this->activityManager->getRequirePNG())
			)
		);
	}

	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @return IEvent
	 */
	private function parseAsMember(IEvent &$event, Circle $circle, $params) {
		if ($event->getType() !== 'circles_as_member') {
			return $event;
		}

		switch ($event->getSubject()) {
			case 'circle_create':
				return $this->parseCircleEvent(
					$event, $circle, null,
					$this->l10n->t('You created the circle {circle}'),
					$this->l10n->t('{author} created the circle {circle}')
				);

			case 'circle_delete':
				return $this->parseCircleEvent(
					$event, $circle, null,
					$this->l10n->t('You deleted {circle}'),
					$this->l10n->t('{author} deleted {circle}')
				);
		}

		if (key_exists('member', $params)) {
			$this->parseMemberAsMember($event, $circle);
		}

		return $event;
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @return IEvent
	 * @throws Exception
	 */
	private function parseAsModerator(IEvent &$event, Circle $circle, $params) {
		if ($event->getType() !== 'circles_as_moderator') {
			return $event;
		}

		try {
			if (key_exists('member', $params)) {
				return $this->parseMemberAsModerator($event, $circle);
			}

			if (key_exists('group', $params)) {
				return $this->parseGroupAsModerator($event, $circle);
			}

			if (key_exists('link', $params)) {
				return $this->parseLinkAsModerator($event, $circle);
			}

			throw new InvalidArgumentError();
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberAsMember(IEvent &$event, Circle $circle) {
		$params = $event->getSubjectParameters();
		$member = Member::fromJSON($params['member']);

		switch ($event->getSubject()) {
			case 'member_join':
				return $this->parseSubjectMemberJoin($event, $circle, $member);

			case 'member_add':
				return $this->parseSubjectMemberAdd($event, $circle, $member);

			case 'member_left':
				return $this->parseSubjectMemberLeft($event, $circle, $member);

			case 'member_remove':
				return $this->parseSubjectMemberRemove($event, $circle, $member);
		}

		return $event;
	}


	/**
	 * Parse on Subject 'member_join'.
	 * If circle is closed, we say that user accepted his invitation.
	 *
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @return IEvent
	 */
	private function parseSubjectMemberJoin(IEvent &$event, Circle $circle, Member $member) {
		if ($circle->getType() === Circle::CIRCLES_CLOSED) {
			return $this->parseCircleMemberEvent(
				$event, $circle, $member,
				$this->l10n->t('You accepted the invitation to join {circle}'),
				$this->l10n->t('{member} accepted the invitation to join {circle}')
			);
		} else {
			return $this->parseCircleMemberEvent(
				$event, $circle, $member,
				$this->l10n->t('You joined {circle}'),
				$this->l10n->t('{member} joined {circle}')
			);
		}
	}


	/**
	 * Parse on Subject 'member_add'.
	 * If circle is closed, we say that user's invitation was accepted.
	 *
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 *
	 * @return IEvent
	 */
	private function parseSubjectMemberAdd(IEvent &$event, Circle $circle, Member $member) {
		if ($circle->getType() === Circle::CIRCLES_CLOSED) {
			return $this->parseCircleMemberAdvancedEvent(
				$event, $circle, $member,
				$this->l10n->t("You accepted {member}'s request to join {circle}"),
				$this->l10n->t('Your request to join {circle} has been accepted by {author}'),
				$this->l10n->t("{member}'s request to join {circle} has been accepted by {author}")
			);
		} else {
			return $this->parseCircleMemberAdvancedEvent(
				$event, $circle, $member,
				$this->l10n->t('You added {member} as member to {circle}'),
				$this->l10n->t('You have been added as member to {circle} by {author}'),
				$this->l10n->t('{member} has been added as member to {circle} by {author}')
			);
		}
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
	 * @return IEvent
	 */
	private function parseSubjectMemberLeft(IEvent &$event, Circle $circle, Member $member) {
		if ($circle->getType() === Circle::CIRCLES_CLOSED
			&& $member->getLevel() === Member::LEVEL_NONE) {
			return $this->parseSubjectNonMemberLeftClosedCircle($event, $circle, $member);
		} else {
			return $this->parseCircleMemberEvent(
				$event, $circle, $member,
				$this->l10n->t('You left {circle}'),
				$this->l10n->t('{member} left {circle}')
			);
		}
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
	 * @return IEvent
	 */
	private function parseSubjectNonMemberLeftClosedCircle(
		IEvent &$event, Circle $circle, Member $member
	) {
		if ($member->getStatus() === Member::STATUS_INVITED) {
			return $this->parseCircleMemberEvent(
				$event, $circle, $member,
				$this->l10n->t("You declined the invitation to join {circle}"),
				$this->l10n->t("{member} declined an invitation to join {circle}")
			);
		}

		return $this->parseCircleMemberEvent(
			$event, $circle, $member,
			$this->l10n->t("You cancelled your request to join {circle}"),
			$this->l10n->t("{member} cancelled his request to join {circle}")
		);
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
	 * @return IEvent
	 */
	private function parseSubjectMemberRemove(IEvent &$event, Circle $circle, Member $member) {
		if ($circle->getType() === Circle::CIRCLES_CLOSED
			&& $member->getLevel() === Member::LEVEL_NONE) {
			return $this->parseSubjectNonMemberRemoveClosedCircle($event, $circle, $member);

		} else {
			return $this->parseCircleMemberAdvancedEvent(
				$event, $circle, $member,
				$this->l10n->t('You removed {member} from {circle}'),
				$this->l10n->t('You have been removed from {circle} by {author}'),
				$this->l10n->t('{member} has been removed from {circle} by {author}')
			);
		}
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
	private function parseSubjectNonMemberRemoveClosedCircle(
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
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseGroupAsModerator(IEvent &$event, Circle $circle) {

		$params = $event->getSubjectParameters();
		$group = Member::fromJSON($params['group']);

		switch ($event->getSubject()) {

			case 'group_link':
				return $this->parseCircleMemberEvent(
					$event, $circle, $group,
					$this->l10n->t('You linked {group} to {circle}'),
					$this->l10n->t('{group} has been linked to {circle} by {author}')
				);

			case 'group_unlink':
				return $this->parseCircleMemberEvent(
					$event, $circle, $group,
					$this->l10n->t('You unlinked {group} from {circle}'),
					$this->l10n->t('{group} has been unlinked from {circle} by {author}')
				);

			case 'group_level':
				$level = [$this->l10n->t($group->getLevelString())];

				return $this->parseCircleMemberEvent(
					$event, $circle, $group,
					$this->l10n->t(
						'You changed the level of the linked group {group} in {circle} to %1$s',
						$level
					),
					$this->l10n->t(
						'{author} changed the level of the linked group {group} in {circle} to %1$s',
						$level
					)
				);
		}

		throw new InvalidArgumentException();
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseMemberAsModerator(IEvent &$event, Circle $circle) {

		$params = $event->getSubjectParameters();
		$member = Member::fromJSON($params['member']);

		switch ($event->getSubject()) {
			case 'member_invited':
				return $this->parseCircleMemberAdvancedEvent(
					$event, $circle, $member,
					$this->l10n->t('You invited {member} to join {circle}'),
					$this->l10n->t('You have been invited to join {circle} by {author}'),
					$this->l10n->t('{member} has been invited to join {circle} by {author}')
				);

			case 'member_level':
				$level = [$this->l10n->t($member->getLevelString())];

				return $this->parseCircleMemberAdvancedEvent(
					$event, $circle, $member,
					$this->l10n->t('You changed {member}\'s level in {circle} to %1$s', $level),
					$this->l10n->t('{author} changed your level in {circle} to %1$s', $level),
					$this->l10n->t('{author} changed {member}\'s level in {circle} to %1$s', $level)
				);

			case 'member_request_invitation':
				return $this->parseMemberEvent(
					$event, $circle, $member,
					$this->l10n->t('You sent a request to join {circle}'),
					$this->l10n->t('{member} sent a request to join {circle}')
				);

			case 'member_owner':
				return $this->parseMemberEvent(
					$event, $circle, $member,
					$this->l10n->t('You are the new owner of {circle}'),
					$this->l10n->t('{member} is the new owner of {circle}')
				);
		}

		throw new InvalidArgumentException();
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 *
	 * @return IEvent
	 */
	private function parseLinkAsModerator(IEvent &$event, Circle $circle) {

		$params = $event->getSubjectParameters();
		$remote = FederatedLink::fromJSON($params['link']);

		switch ($event->getSubject()) {
			case 'link_request_sent':
				return $this->parseCircleEvent(
					$event, $circle, $remote,
					$this->l10n->t('You sent a request to link {circle} with {remote}'),
					$this->l10n->t('{author} sent a request to link {circle} with {remote}')
				);

			case 'link_request_received';
				return $this->parseLinkEvent(
					$event, $circle, $remote,
					$this->l10n->t('{remote} requested a link with {circle}')
				);

			case 'link_request_rejected';
				return $this->parseLinkEvent(
					$event, $circle, $remote, $this->l10n->t(
					'The request to link {circle} with {remote} has been rejected'
				)
				);

			case 'link_request_canceled':
				return $this->parseLinkEvent(
					$event, $circle, $remote,
					$this->l10n->t(
						'The request to link {remote} with {circle} has been canceled remotely'
					)
				);

			case 'link_request_accepted':
				return $this->parseLinkEvent(
					$event, $circle, $remote,
					$this->l10n->t('The request to link {circle} with {remote} has been accepted')
				);

			case 'link_request_removed':
				return $this->parseCircleEvent(
					$event, $circle, $remote,
					$this->l10n->t('You dismissed the request to link {remote} with {circle}'),
					$this->l10n->t('{author} dismissed the request to link {remote} with {circle}')
				);

			case 'link_request_canceling':
				return $this->parseCircleEvent(
					$event, $circle, $remote,
					$this->l10n->t('You canceled the request to link {circle} with {remote}'),
					$this->l10n->t('{author} canceled the request to link {circle} with {remote}')
				);

			case 'link_request_accepting':
				return $this->parseCircleEvent(
					$event, $circle, $remote,
					$this->l10n->t('You accepted the request to link {remote} with {circle}'),
					$this->l10n->t('{author} accepted the request to link {remote} with {circle}')
				);

			case 'link_up':
				return $this->parseLinkEvent(
					$event, $circle, $remote,
					$this->l10n->t('A link between {circle} and {remote} is now up and running')
				);

			case 'link_down':
				return $this->parseLinkEvent(
					$event, $circle, $remote,
					$this->l10n->t(
						'The link between {circle} and {remote} has been shutdown remotely'
					)
				);

			case 'link_remove':
				return $this->parseCircleEvent(
					$event, $circle, $remote,
					$this->l10n->t('You closed the link between {circle} and {remote}'),
					$this->l10n->t('{author} closed the link between {circle} and {remote}')
				);
		}

		throw new InvalidArgumentException();
	}

}

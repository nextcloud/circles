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
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CirclesService;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OpenCloud\Common\Exceptions\InvalidArgumentError;

class Provider extends SubjectProvider implements IProvider {


	/**
	 * @param string $lang
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 *
	 * @return IEvent
	 */
	public function parse($lang, IEvent $event, IEvent $previousEvent = null) {

		if ($event->getApp() !== Application::APP_NAME) {
			throw new \InvalidArgumentException();
		}

		try {

			$params = $event->getSubjectParameters();
			$circle = Circle::fromJSON($params['circle']);

			$this->setIcon($event, $circle);

			$this->parseAsMember($event, $circle, $params);
			$this->parseAsModerator($event, $circle, $params);

		} catch (FakeException $e) {
		} catch (\Exception $e) {
			throw new \InvalidArgumentException();
		}

		$this->generateParsedSubject($event);

		return $event;
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 */
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
	 */
	private function parseAsMember(IEvent &$event, Circle $circle, $params) {
		if ($event->getType() !== 'circles_as_member') {
			return;
		}

		switch ($event->getSubject()) {
			case 'circle_create':
				$this->parseCircleEvent(
					$event, $circle, null,
					$this->l10n->t('You created the circle {circle}'),
					$this->l10n->t('{author} created the circle {circle}')
				);

				return;

			case 'circle_delete':
				$this->parseCircleEvent(
					$event, $circle, null,
					$this->l10n->t('You deleted {circle}'),
					$this->l10n->t('{author} deleted {circle}')
				);

				return;
		}

		if (key_exists('member', $params)) {
			$this->parseMemberAsMember($event, $circle);
		}
	}


	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws Exception
	 */
	private function parseAsModerator(IEvent &$event, Circle $circle, $params) {
		if ($event->getType() !== 'circles_as_moderator') {
			return;
		}

		try {
			$this->parseMemberAsModerator($event, $circle, $params);
			$this->parseGroupAsModerator($event, $circle, $params);
			$this->parseLinkAsModerator($event, $circle, $params);


			throw new InvalidArgumentError();
		} catch (FakeException $e) {
			return;
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

		try {
			$this->parseSubjectMemberJoin($event, $circle, $member);
			$this->parseSubjectMemberAdd($event, $circle, $member);
			$this->parseSubjectMemberLeft($event, $circle, $member);
			$this->parseSubjectMemberRemove($event, $circle, $member);
		} catch (FakeException $e) {
			return $event;
		}


		return $event;
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param array $params
	 */
	private function parseGroupAsModerator(IEvent &$event, Circle $circle, $params) {

		if (!key_exists('group', $params)) {
			return;
		}
		$group = Member::fromJSON($params['group']);

		$this->parseGroupLink($event, $circle, $group);
		$this->parseGroupUnlink($event, $circle, $group);
		$this->parseGroupLevel($event, $circle, $group);

		throw new InvalidArgumentException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param array $params
	 */
	private function parseMemberAsModerator(IEvent &$event, Circle $circle, $params) {

		if (!key_exists('member', $params)) {
			return;
		}
		$member = Member::fromJSON($params['member']);

		$this->parseMemberInvited($event, $circle, $member);
		$this->parseMemberLevel($event, $circle, $member);
		$this->parseMemberRequestInvitation($event, $circle, $member);
		$this->parseMemberOwner($event, $circle, $member);

		throw new InvalidArgumentException();
	}


	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param array $params
	 */
	private function parseLinkAsModerator(IEvent &$event, Circle $circle, $params) {

		if (!key_exists('link', $params)) {
			return;
		}

		$remote = FederatedLink::fromJSON($params['link']);

		$this->parseLinkRequestSent($event, $circle, $remote);
		$this->parseLinkRequestReceived($event, $circle, $remote);
		$this->parseLinkRequestRejected($event, $circle, $remote);
		$this->parseLinkRequestCanceled($event, $circle, $remote);
		$this->parseLinkRequestAccepted($event, $circle, $remote);
		$this->parseLinkRequestRemoved($event, $circle, $remote);
		$this->parseLinkRequestCanceling($event, $circle, $remote);
		$this->parseLinkRequestAccepting($event, $circle, $remote);
		$this->parseLinkUp($event, $circle, $remote);
		$this->parseLinkDown($event, $circle, $remote);
		$this->parseLinkRemove($event, $circle, $remote);

		throw new InvalidArgumentException();
	}


}
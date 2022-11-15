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

use Exception;
use InvalidArgumentException;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\MiscService;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;

/**
 * Class Provider
 *
 * @package OCA\Circles\Activity
 */
class Provider implements IProvider {
	/** @var ProviderSubjectCircle */
	private $parserCircle;

	/** @var ProviderSubjectMember */
	private $parserMember;

	/** @var ProviderSubjectGroup */
	private $parserGroup;

	/** @var ProviderSubjectLink */
	private $parserLink;

	/** @var MiscService */
	protected $miscService;

	/** @var IManager */
	protected $activityManager;


	public function __construct(
		IManager $activityManager, MiscService $miscService, ProviderSubjectCircle $parserCircle,
		ProviderSubjectMember $parserMember, ProviderSubjectGroup $parserGroup,
		ProviderSubjectLink $parserLink
	) {
		$this->activityManager = $activityManager;
		$this->miscService = $miscService;

		$this->parserCircle = $parserCircle;
		$this->parserMember = $parserMember;
		$this->parserGroup = $parserGroup;
		$this->parserLink = $parserLink;
	}


	/**
	 * {@inheritdoc}
	 */
	public function parse($lang, IEvent $event, IEvent $previousEvent = null) {
		try {
			$params = $event->getSubjectParameters();
			$this->initActivityParser($event, $params);

			$circle = DeprecatedCircle::fromJSON($params['circle']);

			$this->setIcon($event, $circle);
			$this->parseAsNonMember($event, $circle, $params);
			$this->parseAsMember($event, $circle, $params);
			$this->parseAsModerator($event, $circle, $params);
		} catch (FakeException $e) {
			/** clean exit */
		}

		return $event;
	}


	/**
	 * @param IEvent $event
	 * @param array $params
	 */
	private function initActivityParser(IEvent $event, $params) {
		if ($event->getApp() !== Application::APP_ID) {
			throw new InvalidArgumentException();
		}

		if (!key_exists('circle', $params)) {
			throw new InvalidArgumentException();
		}
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 */
	private function setIcon(IEvent $event, DeprecatedCircle $circle) {
		$event->setIcon(
			CirclesService::getCircleIcon(
				$circle->getType(),
				(method_exists($this->activityManager, 'getRequirePNG')
				 && $this->activityManager->getRequirePNG())
			)
		);
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseAsNonMember(IEvent $event, DeprecatedCircle $circle, $params) {
		if ($event->getType() !== 'circles_as_non_member') {
			return;
		}

		$this->parserCircle->parseSubjectCircleCreate($event, $circle);
	}

	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseAsMember(IEvent $event, DeprecatedCircle $circle, $params) {
		if ($event->getType() !== 'circles_as_member') {
			return;
		}

//		$this->parserCircle->parseSubjectCircleCreate($event, $circle);
		$this->parserCircle->parseSubjectCircleDelete($event, $circle);
		$this->parseMemberAsMember($event, $circle, $params);
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws Exception
	 */
	private function parseAsModerator(IEvent $event, DeprecatedCircle $circle, $params) {
		if ($event->getType() !== 'circles_as_moderator') {
			return;
		}

		$this->parseMemberAsModerator($event, $circle, $params);
		$this->parseGroupAsModerator($event, $circle, $params);
		$this->parseLinkAsModerator($event, $circle, $params);
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseMemberAsMember(IEvent $event, DeprecatedCircle $circle, $params) {
		if (!key_exists('member', $params)) {
			return;
		}

		$member = DeprecatedMember::fromJSON($params['member']);

		$this->parserMember->parseSubjectMemberJoin($event, $circle, $member);
		$this->parserMember->parseSubjectMemberAdd($event, $circle, $member);
		$this->parserMember->parseSubjectMemberLeft($event, $circle, $member);
		$this->parserMember->parseSubjectMemberRemove($event, $circle, $member);
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseGroupAsModerator(IEvent $event, DeprecatedCircle $circle, $params) {
		if (!key_exists('group', $params)) {
			return;
		}

		$group = DeprecatedMember::fromJSON($params['group']);

		$this->parserGroup->parseGroupLink($event, $circle, $group);
		$this->parserGroup->parseGroupUnlink($event, $circle, $group);
		$this->parserGroup->parseGroupLevel($event, $circle, $group);
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseMemberAsModerator(IEvent $event, DeprecatedCircle $circle, $params) {
		if (!key_exists('member', $params)) {
			return;
		}

		$member = DeprecatedMember::fromJSON($params['member']);

		$this->parserMember->parseMemberInvited($event, $circle, $member);
		$this->parserMember->parseMemberLevel($event, $circle, $member);
		$this->parserMember->parseMemberRequestInvitation($event, $circle, $member);
		$this->parserMember->parseMemberOwner($event, $circle, $member);
	}


	/**
	 * @param IEvent $event
	 * @param DeprecatedCircle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseLinkAsModerator(IEvent $event, DeprecatedCircle $circle, $params) {
		if (!key_exists('link', $params)) {
			return;
		}

		$remote = FederatedLink::fromJSON($params['link']);

		$this->parserLink->parseLinkRequestSent($event, $circle, $remote);
		$this->parserLink->parseLinkRequestReceived($event, $circle, $remote);
		$this->parserLink->parseLinkRequestRejected($event, $circle, $remote);
		$this->parserLink->parseLinkRequestCanceled($event, $circle, $remote);
		$this->parserLink->parseLinkRequestAccepted($event, $circle, $remote);
		$this->parserLink->parseLinkRequestRemoved($event, $circle, $remote);
		$this->parserLink->parseLinkRequestCanceling($event, $circle, $remote);
		$this->parserLink->parseLinkRequestAccepting($event, $circle, $remote);
		$this->parserLink->parseLinkUp($event, $circle, $remote);
		$this->parserLink->parseLinkDown($event, $circle, $remote);
		$this->parserLink->parseLinkRemove($event, $circle, $remote);
	}
}

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

use InvalidArgumentException;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IURLGenerator;

class Provider implements IProvider {
	use TDeserialize;

	public function __construct(
		private IManager $activityManager,
		private IURLGenerator $urlGenerator,
		private ProviderSubjectCircle $parserCircle,
		private ProviderSubjectMember $parserMember,
		private ProviderSubjectCircleMember $parserCircleMember
	) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function parse($language, IEvent $event, IEvent $previousEvent = null): IEvent {
		try {
			$params = $event->getSubjectParameters();
			$this->initActivityParser($event, $params);

			/** @var Circle $circle */
			$circle = $this->deserializeJson($params['circle'], Circle::class);

			$this->setIcon($event);
			$this->parseAsNonMember($event, $circle);
			$this->parseAsMember($event, $circle, $params);
			$this->parseAsModerator($event, $circle, $params);
		} catch (FakeException|InvalidItemException $e) {
			/** clean exit */
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 */
	private function initActivityParser(IEvent $event, array $params): void {
		if ($event->getApp() !== Application::APP_ID) {
			throw new InvalidArgumentException();
		}

		if (!key_exists('circle', $params)) {
			throw new InvalidArgumentException();
		}
	}

	/**
	 * @param IEvent $event
	 */
	private function setIcon(IEvent $event): void {
		$path = $this->urlGenerator->imagePath(Application::APP_ID, 'circles.svg');
		$event->setIcon($this->urlGenerator->getAbsoluteURL($path));
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseAsNonMember(
		IEvent $event,
		Circle $circle
	): void {
		if ($event->getType() !== 'circles_as_non_member') {
			return;
		}

		$this->parserCircle->parseSubjectCircleCreate($event, $circle);
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseAsMember(
		IEvent $event,
		Circle $circle,
		array $params
	): void {
		if ($event->getType() !== 'circles_as_member') {
			return;
		}

		$this->parserCircle->parseSubjectCircleCreate($event, $circle);
		$this->parserCircle->parseSubjectCircleDelete($event, $circle);
		$this->parseMemberAsMember($event, $circle, $params);
		$this->parseCircleMemberAsMember($event, $circle, $params);
	}

	/**
	 * @param Circle $circle
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 * @throws InvalidItemException
	 */
	private function parseAsModerator(IEvent $event, Circle $circle, array $params): void {
		if ($event->getType() !== 'circles_as_moderator') {
			return;
		}

		$this->parseMemberAsModerator($event, $circle, $params);
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 * @throws InvalidItemException
	 */
	private function parseMemberAsMember(
		IEvent $event,
		Circle $circle,
		array $params
	): void {
		if (!key_exists('member', $params)) {
			return;
		}

		/** @var Member $member */
		$member = $this->deserializeJson($params['member'], Member::class);

		$this->parserMember->parseSubjectMemberJoin($event, $circle, $member);
		$this->parserMember->parseSubjectMemberAdd($event, $circle, $member);
		$this->parserMember->parseSubjectMemberLeft($event, $circle, $member);
		$this->parserMember->parseSubjectMemberRemove($event, $circle, $member);
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 * @throws InvalidItemException
	 */
	private function parseCircleMemberAsMember(
		IEvent $event,
		Circle $circle,
		array $params
	): void {
		if (!key_exists('member', $params)) {
			return;
		}

		/** @var Member $member */
		$member = $this->deserializeJson($params['member'], Member::class);

		$this->parserCircleMember->parseSubjectCircleMemberJoin($event, $circle, $member);
		$this->parserCircleMember->parseSubjectCircleMemberAdd($event, $circle, $member);
		$this->parserCircleMember->parseSubjectCircleMemberLeft($event, $circle, $member);
		$this->parserCircleMember->parseSubjectCircleMemberRemove($event, $circle, $member);
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param array $params
	 *
	 * @throws FakeException
	 * @throws InvalidItemException
	 */
	private function parseMemberAsModerator(
		IEvent $event,
		Circle $circle,
		array $params
	): void {
		if (!key_exists('member', $params)) {
			return;
		}

		/** @var Member $member */
		$member = $this->deserializeJson($params['member'], Member::class);

		$this->parserMember->parseMemberInvited($event, $circle, $member);
		$this->parserMember->parseMemberLevel($event, $circle, $member, $params['level'] ?? 0);
		$this->parserMember->parseMemberRequestInvitation($event, $circle, $member);
		$this->parserMember->parseMemberOwner($event, $circle, $member);
	}
}

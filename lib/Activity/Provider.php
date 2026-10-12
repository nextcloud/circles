<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Activity;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCP\Activity\Exceptions\UnknownActivityException;
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
		private ProviderSubjectCircleMember $parserCircleMember,
		private Deprecated\Provider $deprecatedProvider,
	) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null): IEvent {
		try {
			$params = $event->getSubjectParameters();
			$ver = $this->initActivityParser($event, $params);

			if ($ver === 1) {
				return $this->deprecatedProvider->parse($language, $event, $previousEvent);
			}

			$this->setIcon($event);
			$this->parseAsNonMember($event, $params);
			$this->parseAsMember($event, $params);
			$this->parseAsModerator($event, $params);
		} catch (FakeException|InvalidItemException) {
			/** clean exit */
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 */
	private function initActivityParser(IEvent $event, array $params): int {
		if ($event->getApp() !== Application::APP_ID) {
			throw new UnknownActivityException();
		}

		if (!key_exists('circle', $params)) {
			throw new UnknownActivityException();
		}

		return $params['ver'] ?? 1;
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
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseAsNonMember(
		IEvent $event,
		array $params,
	): void {
		if ($event->getType() !== 'circles_as_non_member') {
			return;
		}

		$this->parserCircle->parseSubjectCircleCreate($event, $params);
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseAsMember(
		IEvent $event,
		array $params,
	): void {
		if ($event->getType() !== 'circles_as_member') {
			return;
		}

		$this->parserCircle->parseSubjectCircleCreate($event, $params);
		$this->parserCircle->parseSubjectCircleDelete($event, $params);
		$this->parserCircle->parseSubjectCircleEdit($event, $params);
		$this->parseMemberAsMember($event, $params);
		$this->parseCircleMemberAsMember($event, $params);
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 * @throws InvalidItemException
	 */
	private function parseAsModerator(IEvent $event, array $params): void {
		if ($event->getType() !== 'circles_as_moderator') {
			return;
		}

		$this->parseMemberAsModerator($event, $params);
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseMemberAsMember(
		IEvent $event,
		array $params,
	): void {
		if (!array_key_exists('member', $params)) {
			return;
		}

		$this->parserMember->parseSubjectMemberJoin($event, $params);
		$this->parserMember->parseSubjectMemberAdd($event, $params);
		$this->parserMember->parseSubjectMemberLeft($event, $params);
		$this->parserMember->parseSubjectMemberRemove($event, $params);
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseCircleMemberAsMember(
		IEvent $event,
		array $params,
	): void {
		if (!array_key_exists('member', $params)) {
			return;
		}

		$this->parserCircleMember->parseSubjectCircleMemberJoin($event, $params);
		$this->parserCircleMember->parseSubjectCircleMemberAdd($event, $params);
		$this->parserCircleMember->parseSubjectCircleMemberLeft($event, $params);
		$this->parserCircleMember->parseSubjectCircleMemberRemove($event, $params);
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 * @throws InvalidItemException
	 */
	private function parseMemberAsModerator(
		IEvent $event,
		array $params,
	): void {
		if (!array_key_exists('member', $params)) {
			return;
		}

		$this->parserMember->parseMemberInvited($event, $params);
		$this->parserMember->parseMemberLevel($event, $params);
		$this->parserMember->parseMemberRequestInvitation($event, $params);
		$this->parserMember->parseMemberOwner($event, $params);
	}
}

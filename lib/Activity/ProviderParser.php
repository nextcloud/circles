<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Activity;

use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;

class ProviderParser {
	public function __construct(
		protected IURLGenerator $url,
		protected IManager $activityManager,
		protected IL10N $l10n,
	) {
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param string $ownEvent
	 * @param string $othersEvent
	 */
	protected function parseCircleEvent(
		IEvent $event,
		Circle $circle,
		string $ownEvent,
		string $othersEvent,
	): void {
		$data = [
			'author' => $this->generateViewerParameter($circle),
			'circle' => $this->generateCircleParameter($circle)
		];

		if ($this->isViewerTheAuthor($circle, $this->activityManager->getCurrentUserId())) {
			$this->setSubject($event, $ownEvent, $data);

			return;
		}

		$this->setSubject($event, $othersEvent, $data);
	}

	/**
	 * @param IEvent $event
	 * @param string $line
	 * @param array $data
	 */
	protected function setSubject(
		IEvent $event,
		string $line,
		array $data,
	): void {
		$this->setParsedSubject($event, $line, $data);
		$this->setRichSubject($event, $line, $data);
	}

	/**
	 * @param IEvent $event
	 * @param string $line
	 * @param array $data
	 */
	protected function setRichSubject(
		IEvent $event,
		string $line,
		array $data,
	): void {
		$ak = array_keys($data);
		foreach ($ak as $k) {
			$subAk = array_keys($data[$k]);
			foreach ($subAk as $sK) {
				if (str_starts_with($sK, '_')) {
					unset($data[$k][$sK]);
				}
			}
		}

		$event->setRichSubject($line, $data);
	}

	/**
	 * @param IEvent $event
	 * @param string $line
	 * @param array $data
	 */
	protected function setParsedSubject(
		IEvent $event,
		string $line,
		array $data,
	): void {
		$ak = array_keys($data);
		$replace = [];
		foreach ($ak as $k) {
			if (!key_exists('_parsed', $data[$k])) {
				continue;
			}

			$replace['{' . $k . '}'] = $data[$k]['_parsed'];
		}

		$event->setParsedSubject(strtr($line, $replace));
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @param string $ownEvent
	 * @param string $othersEvent
	 */
	protected function parseMemberEvent(
		IEvent $event,
		Circle $circle,
		Member $member,
		string $ownEvent,
		string $othersEvent,
	): void {
		$data = [
			'circle' => $this->generateCircleParameter($circle),
			'member' => $this->generateUserParameter($member)
		];

		if ($member->getUserId() === $this->activityManager->getCurrentUserId()
		) {
			$this->setSubject($event, $ownEvent, $data);

			return;
		}

		$this->setSubject($event, $othersEvent, $data);
	}

	/**
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @param string $ownEvent
	 * @param string $othersEvent
	 */
	protected function parseCircleMemberEvent(
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
	 * general function to generate Circle+Member advanced event.
	 *
	 * @param IEvent $event
	 * @param Circle $circle
	 * @param Member $member
	 * @param string $ownEvent
	 * @param string $targetEvent
	 * @param string $othersEvent
	 */
	protected function parseCircleMemberAdvancedEvent(
		IEvent $event,
		Circle $circle,
		Member $member,
		string $ownEvent,
		string $targetEvent,
		string $othersEvent,
	): void {
		$data = [
			'author' => $this->generateViewerParameter($circle),
			'circle' => $this->generateCircleParameter($circle),
			'member' => $this->generateUserParameter($member)
		];

		if ($this->isViewerTheAuthor($circle, $this->activityManager->getCurrentUserId())) {
			$this->setSubject($event, $ownEvent, $data);

			return;
		}

		if ($member->getUserId() === $this->activityManager->getCurrentUserId()) {
			$this->setSubject($event, $targetEvent, $data);

			return;
		}

		$this->setSubject($event, $othersEvent, $data);
	}

	/**
	 * @param Circle $circle
	 * @param string $userId
	 *
	 * @return bool
	 */
	protected function isViewerTheAuthor(Circle $circle, string $userId): bool {
		if (!$circle->hasInitiator()) {
			return false;
		}

		$initiator = $circle->getInitiator();

		return ($initiator->getUserType() === Member::TYPE_USER
			&& $initiator->getUserId() === $userId);
	}

	/**
	 * @param Circle $circle
	 *
	 * @return array <string,string|integer>
	 */
	protected function generateViewerParameter(Circle $circle): array {
		if (!$circle->hasInitiator()) {
			return [];
		}

		return $this->generateUserParameter($circle->getInitiator());
	}

	/**
	 * @param Member $member
	 *
	 * @return array <string,string|integer>
	 */
	protected function generateExternalMemberParameter(Member $member): array {
		return [
			'type' => 'email',
			'id' => $member->getUserId(),
			'link' => '',
			'name' => $member->getDisplayName(),
			'_parsed' => $member->getDisplayName()
		];
	}

	/**
	 * @param Circle $circle
	 *
	 * @return array<string,string|integer>
	 */
	protected function generateCircleParameter(Circle $circle): array {
		return [
			'type' => 'circle',
			'id' => $circle->getSingleId(),
			'name' => $circle->getName(),
			'_parsed' => $circle->getName(),
			'link' => $this->url->getAbsoluteURL($circle->getUrl()),
		];
	}

	/**
	 * @return array <string,string|integer>
	 */
	protected function generateUserParameter(IFederatedUser $member): array {
		$display = $member->getDisplayName();
		if ($display === '') {
			$display = $member->getUserId();
		}

		return [
			'type' => 'user',
			'id' => $member->getUserId(),
			'name' => $display,
			'_parsed' => $display
		];
	}

	/**
	 * @return array <string,string|integer>
	 */
	protected function generateGroupParameter(Member $group): array {
		return [
			'type' => 'user-group',
			'id' => $group->getUserId(),
			'name' => $group->getUserId(),
			'_parsed' => $group->getUserId()
		];
	}
}

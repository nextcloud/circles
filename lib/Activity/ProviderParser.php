<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Activity;

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

	protected function parseCircleEvent(
		IEvent $event,
		array $params,
		string $ownEvent,
		string $othersEvent,
	): void {
		$data = [
			'author' => $this->generateUserParameter($params['initiator'] ?? []),
			'circle' => $this->generateCircleParameter($params['circle'])
		];

		if ($this->isViewerTheAuthor($params['initiator'] ?? [], $this->activityManager->getCurrentUserId())) {
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
	 * @param array $params
	 * @param string $ownEvent
	 * @param string $othersEvent
	 */
	protected function parseMemberEvent(
		IEvent $event,
		array $params,
		string $ownEvent,
		string $othersEvent,
	): void {
		$data = [
			'circle' => $this->generateCircleParameter($params['circle']),
			'member' => $this->generateUserParameter($params['member'] ?? [])
		];

		if ($this->isViewerTheAuthor($params['member'] ?? [], $this->activityManager->getCurrentUserId())) {
			$this->setSubject($event, $ownEvent, $data);

			return;
		}

		$this->setSubject($event, $othersEvent, $data);
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 * @param string $ownEvent
	 * @param string $othersEvent
	 */
	protected function parseCircleMemberEvent(
		IEvent $event,
		array $params,
		string $ownEvent,
		string $othersEvent,
	): void {
		$data = [
			'author' => $this->generateUserParameter($params['initiator'] ?? []),
			'circle' => $this->generateCircleParameter($params['circle']),
			'member' => $this->generateUserParameter($params['member'] ?? []),
			'external' => $this->generateExternalMemberParameter($params['member'] ?? []),
			'group' => $this->generateGroupParameter($params['member'] ?? []),
		];

		if ($this->isViewerTheAuthor($params['initiator'] ?? [], $this->activityManager->getCurrentUserId())) {
			$this->setSubject($event, $ownEvent, $data);

			return;
		}

		$this->setSubject($event, $othersEvent, $data);
	}

	/**
	 * general function to generate Circle+Member advanced event.
	 *
	 * @param IEvent $event
	 * @param array $params
	 * @param string $ownEvent
	 * @param string $targetEvent
	 * @param string $othersEvent
	 */
	protected function parseCircleMemberAdvancedEvent(
		IEvent $event,
		array $params,
		string $ownEvent,
		string $targetEvent,
		string $othersEvent,
	): void {
		$data = [
			'author' => $this->generateUserParameter($params['initiator'] ?? []),
			'circle' => $this->generateCircleParameter($params['circle']),
			'member' => $this->generateUserParameter($params['member'] ?? [])
		];

		if ($this->isViewerTheAuthor($params['initiator'] ?? [], $this->activityManager->getCurrentUserId())) {
			$this->setSubject($event, $ownEvent, $data);

			return;
		}

		if ($this->isViewerTheAuthor($params['member'] ?? [], $this->activityManager->getCurrentUserId())) {
			$this->setSubject($event, $targetEvent, $data);

			return;
		}

		$this->setSubject($event, $othersEvent, $data);
	}

	/**
	 * @param array $params
	 * @param string $userId
	 *
	 * @return bool
	 */
	protected function isViewerTheAuthor(array $initiator, string $userId): bool {
		return (($initiator['type'] ?? 0) === Member::TYPE_USER && ($initiator['userId'] ?? '') === $userId);
	}


	/**
	 * @param array $member
	 *
	 * @return array <string,string|integer>
	 */
	protected function generateExternalMemberParameter(array $member): array {
		$data = $this->generateUserParameter($member);
		if (!empty($data)) {
			$data['link'] = '';
		}

		return $data;
	}

	/**
	 * @param array $circle
	 *
	 * @return array<string,string|integer>
	 */
	protected function generateCircleParameter(array $circle): array {
		return [
			'type' => 'circle',
			'id' => $circle['singleId'],
			'name' => $circle['name'],
			'_parsed' => $circle['name'],
			'link' => $this->url->getAbsoluteURL($circle['url']),
		];
	}

	/**
	 * @return array <string,string|integer>
	 */
	protected function generateUserParameter(array $member): array {
		if (!array_key_exists('userId', $member)) {
			return [];
		}

		return [
			'type' => 'user',
			'id' => $member['userId'],
			'name' => $member['displayName'] ?? $member['userId'],
			'_parsed' => $member['displayName'] ?? $member['userId'],
		];
	}

	/**
	 * @return array <string,string|integer>
	 */
	protected function generateGroupParameter(array $group): array {
		if (!array_key_exists('userId', $group)) {
			return [];
		}

		return [
			'type' => 'user-group',
			'id' => $group['userId'],
			'name' => $group['userId'],
			'_parsed' => $group['userId']
		];
	}
}

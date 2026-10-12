<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Activity;

use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\ConfigService;
use OCP\Activity\IEvent;

class ProviderSubjectCircle extends ProviderParser {
	private const SETTING_TEAM_TAB_ORDER = 'teamsTabOrder';
	private const SETTING_PASSWORD_SINGLE = 'password_single';
	private const SETTING_PASSWORD_SINGLE_ENABLED = 'password_single_enabled';
	private const CONFIG_ACTIVITY_SUBJECTS = [
		'config_open_enabled' => ['enabled', 'Anyone can request membership'],
		'config_open_disabled' => ['disabled', 'Anyone can request membership'],
		'config_invite_enabled' => ['enabled', 'Members need to accept invitation'],
		'config_invite_disabled' => ['disabled', 'Members need to accept invitation'],
		'config_request_enabled' => ['enabled', 'Memberships must be confirmed or accepted by a moderator'],
		'config_request_disabled' => ['disabled', 'Memberships must be confirmed or accepted by a moderator'],
		'config_friend_enabled' => ['enabled', 'Members can also invite'],
		'config_friend_disabled' => ['disabled', 'Members can also invite'],
		'config_root_enabled' => ['enabled', 'Prevent teams from being a member of another team'],
		'config_root_disabled' => ['disabled', 'Prevent teams from being a member of another team'],
		'config_federated_enabled' => ['enabled', 'Allow federated members'],
		'config_federated_disabled' => ['disabled', 'Allow federated members'],
		'config_visible_enabled' => ['enabled', 'Visible to everyone'],
		'config_visible_disabled' => ['disabled', 'Visible to everyone'],
	];

	public function parseSubjectCircleCreate(IEvent $event, array $params): void {
		if ($event->getSubject() !== 'circle_create') {
			return;
		}

		$this->parseCircleEvent(
			$event, $params,
			$this->l10n->t('You created the team {circle}'),
			$this->l10n->t('{author} created the team {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseSubjectCircleDelete(IEvent $event, array $params): void {
		if ($event->getSubject() !== 'circle_delete') {
			return;
		}

		$this->parseCircleEvent(
			$event, $params,
			$this->l10n->t('You deleted {circle}'),
			$this->l10n->t('{author} deleted {circle}')
		);

		throw new FakeException();
	}

	public function parseSubjectCircleEdit(IEvent $event, array $params): void {
		if ($event->getSubject() !== 'circle_edit') {
			return;
		}

		$change = (string)($params['change'] ?? 'details');
		if ($change === 'name' || $change === 'displayName') {
			$data = [
				'author' => $this->generateUserParameter($params['initiator'] ?? []),
				'title' => $this->generateHighlightParameter((string)($params['title'] ?? '')),
			];
			if ($this->isViewerTheAuthor($params['initiator'] ?? [], $this->activityManager->getCurrentUserId())) {
				$this->setSubject($event, $this->l10n->t('You changed the team title to {title}'), $data);
			} else {
				$this->setSubject($event, $this->l10n->t('{author} changed the team title to {title}'), $data);
			}

			throw new FakeException();
		}

		if ($change === 'description') {
			$this->parseCircleEvent(
				$event,
				$params,
				$this->l10n->t('You changed the description of {circle}'),
				$this->l10n->t('{author} changed the description of {circle}'),
			);

			throw new FakeException();
		}

		$this->parseCircleEvent(
			$event, $params,
			$this->l10n->t('You edited the team {circle}'),
			$this->l10n->t('{author} edited the team {circle}')
		);

		throw new FakeException();
	}

	public function parseSubjectCircleSettingChanged(IEvent $event, array $params): void {
		if ($event->getSubject() !== 'circle_setting_changed') {
			return;
		}

		$setting = (string)($params['setting'] ?? '');
		if (isset(self::CONFIG_ACTIVITY_SUBJECTS[$setting])) {
			[$state, $label] = self::CONFIG_ACTIVITY_SUBJECTS[$setting];
			$data = [
				'author' => $this->generateUserParameter($params['initiator'] ?? []),
				'circle' => $this->generateCircleParameter($params['circle']),
				'state' => $this->generateHighlightParameter($state),
				'setting' => $this->generateHighlightParameter($label),
			];
			if ($this->isViewerTheAuthor($params['initiator'] ?? [], $this->activityManager->getCurrentUserId())) {
				$this->setSubject($event, $this->l10n->t('You {state} "{setting}" for {circle}'), $data);
			} else {
				$this->setSubject($event, $this->l10n->t('{author} {state} "{setting}" for {circle}'), $data);
			}

			throw new FakeException();
		}

		[$ownEvent, $othersEvent] = $this->getSettingActivitySubjects($setting);

		$this->parseCircleEvent(
			$event, $params,
			$ownEvent,
			$othersEvent,
		);

		throw new FakeException();
	}

	/** @return array{string, string} */
	private function getSettingActivitySubjects(string $setting): array {
		return match ($setting) {
			Circle::SETTING_TEAM_FOLDER_QUOTA => [
				$this->l10n->t('You changed the Team Folder quota of {circle}'),
				$this->l10n->t('{author} changed the Team Folder quota of {circle}'),
			],
			self::SETTING_TEAM_TAB_ORDER => [
				$this->l10n->t('You changed the navigation order of {circle}'),
				$this->l10n->t('{author} changed the navigation order of {circle}'),
			],
			ConfigService::MEMBERS_LIMIT => [
				$this->l10n->t('You changed the member limit of {circle}'),
				$this->l10n->t('{author} changed the member limit of {circle}'),
			],
			ConfigService::ENFORCE_PASSWORD, self::SETTING_PASSWORD_SINGLE_ENABLED => [
				$this->l10n->t('You changed the password protection of {circle}'),
				$this->l10n->t('{author} changed the password protection of {circle}'),
			],
			self::SETTING_PASSWORD_SINGLE => [
				$this->l10n->t('You changed the password of {circle}'),
				$this->l10n->t('{author} changed the password of {circle}'),
			],
			default => [
				$this->l10n->t('You changed the settings of {circle}'),
				$this->l10n->t('{author} changed the settings of {circle}'),
			],
		};
	}
}

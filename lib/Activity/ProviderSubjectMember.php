<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Activity;

use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\Activity\IEvent;

class ProviderSubjectMember extends ProviderParser {
	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberJoin(
		IEvent $event,
		array $params,
	): void {
		if ($event->getSubject() !== 'member_join') {
			return;
		}

		$this->parseSubjectMemberJoinOnInvite($event, $params);
		$this->parseCircleMemberEvent(
			$event, $params, $this->l10n->t('You joined {circle}'),
			$this->l10n->t('{member} joined {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberJoinOnInvite(
		IEvent $event,
		array $params,
	): void {
		if ((($params['circle']['config'] ?? 0) & Circle::CFG_INVITE) === 0) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $params,
			$this->l10n->t('You accepted the invitation to join {circle}'),
			$this->l10n->t('{member} accepted the invitation to join {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberAdd(
		IEvent $event,
		array $params,
	): void {
		if ($event->getSubject() !== 'member_added') {
			return;
		}

		$this->parseSubjectMemberAddNotLocalMember($event, $params);
		$this->parseSubjectMemberAddClosedCircle($event, $params);
		$this->parseCircleMemberAdvancedEvent(
			$event, $params,
			$this->l10n->t('You added {member} as member to {circle}'),
			$this->l10n->t('You have been added as member to {circle} by {author}'),
			$this->l10n->t('{member} has been added as member to {circle} by {author}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberAddNotLocalMember(
		IEvent $event,
		array $params,
	): void {
		if (($params['member']['type'] ?? Member::TYPE_USER) === Member::TYPE_USER) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $params,
			$this->l10n->t('You added {external} to {circle}'),
			$this->l10n->t('{external} has been added to {circle} by {author}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberAddClosedCircle(
		IEvent $event,
		array $params,
	): void {
		if ((($params['circle']['config'] ?? 0) & Circle::CFG_REQUEST) === 0) {
			return;
		}

		$this->parseCircleMemberAdvancedEvent(
			$event, $params,
			$this->l10n->t("You accepted {member}'s request to join {circle}"),
			$this->l10n->t('Your request to join {circle} has been accepted by {author}'),
			$this->l10n->t("{member}'s request to join {circle} has been accepted by {author}")
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberLeft(
		IEvent $event,
		array $params,
	): void {
		if ($event->getSubject() !== 'member_left') {
			return;
		}

		$this->parseSubjectNonMemberLeftInvite($event, $params);
		$this->parseSubjectNonMemberLeftRequest($event, $params);
		$this->parseCircleMemberEvent(
			$event, $params,
			$this->l10n->t('You left {circle}'),
			$this->l10n->t('{member} left {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseSubjectNonMemberLeftInvite(
		IEvent $event,
		array $params,
	): void {
		if (((($params['circle']['config'] ?? 0) & Circle::CFG_INVITE) === 0)
			|| (($params['member']['level'] ?? 1) > Member::LEVEL_NONE)) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $params,
			$this->l10n->t('You declined the invitation to join {circle}'),
			$this->l10n->t('{member} declined an invitation to join {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseSubjectNonMemberLeftRequest(
		IEvent $event,
		array $params,
	): void {
		if (((($params['circle']['config'] ?? 0) & Circle::CFG_REQUEST) === 0)
			|| (($params['member']['level'] ?? 1) > Member::LEVEL_NONE)) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $params,
			$this->l10n->t('You cancelled your request to join {circle}'),
			$this->l10n->t('{member} cancelled a request to join {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseSubjectMemberRemove(
		IEvent $event,
		array $params,
	): void {
		if ($event->getSubject() !== 'member_remove') {
			return;
		}

		$this->parseSubjectMemberRemoveNotLocalMember($event, $params);
		$this->parseSubjectMemberRemoveNotYetMember($event, $params);
		$this->parseCircleMemberAdvancedEvent(
			$event, $params,
			$this->l10n->t('You removed {member} from {circle}'),
			$this->l10n->t('You have been removed from {circle} by {author}'),
			$this->l10n->t('{member} has been removed from {circle} by {author}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberRemoveNotLocalMember(
		IEvent $event,
		array $params,
	): void {
		if (($params['member']['type'] ?? Member::TYPE_USER) === Member::TYPE_USER) {
			return;
		}

		$this->parseCircleMemberEvent(
			$event, $params,
			$this->l10n->t('You removed {external} from {circle}'),
			$this->l10n->t('{external} has been removed from {circle} by {author}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	private function parseSubjectMemberRemoveNotYetMember(
		IEvent $event,
		array $params,
	): void {
		if (((($params['circle']['config'] ?? 0) & Circle::CFG_INVITE) === 0)
			|| (($params['member']['level'] ?? 1) > Member::LEVEL_NONE)) {
			return;
		}

		$this->parseSubjectMemberRemoveNotYetMemberRequesting($event, $params);
		$this->parseCircleMemberAdvancedEvent(
			$event, $params,
			$this->l10n->t("You cancelled {member}'s invitation to join {circle}"),
			$this->l10n->t('Your invitation to join {circle} has been cancelled by {author}'),
			$this->l10n->t("{author} cancelled {member}'s invitation to join {circle}")
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 * @throws FakeException
	 */
	private function parseSubjectMemberRemoveNotYetMemberRequesting(
		IEvent $event,
		array $params,
	): void {
		if (($params['member']['status'] ?? '') !== Member::STATUS_REQUEST) {
			return;
		}

		$this->parseCircleMemberAdvancedEvent(
			$event, $params,
			$this->l10n->t("You dismissed {member}'s request to join {circle}"),
			$this->l10n->t('Your request to join {circle} has been dismissed by {author}'),
			$this->l10n->t("{member}'s request to join {circle} has been dismissed by {author}")
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseMemberInvited(IEvent $event, array $params): void {
		if ($event->getSubject() !== 'member_invited') {
			return;
		}

		$this->parseCircleMemberAdvancedEvent(
			$event, $params,
			$this->l10n->t('You invited {member} to join {circle}'),
			$this->l10n->t('You have been invited to join {circle} by {author}'),
			$this->l10n->t('{member} has been invited to join {circle} by {author}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseMemberLevel(
		IEvent $event,
		array $params,
	): void {
		if ($event->getSubject() !== 'member_level') {
			return;
		}

		$level = $params['level'] ?? 0;
		$levelString = $this->l10n->t(Member::$DEF_LEVEL[$level] ?? '');
		$this->parseCircleMemberAdvancedEvent(
			$event, $params,
			$this->l10n->t('You changed {member}\'s level in {circle} to %1$s', [$levelString]),
			$this->l10n->t('{author} changed your level in {circle} to %1$s', [$levelString]),
			$this->l10n->t('{author} changed {member}\'s level in {circle} to %1$s', [$levelString])
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseMemberRequestInvitation(
		IEvent $event,
		array $params,
	): void {
		if ($event->getSubject() !== 'member_request_invitation') {
			return;
		}

		$this->parseMemberEvent(
			$event, $params,
			$this->l10n->t('You sent a request to join {circle}'),
			$this->l10n->t('{member} sent a request to join {circle}')
		);

		throw new FakeException();
	}

	/**
	 * @param IEvent $event
	 * @param array $params
	 *
	 * @throws FakeException
	 */
	public function parseMemberOwner(
		IEvent $event,
		array $params,
	): void {
		if ($event->getSubject() !== 'member_owner') {
			return;
		}

		$this->parseMemberEvent(
			$event, $params,
			$this->l10n->t('You are the new owner of {circle}'),
			$this->l10n->t('{member} is the new owner of {circle}')
		);
		throw new FakeException();
	}
}

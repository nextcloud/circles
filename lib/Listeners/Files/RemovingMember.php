<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners\Files;

use OCA\Circles\Events\RemovingCircleMemberEvent;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<RemovingCircleMemberEvent|Event> */
class RemovingMember implements IEventListener {
	use TStringTools;

	private LoggerInterface $logger;
	private MemberService $memberService;
	private ShareTokenService $shareTokenService;
	private ShareWrapperService $shareWrapperService;

	public function __construct(
		LoggerInterface $logger,
		MemberService $memberService,
		ShareTokenService $shareTokenService,
		ShareWrapperService $shareWrapperService,
	) {
		$this->logger = $logger;
		$this->memberService = $memberService;
		$this->shareTokenService = $shareTokenService;
		$this->shareWrapperService = $shareWrapperService;
	}

	public function handle(Event $event): void {
		if (!$event instanceof RemovingCircleMemberEvent) {
			return;
		}

		$member = $event->getMember();

		if ($member->getUserType() === Member::TYPE_CIRCLE) {
			$members = $member->getBasedOn()->getInheritedMembers();
		} else {
			$members = [$member];
		}

		$circle = $event->getCircle();
		$singleIds = array_merge(
			[$circle->getSingleId()],
			array_map(
				function (Membership $membership) {
					return $membership->getCircleId();
				}, $circle->getMemberships()
			)
		);

		/** @var Member[] $members */
		foreach ($members as $member) {
			if ($member->getUserType() === Member::TYPE_MAIL
				|| $member->getUserType() === Member::TYPE_CONTACT
			) {
				$this->removingSharesExternalMember($member, $singleIds);
				continue;
			}

			if ($member->getUserType() === Member::TYPE_USER) {
				$this->removingSharesInternalMember($member, $singleIds);
			}
		}
	}


	/**
	 * @param Member $member
	 * @param string[] $singleIds
	 */
	private function removingSharesExternalMember(Member $member, array $singleIds) {
		foreach ($singleIds as $singleId) {
			try {
				$member->getLink($singleId);
				continue;
			} catch (MembershipNotFoundException $e) {
			}

			$this->shareTokenService->removeTokens($member->getSingleId(), $singleId);
		}
	}


	/**
	 * @param Member $member
	 * @param string[] $singleIds
	 */
	private function removingSharesInternalMember(Member $member, array $singleIds) {
		foreach ($singleIds as $singleId) {
			try {
				$member->getLink($singleId);
				continue;
			} catch (MembershipNotFoundException $e) {
			}

			try {
				$this->shareWrapperService->deleteUserSharesToCircle($singleId, $member->getUserId());
			} catch (\Exception $e) {
				$this->logger->notice('issue while deleting user shares: ' . $e->getMessage());
			}
		}
	}
}

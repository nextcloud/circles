<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners\Files;

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MountRequest;
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
	private MemberRequest $memberRequest;
	private MountRequest $mountRequest;

	public function __construct(
		LoggerInterface $logger,
		MemberService $memberService,
		ShareTokenService $shareTokenService,
		ShareWrapperService $shareWrapperService,
		MemberRequest $memberRequest,
		MountRequest $mountRequest,
	) {
		$this->logger = $logger;
		$this->memberService = $memberService;
		$this->shareTokenService = $shareTokenService;
		$this->shareWrapperService = $shareWrapperService;
		$this->memberRequest = $memberRequest;
		$this->mountRequest = $mountRequest;
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
				$this->removingMountsIfLastLocalMember($member, $singleIds);
			}
		}
	}


	/**
	 * A circle mount is created on the local instance when a file or folder is
	 * shared on the remote instance with a circle that lives there but also has
	 * members on the local instance. This mount is used by every member of the
	 * local instance who belongs to that remote circle.
	 *
	 * Removing a share on a remote circle only propagates to instances that still
	 * have a member in that circle. Once the last local member leaves, the local
	 * instance stops receiving that propagation, so we have to remove the mount
	 * at this point.
	 *
	 * We have to iterate through every parent circle this member's circle belongs
	 * to, checking for circles where this member leaving would be the last local
	 * member leaving that circle, and thus where the mount must be removed.
	 *
	 * @param Member $member the member being removed
	 * @param string[] $singleIds parent circles this member's circle belongs to
	 */
	private function removingMountsIfLastLocalMember(Member $member, array $singleIds): void {
		if (!$member->isLocal()) {
			return;
		}

		foreach ($singleIds as $singleId) {
			if (!$this->mountRequest->hasMountForCircleId($singleId)) {
				continue;
			}

			foreach ($this->memberRequest->getInheritedMembers($singleId) as $remainingMember) {
				if ($remainingMember->getUserType() === Member::TYPE_USER && $remainingMember->isLocal()) {
					continue 2;
				}
			}

			$this->mountRequest->deleteByCircleId($singleId);
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

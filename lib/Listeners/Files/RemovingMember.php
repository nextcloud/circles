<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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

/**
 * Class RemovingMember
 *
 * @package OCA\Circles\Listeners\Files
 */
class RemovingMember implements IEventListener {
	use TStringTools;

	private LoggerInterface $logger;
	private MemberService $memberService;
	private ShareTokenService $shareTokenService;
	private ShareWrapperService $shareWrapperService;

	/**
	 * RemovingMember constructor.
	 *
	 * @param LoggerInterface $logger
	 * @param MemberService $memberService
	 * @param ShareTokenService $shareTokenService
	 * @param ShareWrapperService $shareWrapperService
	 */
	public function __construct(
		LoggerInterface $logger,
		MemberService $memberService,
		ShareTokenService $shareTokenService,
		ShareWrapperService $shareWrapperService
	) {
		$this->logger = $logger;
		$this->memberService = $memberService;
		$this->shareTokenService = $shareTokenService;
		$this->shareWrapperService = $shareWrapperService;
	}


	/**
	 * @param Event $event
	 */
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

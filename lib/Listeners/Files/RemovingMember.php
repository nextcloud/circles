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

use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use ArtificialOwl\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\RemovingCircleMemberEvent;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\ShareTokenService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class RemovingMember
 *
 * @package OCA\Circles\Listeners\Files
 */
class RemovingMember implements IEventListener {
	use TStringTools;
	use TNC22Logger;


	/** @var MemberService */
	private $memberService;

	/** @var ShareTokenService */
	private $shareTokenService;


	/**
	 * RemovingMember constructor.
	 *
	 * @param MemberService $memberService
	 * @param ShareTokenService $shareTokenService
	 */
	public function __construct(
		MemberService $memberService,
		ShareTokenService $shareTokenService
	) {
		$this->memberService = $memberService;
		$this->shareTokenService = $shareTokenService;

		$this->setup('app', Application::APP_ID);
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
			if ($member->getUserType() !== Member::TYPE_MAIL
				&& $member->getUserType() !== Member::TYPE_CONTACT
			) {
				continue;
			}

			foreach ($singleIds as $singleId) {
				try {
					$member->getMembership($singleId);
					continue;
				} catch (MembershipNotFoundException $e) {
				}

				$this->shareTokenService->removeTokens($member->getSingleId(), $singleId);
			}
		}
	}
}

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


namespace OCA\Circles\Listeners\Examples;

use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\ConfigService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class ExampleAddingCircleMember
 *
 * @package OCA\Circles\Listeners\Files
 */
class ExampleAddingCircleMember implements IEventListener {
	use TNCLogger;


	/** @var ConfigService */
	private $configService;


	/**
	 * ExampleAddingCircleMember constructor.
	 *
	 * @param ConfigService $configService
	 */
	public function __construct(ConfigService $configService) {
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!$event instanceof AddingCircleMemberEvent) {
			return;
		}

		if ($this->configService->getAppValue(ConfigService::EVENT_EXAMPLES) !== '1') {
			return;
		}

		$prefix = '[Example Event] (ExampleAddingCircleMember) ';

		$circle = $event->getCircle();
		$member = $event->getMember();

		$eventType = ($event->getType() === CircleGenericEvent::INVITED) ? 'invited' : 'joined';

		$info = 'A new member have been added (' . $eventType . ') to a Circle. ';

		$info .= 'userId: ' . $member->getUserId() . '; userType: ' . Member::$TYPE[$member->getUserType()]
				 . '; singleId: ' . $member->getSingleId() . '; memberId: ' . $member->getId()
				 . '; isLocal: ' . json_encode($member->isLocal()) . '; level: '
				 . Member::$DEF_LEVEL[$member->getLevel()] . '; ';

		$memberships = array_map(
			function (Membership $membership) {
				return $membership->getCircleId();
			}, $circle->getMemberships()
		);

		$listMemberships = (count($memberships) > 0) ? implode(', ', $memberships) : 'none';
		$info .= 'circleName: ' . $circle->getDisplayName() . '; circleId: ' . $circle->getSingleId()
				 . '; Circle memberships: ' . $listMemberships . '.';

		if ($member->getUserType() === Member::TYPE_CIRCLE) {
			$basedOn = $member->getBasedOn();
			$members = array_map(
				function (Member $member) {
					return $member->getUserId() . ' (' . Member::$TYPE[$member->getUserType()] . ')';
				}, $basedOn->getInheritedMembers()
			);

			$info .= ' Member is a Circle (singleId: ' . $basedOn->getSingleId()
					 . ') that contains those inherited members: ' . implode(', ', $members);
		}

		$this->log(3, $prefix . $info);
	}
}

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
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class ExampleRequestingCircleMember
 *
 * @package OCA\Circles\Listeners\Examples
 */
class ExampleRequestingCircleMember implements IEventListener {
	use TNCLogger;


	/** @var ConfigService */
	private $configService;


	/**
	 * ExampleRequestingCircleMember constructor.
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
		if (!$event instanceof RequestingCircleMemberEvent) {
			return;
		}

		if ($this->configService->getAppValue(ConfigService::EVENT_EXAMPLES) !== '1') {
			return;
		}

		$prefix = '[Example Event] (ExampleRequestingCircleMember) ';

		if ($event->getType() === CircleGenericEvent::INVITED) {
			$info = 'A new member have been invited to a Circle. ';
		} else {
			$info = 'A new member is requesting to join a Circle. ';
		}

		$circle = $event->getCircle();
		$member = $event->getMember();
		$info .= 'circleId: ' . $circle->getSingleId() . '; userId: ' . $member->getUserId() . '; userType: '
				 . Member::$TYPE[$member->getUserType()] . '; singleId: ' . $member->getSingleId()
				 . '; memberId: ' . $member->getId() . '; isLocal: ' . json_encode($member->isLocal()) . '; ';

		$this->log(3, $prefix . $info);
	}
}

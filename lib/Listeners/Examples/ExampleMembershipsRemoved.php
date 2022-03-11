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
use OCA\Circles\Tools\Traits\TStringTools;
use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\CirclesManager;
use OCA\Circles\Events\MembershipsRemovedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\ConfigService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class ExampleMembershipsRemoved
 *
 * @package OCA\Circles\Listeners\Files
 */
class ExampleMembershipsRemoved implements IEventListener {
	use TStringTools;
	use TNCLogger;


	/** @var CirclesManager */
	private $circlesManager;

	/** @var ConfigService */
	private $configService;


	/**
	 * ExampleMembershipsRemoved constructor.
	 *
	 * @param CirclesManager $circlesManager
	 * @param ConfigService $configService
	 */
	public function __construct(
		CirclesManager $circlesManager,
		ConfigService $configService
	) {
		$this->circlesManager = $circlesManager;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!$event instanceof MembershipsRemovedEvent) {
			return;
		}

		if ($this->configService->getAppValue(ConfigService::EVENT_EXAMPLES) !== '1') {
			return;
		}

		$prefix = '[Example Event] (ExampleMembershipsRemoved) ';

		$memberships = array_map(
			function (Membership $membership) {
				$inheritance = ($membership->getInheritanceDepth() > 1) ?
					'an inherited member' : 'a direct member';
				try {
					$federatedUser = $this->circlesManager->getFederatedUser($membership->getSingleId());
				} catch (Exception $e) {
					$this->e($e);

					return $membership->getSingleId() . ' is not ' . $inheritance . ' of '
						   . $membership->getCircleId() . ' anymore';
				}

				return $federatedUser->getUserId() . ' (' . Member::$TYPE[$federatedUser->getUserType()]
					   . ') is not ' . $inheritance . ' of ' . $membership->getCircleId() . ' anymore';
			}, $event->getMemberships()
		);

		$this->log(3, $prefix . implode('. ', $memberships));
	}
}

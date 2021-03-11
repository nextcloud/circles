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

namespace OCA\Circles\Service;


use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\GroupNotFoundException;
use OCA\Circles\FederatedItems\CircleCreate;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCP\IGroupManager;


/**
 * Class GroupService
 *
 * @package OCA\Circles\Service
 */
class GroupService {


	use TStringTools;


	const SOURCE = 'NextcloudGroups';


	/** @var IGroupManager */
	private $groupManager;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var FederatedEventService */
	private $federatedEventService;


	/**
	 * GroupService constructor.
	 *
	 * @param IGroupManager $groupManager
	 * @param CircleRequest $circleRequest
	 * @param FederatedUserService $federatedUserService
	 * @param FederatedEventService $federatedEventService
	 */
	public function __construct(
		IGroupManager $groupManager, CircleRequest $circleRequest, FederatedUserService $federatedUserService,
		FederatedEventService $federatedEventService
	) {
		$this->groupManager = $groupManager;
		$this->circleRequest = $circleRequest;
		$this->federatedUserService = $federatedUserService;
		$this->federatedEventService = $federatedEventService;
	}


	/**
	 * @param string $groupId
	 *
	 * @return Circle
	 * @throws GroupNotFoundException
	 */
	public function getGroupCircle(string $groupId): Circle {
		$group = $this->groupManager->get($groupId);
		if ($group === null) {
			throw new GroupNotFoundException('group not found');
		}

		$this->federatedUserService->setLocalCurrentApp(Application::APP_ID);
		$owner = $this->federatedUserService->getCurrentApp();

		$circle = new Circle();
		$circle->setName('group:' . $groupId);
		$circle->setConfig(Circle::CFG_SYSTEM | Circle::CFG_NO_OWNER | Circle::CFG_HIDDEN);
		$circle->setId($this->token(ManagedModel::ID_LENGTH));

		$member = new Member();
		$member->importFromIFederatedUser($owner);
		$member->setId($this->token(ManagedModel::ID_LENGTH))
			   ->setCircleId($circle->getId())
			   ->setLevel(Member::LEVEL_OWNER)
			   ->setStatus(Member::STATUS_MEMBER);
		$circle->setOwner($member)
			   ->setInitiator($member);

		try {
			return $this->circleRequest->searchCircle($circle);
		} catch (CircleNotFoundException $e) {
		}

		$circle->setDisplayName($groupId);



		$event = new FederatedEvent(CircleCreate::class);
		$event->setCircle($circle);
		$this->federatedEventService->newEvent($event);

		return $circle;
	}

}


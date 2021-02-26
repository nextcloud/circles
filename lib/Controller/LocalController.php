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


namespace OCA\Circles\Controller;


use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Controller;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Deserialize;
use Exception;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;


/**
 * Class LocalController
 *
 * @package OCA\Circles\Controller
 */
class LocalController extends OcsController {


	use TNC21Deserialize;
	use TNC21Controller;


	/** @var IUserSession */
	private $userSession;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	protected $configService;


	/**
	 * BaseController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		$appName, IRequest $request, IUserSession $userSession, FederatedUserService $federatedUserService,
		CircleService $circleService, MemberService $memberService, ConfigService $configService
	) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->configService = $configService;
	}


	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 * @throws CircleNotFoundException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws InitiatorNotFoundException
	 */
	public function circles(): DataResponse {
		$this->setCurrentFederatedUser();

		return $this->success($this->circleService->getCircles(), false);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $name
	 * @param bool $personal
	 *
	 * @return DataResponse
	 * @throws CircleNotFoundException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 */
	public function create(string $name, bool $personal = false): DataResponse {
		$this->setCurrentFederatedUser();

		try {
			$result = $this->circleService->create($name);

			return $this->successObj($result);
		} catch (Exception $e) {
			return $this->fail($e);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 * @throws CircleNotFoundException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 */
	public function members(string $circleId): DataResponse {
		$this->setCurrentFederatedUser();

		try {
			$members = $this->memberService->getMembers($circleId);

			return $this->success($members);
		} catch (Exception $e) {
			return $this->fail($e);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $userId
	 * @param int $type
	 *
	 * @return DataResponse
	 * @throws CircleNotFoundException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 */
	public function memberAdd(string $circleId, string $userId, int $type): DataResponse {
		$this->setCurrentFederatedUser();

		try {
			$member = $this->federatedUserService->generateFederatedUser($userId, (int)$type);
			$result = $this->memberService->addMember($circleId, $member);

			return $this->successObj($result);
		} catch (Exception $e) {
			return $this->fail($e);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $memberId
	 * @param string $level
	 *
	 * @return DataResponse
	 * @throws CircleNotFoundException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws MemberLevelException
	 */
	public function memberLevel(string $circleId, string $memberId, string $level): DataResponse {
		$this->setCurrentFederatedUser();

		try {
			$level = Member::parseLevelString($level);
			$this->memberService->getMember($memberId, $circleId);
			$result = $this->memberService->memberLevel($memberId, $level);

			return $this->successObj($result);
		} catch (Exception $e) {
			return $this->fail($e);
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $memberId
	 *
	 * @return DataResponse
	 * @throws CircleNotFoundException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 */
	public function memberRemove(string $circleId, string $memberId): DataResponse {
		$this->setCurrentFederatedUser();

		try {
			$this->memberService->getMember($memberId, $circleId);
			$result = $this->memberService->removeMember($memberId);

			return $this->successObj($result);
		} catch (Exception $e) {
			return $this->fail($e);
		}
	}


	/**
	 * @throws CircleNotFoundException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 */
	private function setCurrentFederatedUser() {
		$user = $this->userSession->getUser();
		$this->federatedUserService->setLocalCurrentUser($user);
	}

}


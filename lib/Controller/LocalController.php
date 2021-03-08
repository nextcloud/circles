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


use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Deserialize;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Logger;
use Exception;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\SearchService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
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
	use TNC21Logger;


	/** @var IUserSession */
	private $userSession;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var SearchService */
	private $searchService;

	/** @var ConfigService */
	protected $configService;


	/**
	 * LocalController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param SearchService $searchService
	 * @param ConfigService $configService
	 */
	public function __construct(
		string $appName, IRequest $request, IUserSession $userSession,
		FederatedUserService $federatedUserService, CircleService $circleService,
		MemberService $memberService, SearchService $searchService, ConfigService $configService
	) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->searchService = $searchService;
		$this->configService = $configService;

		$this->setup('app', 'circles');
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $name
	 * @param bool $personal
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function create(string $name, bool $personal = false): DataResponse {
		try {
			$this->setCurrentFederatedUser();
			$circle = $this->circleService->create($name, null, $personal);

			return new DataResponse($circle->jsonSerialize());
		} catch (Exception $e) {
			throw new OcsException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $term
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function search(string $term): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			return new DataResponse($this->searchService->search($needle));
		} catch (Exception $e) {
			throw new OcsException($e->getMessage(), $e->getCode());
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
	 * @throws OCSException
	 */
	public function memberAdd(string $circleId, string $userId, int $type): DataResponse {
		try {
			$this->setCurrentFederatedUser();
			$member = $this->federatedUserService->generateFederatedUser($userId, (int)$type);
			$result = $this->memberService->addMember($circleId, $member);

			return new DataResponse($result->jsonSerialize());
		} catch (Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function circleJoin(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();
			$result = $this->circleService->circleJoin($circleId);

			return new DataResponse($result->jsonSerialize());
		} catch (Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
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
	 * @throws OCSException
	 */
	public function memberLevel(string $circleId, string $memberId, string $level): DataResponse {
		try {
			$this->setCurrentFederatedUser();
			$level = Member::parseLevelString($level);
			$this->memberService->getMember($memberId, $circleId);
			$result = $this->memberService->memberLevel($memberId, $level);

			return new DataResponse($result->jsonSerialize());
		} catch (Exception $e) {
			throw new OcsException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $memberId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function memberRemove(string $circleId, string $memberId): DataResponse {
		try {
			$this->setCurrentFederatedUser();
			$this->memberService->getMember($memberId, $circleId);
			$result = $this->memberService->removeMember($memberId);

			return new DataResponse($result->jsonSerialize());
		} catch (Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function circles(): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			return new DataResponse($this->circleService->getCircles());
		} catch (Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function members(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			return new DataResponse($this->memberService->getMembers($circleId));
		} catch (Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws FederatedUserException
	 * @throws SingleCircleNotFoundException
	 */
	private function setCurrentFederatedUser() {
		$user = $this->userSession->getUser();
		$this->federatedUserService->setLocalCurrentUser($user);
	}

}


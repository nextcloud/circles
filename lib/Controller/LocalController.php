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

use Exception;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\FrontendException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\BasicProbe;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\SearchService;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;
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
class LocalController extends OCSController {
	use TDeserialize;
	use TNCLogger;


	/** @var IUserSession */
	private $userSession;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var MembershipService */
	private $membershipService;

	/** @var PermissionService */
	private $permissionService;

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
	 * @param MembershipService $membershipService
	 * @param SearchService $searchService
	 * @param ConfigService $configService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		IUserSession $userSession,
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MemberService $memberService,
		MembershipService $membershipService,
		PermissionService $permissionService,
		SearchService $searchService,
		ConfigService $configService
	) {
		parent::__construct($appName, $request);

		$this->userSession = $userSession;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->membershipService = $membershipService;
		$this->permissionService = $permissionService;
		$this->searchService = $searchService;
		$this->configService = $configService;

		$this->setup('app', 'circles');
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $name
	 * @param bool $personal
	 * @param bool $local
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function create(string $name, bool $personal = false, bool $local = false): DataResponse {
		try {
			$this->setCurrentFederatedUser();
			$this->permissionService->confirmCircleCreation();

			$circle = $this->circleService->create($name, null, $personal, $local);

			return new DataResponse($this->serializeArray($circle));
		} catch (Exception $e) {
			$this->e($e, ['name' => $name, 'members' => $personal, 'local' => $local]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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
	public function destroy(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$circle = $this->circleService->destroy($circleId);

			return new DataResponse($this->serializeArray($circle));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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

			return new DataResponse($this->serializeArray($this->searchService->search($term)));
		} catch (Exception $e) {
			$this->e($e, ['term' => $term]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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
	public function circleDetails(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$probe = new CircleProbe();
			$probe->includeNonVisibleCircles();

			return new DataResponse($this->serialize($this->circleService->getCircle($circleId, $probe)));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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

			// exception in Contact
			if ($type === Member::TYPE_CONTACT) {
				$currentUser = $this->federatedUserService->getCurrentUser();
				if (!$this->configService->isLocalInstance($currentUser->getInstance())) {
					throw new OCSException('works only from local instance', 404);
				}

				$userId = $currentUser->getUserId() . '/' . $userId;
			}

			$federatedUser = $this->federatedUserService->generateFederatedUser($userId, $type);
			$result = $this->memberService->addMember($circleId, $federatedUser);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'userId' => $userId, 'type' => $type]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param array $members
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function membersAdd(string $circleId, array $members): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$federatedUsers = [];
			foreach ($members as $member) {
				// TODO: generate Multiple FederatedUsers using a single SQL request
				$federatedUsers[] = $this->federatedUserService->generateFederatedUser(
					$this->get('id', $member),
					$this->getInt('type', $member)
				);
			}

			$result = $this->memberService->addMembers($circleId, $federatedUsers);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'members' => $members]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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
	public function circleLeave(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$result = $this->circleService->circleLeave($circleId);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $memberId
	 * @param string|int $level
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function memberLevel(string $circleId, string $memberId, $level): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			if (is_int($level)) {
				$level = Member::parseLevelInt($level);
			} else {
				$level = Member::parseLevelString($level);
			}

			$this->memberService->getMemberById($memberId, $circleId);
			$result = $this->memberService->memberLevel($memberId, $level);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'memberId' => $memberId, 'level' => $level]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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
	public function memberConfirm(string $circleId, string $memberId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$member = $this->memberService->getMemberById($memberId, $circleId);
			$federatedUser = new FederatedUser();
			$federatedUser->importFromIFederatedUser($member);

			$result = $this->memberService->addMember($circleId, $federatedUser);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'memberId' => $memberId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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

			$this->memberService->getMemberById($memberId, $circleId);
			$result = $this->memberService->removeMember($memberId);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'memberId' => $memberId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function circles(int $limit = -1, int $offset = 0): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$probe = new CircleProbe();
			$probe->filterHiddenCircles()
				  ->filterBackendCircles()
				  ->addDetail(BasicProbe::DETAILS_POPULATION)
				  ->setItemsLimit($limit)
				  ->setItemsOffset($offset);

			return new DataResponse($this->serializeArray($this->circleService->getCircles($probe)));
		} catch (Exception $e) {
			$this->e($e);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
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

			return new DataResponse($this->serializeArray($this->memberService->getMembers($circleId)));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $value
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editName(string $circleId, string $value): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$outcome = $this->circleService->updateName($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $value
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editDescription(string $circleId, string $value): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$outcome = $this->circleService->updateDescription($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $setting
	 * @param string|null $value
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editSetting(string $circleId, string $setting, ?string $value = null): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$outcome = $this->circleService->updateSetting($circleId, $setting, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'setting' => $setting, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param int $value
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editConfig(string $circleId, int $value): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$outcome = $this->circleService->updateConfig($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $circleId
	 * @param string $singleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function link(string $circleId, string $singleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();
			$membership = $this->membershipService->getMembership($circleId, $singleId, true);

			return new DataResponse($this->serialize($membership));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'singleId' => $singleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @return void
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws FrontendException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	private function setCurrentFederatedUser(): void {
		if (!$this->configService->getAppValueBool(ConfigService::FRONTEND_ENABLED)) {
			throw new FrontendException('frontend disabled');
		}

		$user = $this->userSession->getUser();
		$this->federatedUserService->setLocalCurrentUser($user);
	}
}

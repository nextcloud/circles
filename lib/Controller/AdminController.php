<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Controller;

use Exception;
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
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
use OCA\Circles\Service\SearchService;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Class AdminController
 *
 * @package OCA\Circles\Controller
 */
class AdminController extends OCSController {
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
		SearchService $searchService,
		ConfigService $configService,
	) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->membershipService = $membershipService;
		$this->searchService = $searchService;
		$this->configService = $configService;

		$this->setup('app', 'circles');
	}


	/**
	 * @param string $emulated
	 * @param string $name
	 * @param bool $personal
	 * @param bool $local
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function create(
		string $emulated,
		string $name,
		bool $personal = false,
		bool $local = false,
	): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);
			$circle = $this->circleService->create($name, null, $personal, $local);

			return new DataResponse($this->serializeArray($circle));
		} catch (Exception $e) {
			$this->e(
				$e, [
					'emulated' => $emulated,
					'name' => $name,
					'members' => $personal,
					'local' => $local
				]
			);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function destroy(string $emulated, string $circleId): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);
			$circle = $this->circleService->destroy($circleId);

			return new DataResponse($this->serializeArray($circle));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param string $userId
	 * @param int $type
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function memberAdd(string $emulated, string $circleId, string $userId, int $type): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

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
			$this->e(
				$e, [
					'emulated' => $emulated,
					'circleId' => $circleId,
					'userId' => $userId,
					'type' => $type
				]
			);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param string $memberId
	 * @param string|int $level
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function memberLevel(string $emulated, string $circleId, string $memberId, $level): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);
			if (is_int($level)) {
				$level = Member::parseLevelInt($level);
			} else {
				$level = Member::parseLevelString($level);
			}

			$this->memberService->getMemberById($memberId, $circleId);
			$result = $this->memberService->memberLevel($memberId, $level);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e(
				$e,
				[
					'emulated' => $emulated,
					'circleId' => $circleId,
					'memberId' => $memberId,
					'level' => $level
				]
			);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param int $limit
	 * @param int $offset
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function circles(string $emulated, int $limit = -1, int $offset = 0): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

			$probe = new CircleProbe();
			$probe->filterHiddenCircles()
				->filterBackendCircles()
				->addDetail(BasicProbe::DETAILS_POPULATION)
				->setItemsLimit($limit)
				->setItemsOffset($offset);

			return new DataResponse($this->serializeArray($this->circleService->getCircles($probe)));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function circleDetails(string $emulated, string $circleId): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

			$probe = new CircleProbe();
			$probe->includeNonVisibleCircles();

			return new DataResponse($this->serialize($this->circleService->getCircle($circleId, $probe)));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function circleJoin(string $emulated, string $circleId): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);
			$result = $this->circleService->circleJoin($circleId);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function circleLeave(string $emulated, string $circleId): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);
			$result = $this->circleService->circleLeave($circleId);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param string $memberId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function memberConfirm(string $emulated, string $circleId, string $memberId): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

			$member = $this->memberService->getMemberById($memberId, $circleId);
			$federatedUser = new FederatedUser();
			$federatedUser->importFromIFederatedUser($member);

			$result = $this->memberService->addMember($circleId, $federatedUser);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId, 'memberId' => $memberId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param string $memberId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function memberRemove(string $emulated, string $circleId, string $memberId): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);
			$this->memberService->getMemberById($memberId, $circleId);

			$result = $this->memberService->removeMember($memberId);

			return new DataResponse($this->serializeArray($result));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId, 'memberId' => $memberId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function members(string $emulated, string $circleId): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

			return new DataResponse($this->serializeArray($this->memberService->getMembers($circleId)));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param string $value
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editName(string $emulated, string $circleId, string $value): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

			$outcome = $this->circleService->updateName($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param string $value
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editDescription(string $emulated, string $circleId, string $value): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

			$outcome = $this->circleService->updateDescription($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param string $setting
	 * @param string|null $value
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editSetting(string $emulated, string $circleId, string $setting, ?string $value = null): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

			$outcome = $this->circleService->updateSetting($circleId, $setting, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'setting' => $setting, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}





	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param int $value
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function editConfig(string $emulated, string $circleId, int $value): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);

			$outcome = $this->circleService->updateConfig($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 * @param string $circleId
	 * @param string $singleId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function link(string $emulated, string $circleId, string $singleId): DataResponse {
		try {
			$this->setLocalFederatedUser($emulated);
			$membership = $this->membershipService->getMembership($circleId, $singleId, true);

			return new DataResponse($this->serialize($membership));
		} catch (Exception $e) {
			$this->e($e, ['emulated' => $emulated, 'circleId' => $circleId, 'singleId' => $singleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	/**
	 * @param string $emulated
	 *
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 */
	private function setLocalFederatedUser(string $emulated): void {
		$user = $this->userSession->getUser();
		$this->federatedUserService->setCurrentPatron($user->getUID());
		$this->federatedUserService->setLocalCurrentUserId($emulated);
	}
}

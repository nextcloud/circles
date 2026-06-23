<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Controller;

use Exception;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\FrontendException;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\BasicProbe;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\AvatarService;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\SearchService;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
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

	/** @var AvatarService */
	private AvatarService $avatarService;

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
	 * @param AvatarService $avatarService
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
		AvatarService $avatarService,
		ConfigService $configService,
	) {
		parent::__construct($appName, $request);

		$this->userSession = $userSession;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->membershipService = $membershipService;
		$this->permissionService = $permissionService;
		$this->searchService = $searchService;
		$this->avatarService = $avatarService;
		$this->configService = $configService;
		$this->setup('app', 'circles');
	}


	#[NoAdminRequired]
	public function create(string $name, bool $personal = false, bool $local = false): DataResponse {
		try {
			if (!$this->configService->isGSAvailable() && $local === true) {
				throw new OCSException('circle configuration not supported', 400);
			}
			$this->setCurrentFederatedUser();

			$this->permissionService->confirmCircleCreation();

			$circle = $this->circleService->create($name, null, $personal, $local);

			return new DataResponse($this->serializeArray($circle));
		} catch (Exception $e) {
			$this->e($e, ['name' => $name, 'members' => $personal, 'local' => $local]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'destroy')]
	public function destroy(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeOwner($memberUser);

			$circle = $this->circleService->destroy($circleId);

			return new DataResponse($this->serializeArray($circle));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	public function search(string $term): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			return new DataResponse($this->serializeArray($this->searchService->search($term)));
		} catch (Exception $e) {
			$this->e($e, ['term' => $term]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[BruteForceProtection(action: 'circleDetails')]
	public function circleDetails(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);

			$probe = new CircleProbe();
			$probe->includeNonVisibleCircles();

			return new DataResponse($this->serialize($this->circleService->getCircle($circleId, $probe)));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'memberAdd')]
	public function memberAdd(string $circleId, string $userId, int $type): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			if (!$this->circleService->getCircle($circleId)->isConfig(Circle::CFG_FRIEND)) {
				$this->permissionService->memberMustBeAtLeastModerator($memberUser);
			}

			// exception in Contact
			if ($type === Member::TYPE_CONTACT) {
				$currentUser = $this->federatedUserService->getCurrentUser();
				if (!$this->configService->isLocalInstance($currentUser->getInstance())) {
					throw new OCSException('works only from local instance', 404);
				}

				$userId = $currentUser->getUserId() . '/' . $userId;
			}

			if ($type === Member::TYPE_CIRCLE) {
				$this->circleService->getCircle($userId);
			}

			$federatedUser = $this->federatedUserService->generateFederatedUser($userId, $type);
			$result = $this->memberService->addMember($circleId, $federatedUser);

			return new DataResponse($this->serializeArray($result));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'userId' => $userId, 'type' => $type]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'membersAdd')]
	public function membersAdd(string $circleId, array $members): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			if (!$this->circleService->getCircle($circleId)->isConfig(Circle::CFG_FRIEND)) {
				$this->permissionService->memberMustBeAtLeastModerator($memberUser);
			}

			$federatedUsers = [];
			foreach ($members as $member) {
				$userId = $this->get('id', $member);
				$type = $this->getInt('type', $member);

				if ($type === Member::TYPE_CIRCLE) {
					$this->circleService->getCircle($userId);
				}

				// TODO: generate Multiple FederatedUsers using a single SQL request
				try {
					$federatedUsers[] = $this->federatedUserService->generateFederatedUser(
						$userId,
						$type,
					);
				} catch (MemberNotFoundException) {
				}
			}

			$result = $this->memberService->addMembers($circleId, $federatedUsers);

			return new DataResponse($this->serializeArray($result));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'members' => $members]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
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


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'circleLeave')]
	public function circleLeave(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);

			$result = $this->circleService->circleLeave($circleId);

			return new DataResponse($this->serializeArray($result));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'memberLevel')]
	public function memberLevel(string $circleId, string $memberId, $level): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeAtLeastModerator($memberUser);

			if (is_int($level)) {
				$level = Member::parseLevelInt($level);
			} else {
				$level = Member::parseLevelString($level);
			}

			$this->memberService->getMemberById($memberId, $circleId);
			$result = $this->memberService->memberLevel($memberId, $level);

			return new DataResponse($this->serializeArray($result));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'memberId' => $memberId, 'level' => $level]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'memberConfirm')]
	public function memberConfirm(string $circleId, string $memberId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			if (!$this->circleService->getCircle($circleId)->isConfig(Circle::CFG_FRIEND)) {
				$this->permissionService->memberMustBeAtLeastModerator($memberUser);
			}

			$member = $this->memberService->getMemberById($memberId, $circleId);
			$federatedUser = new FederatedUser();
			$federatedUser->importFromIFederatedUser($member);

			$result = $this->memberService->addMember($circleId, $federatedUser);

			return new DataResponse($this->serializeArray($result));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'memberId' => $memberId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'memberRemove')]
	public function memberRemove(string $circleId, string $memberId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeAtLeastModerator($memberUser);
			$this->permissionService->memberMustBeHigherLevelThan($memberUser, $memberId);

			$result = $this->memberService->removeMember($memberId);

			return new DataResponse($this->serializeArray($result));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'memberId' => $memberId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	public function circles(int $limit = -1, int $offset = 0): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$probe = new CircleProbe();
			$probe->filterHiddenCircles()
				->filterBackendCircles()
				->addDetail(BasicProbe::DETAILS_POPULATION)
				->setItemsLimit($limit)
				->setItemsOffset($offset);

			// hide full config of "visible to everyone" circles for non-members
			$circles = (array_map(function (Circle $circle) {
				if ($circle->isConfig(Circle::CFG_VISIBLE) && !$circle->hasInitiator()) {
					// return only configs needed by frontend
					$circleConfig = Circle::CFG_VISIBLE;
					if ($circle->isConfig(Circle::CFG_OPEN)) {
						$circleConfig += Circle::CFG_OPEN;
					}
					$circle->setConfig($circleConfig);
				}
				return $circle;
			}, $this->circleService->getCircles($probe)));

			return new DataResponse($this->serializeArray($circles));
		} catch (Exception $e) {
			$this->e($e);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	public function probeCircles(int $limit = -1, int $offset = 0): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$probe = new CircleProbe();
			$probe->filterHiddenCircles()
				->filterBackendCircles()
				->addDetail(BasicProbe::DETAILS_POPULATION)
				->setItemsLimit($limit)
				->setItemsOffset($offset);

			return new DataResponse($this->serializeArray($this->circleService->probeCircles($probe)));
		} catch (Exception $e) {
			$this->e($e);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'members')]
	public function members(string $circleId, bool $fullDetails = false, int $limit = 0, string $search = '', ?int $role = null): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);

			return new DataResponse($this->serializeArray($this->memberService->getMembers($circleId, $fullDetails, $limit, $search, $role)));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'editName')]
	public function editName(string $circleId, string $value): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeAtLeastAdmin($memberUser);

			$outcome = $this->circleService->updateName($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'editDescription')]
	public function editDescription(string $circleId, string $value): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeAtLeastAdmin($memberUser);

			$outcome = $this->circleService->updateDescription($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'editSetting')]
	public function editSetting(string $circleId, string $setting, ?string $value = null): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeAtLeastAdmin($memberUser);

			$outcome = $this->circleService->updateSetting($circleId, $setting, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'setting' => $setting, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'editConfig')]
	public function editConfig(string $circleId, int $value): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeAtLeastAdmin($memberUser);

			$outcome = $this->circleService->updateConfig($circleId, $value);

			return new DataResponse($this->serializeArray($outcome));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'value' => $value]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'circleAvatar')]
	public function circleAvatar(string $circleId): FileDisplayResponse|DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);

			$file = $this->avatarService->getAvatar($circleId);
			if ($file === null) {
				return new DataResponse([], Http::STATUS_NOT_FOUND);
			}

			$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $file->getMimeType()]);
			$response->cacheFor(60 * 60 * 24, false, true);

			return $response;
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'uploadAvatar')]
	public function uploadAvatar(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeAtLeastAdmin($memberUser);

			$outcome = $this->avatarService->updateAvatar($circleId, $this->request->getUploadedFile('file'));

			return new DataResponse($this->serializeArray($outcome));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'removeAvatar')]
	public function removeAvatar(string $circleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$memberUser = $this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeAtLeastAdmin($memberUser);

			$outcome = $this->avatarService->removeAvatar($circleId);

			return new DataResponse($this->serializeArray($outcome));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}


	#[NoAdminRequired]
	#[BruteForceProtection(action: 'link')]
	public function link(string $circleId, string $singleId): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$this->permissionService->userMustBeMember($this->userSession->getUser()->getUID(), $circleId);

			$membership = $this->membershipService->getMembership($circleId, $singleId, true);

			return new DataResponse($this->serialize($membership));
		} catch (InsufficientPermissionException $e) {
			$response = new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$this->e($e, ['circleId' => $circleId, 'singleId' => $singleId]);
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}

	/**
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

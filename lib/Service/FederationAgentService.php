<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\FederatedItems\CircleCreate;
use OCA\Circles\FederatedItems\SingleMemberAdd;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Traits\TNCWellKnown;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\AppFramework\Services\IAppConfig;
use Psr\Log\LoggerInterface;

class FederationAgentService {
	use TNCWellKnown;
	use TStringTools;

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly CircleRequest $circleRequest,
		private readonly MemberRequest $memberRequest,
		private readonly CircleService $circleService,
		private readonly MemberService $memberService,
		private readonly PermissionService $permissionService,
		private readonly FederatedUserService $federatedUserService,
		private readonly FederatedEventService $federatedEventService,
		private readonly RemoteStreamService $remoteStreamService,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * returns the singleId of this instance's federation agent
	 *
	 * federation agent = 'app:federation_agent:{singleId}' circle
	 */
	public function getOrCreateFederationAgentId(): string {
		if (!$this->appConfig->getAppValueBool(ConfigLexicon::FEDERATION_AGENT_ENABLED, false)) {
			return '';
		}

		$singleId = $this->appConfig->getAppValueString(ConfigLexicon::FEDERATION_AGENT_LOCAL_ID, '');
		if ($singleId !== '') {
			return $singleId;
		}

		try {
			$outcome = $this->createFederationAgent();
			$singleId = $outcome['id'];
			$this->appConfig->setAppValueString(ConfigLexicon::FEDERATION_AGENT_LOCAL_ID, $singleId);
		} catch (Exception $e) {
			$this->logger->error("could not create 'app:federation_agent:{singleId}' circle", ['exception' => $e]);
			return '';
		}

		return $singleId;
	}

	/**
	 * sets this instance's federation agent as the current user
	 *
	 * federation agent = 'app:federation_agent:{singleId}' circle
	 *
	 * @throws OwnerNotFoundException
	 * @throws RequestBuilderException
	 * @throws FederatedUserNotFoundException
	 * @throws FederatedUserException
	 */
	public function setFederationAgentAsCurrentUser(): void {
		$singleId = $this->getOrCreateFederationAgentId();
		if ($singleId === '') {
			throw new Exception('could not resolve local federation agent');
		}

		$federatedUser = $this->circleRequest->getFederatedUserBySingleId($singleId);
		$this->federatedUserService->setCurrentUser($federatedUser);
	}

	/**
	 * ensures the federation agent of every given remote instance is added
	 * as a moderator to every given circle
	 *
	 * federation agent = 'app:federation_agent:{singleId}' circle
	 *
	 * @param list<string> $circleIds
	 * @param list<string> $remoteInstances
	 */
	public function ensureFederationAgentsAsModerators(array $circleIds, array $remoteInstances): void {
		if ($circleIds === []) {
			return;
		}

		if ($remoteInstances === []) {
			return;
		}

		$this->federatedUserService->setLocalCurrentApp(Application::APP_ID, Member::APP_CIRCLES);
		$currentApp = $this->federatedUserService->getCurrentApp();
		$this->federatedUserService->setCurrentUser($currentApp);

		foreach ($remoteInstances as $remoteInstance) {
			try {
				// try the cached instance first, to avoid a network request
				$remote = $this->remoteStreamService->getCachedRemoteInstance($remoteInstance);
				$federationAgentId = $remote->getFederationAgentId();
				if ($federationAgentId === '') {
					// not found in cache, fetch it fresh from the remote instance
					$remote = $this->remoteStreamService->retrieveRemoteInstance($remoteInstance);
					$federationAgentId = $remote->getFederationAgentId();
					if ($federationAgentId !== '') {
						// found it, persist so we don't need to fetch it again next time
						$this->remoteStreamService->update($remote, RemoteStreamService::UPDATE_ITEM);
					}
				}
			} catch (Exception $e) {
				$this->logger->error("could not resolve 'app:federation_agent:{singleId}' circle ID from remote instance", ['instance' => $remoteInstance, 'exception' => $e]);
				continue;
			}

			if ($federationAgentId === '') {
				$this->logger->warning("could not find 'app:federation_agent:{singleId}' circle ID on remote instance", ['instance' => $remoteInstance]);
				continue;
			}

			foreach ($circleIds as $circleId) {
				$this->ensureModerator($circleId, $remoteInstance, $federationAgentId);
			}
		}
	}

	private function createFederationAgent(): array {
		$this->federatedUserService->setLocalCurrentApp(Application::APP_ID, Member::APP_CIRCLES);
		$owner = $this->federatedUserService->getCurrentApp();

		$config = Circle::CFG_BACKEND;
		$singleId = $this->token(ManagedModel::ID_LENGTH);

		$circle = new Circle();
		$circle->setName('app:federation_agent:' . $singleId)
			->setSingleId($singleId)
			->setSource(Member::APP_CIRCLES)
			->setConfig($config);

		$this->circleService->confirmName($circle);
		$this->permissionService->confirmAllowedCircleTypes($circle);

		$member = new Member();
		$member->importFromIFederatedUser($owner);
		$member->setId($this->token(ManagedModel::ID_LENGTH))
			->setCircleId($circle->getSingleId())
			->setLevel(Member::LEVEL_OWNER)
			->setStatus(Member::STATUS_MEMBER);

		$this->federatedUserService->setMemberPatron($member);

		$circle->setOwner($member)
			->setInitiator($member);

		$event = new FederatedEvent(CircleCreate::class);
		$event->setCircle($circle);
		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}

	private function ensureModerator(string $circleId, string $remoteInstance, string $federatedId): void {
		try {
			$this->memberRequest->getMember($circleId, $federatedId);
			// already a member
			return;
		} catch (MemberNotFoundException) {
		}

		try {
			$federatedUser = $this->federatedUserService->getFederatedUser($federatedId . '@' . $remoteInstance, Member::TYPE_CIRCLE);
			$circle = $this->circleRequest->getCircle($circleId, $this->federatedUserService->getCurrentUser());

			$member = new Member();
			$member->importFromIFederatedUser($federatedUser);

			$this->federatedUserService->setMemberPatron($member);

			$event = new FederatedEvent(SingleMemberAdd::class);
			$event->setCircle($circle);
			$event->setMember($member);
			$event->setAsync(false);
			$this->federatedEventService->newEvent($event);

			$addedMember = $event->getMember();
			$this->memberService->memberLevel($addedMember->getId(), Member::LEVEL_MODERATOR);

			$this->logger->debug('moderator from remote instance added to circle', ['circleId' => $circleId, 'memberId' => $addedMember->getId(), 'remoteInstance' => $remoteInstance]);
		} catch (Exception $e) {
			$this->logger->error('could not add moderator from remote instance to circle', ['circleId' => $circleId, 'remoteInstance' => $remoteInstance, 'exception' => $e]);
		}
	}
}

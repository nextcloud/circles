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
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Http\Client\IClientService;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICredentialsManager;
use Psr\Log\LoggerInterface;

class OidcService {
	use TStringTools;

	public const CREDENTIAL_REFRESH_TOKEN = 'circles_oidc_refresh_token';
	private const MANAGED_BY_OIDC = 'oidc';

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IUserManager $userManager,
		private readonly ICredentialsManager $credentialsManager,
		private readonly IClientService $clientService,
		private readonly CircleRequest $circleRequest,
		private readonly MemberRequest $memberRequest,
		private readonly ConfigService $configService,
		private readonly FederatedUserService $federatedUserService,
		private readonly FederationAgentService $federationAgentService,
		private readonly MemberService $memberService,
		private readonly LoggerInterface $logger,
	) {
	}

	public function syncMemberships(): void {
		$this->userManager->callForSeenUsers(function (IUser $user): void {
			$this->syncMembershipsForUser($user->getUID());
		});
	}

	public function syncMembershipsForUser(string $userId, ?string $accessToken = null): void {
		if ($accessToken === null) {
			$refreshToken = $this->credentialsManager->retrieve($userId, self::CREDENTIAL_REFRESH_TOKEN);
			if (empty($refreshToken)) {
				return;
			}

			$accessToken = $this->refreshAccessToken($userId, $refreshToken);
			if ($accessToken === null) {
				$this->logger->error('could not refresh OIDC access token', ['userId' => $userId]);
				return;
			}
		}

		$rawMemberships = $this->fetchMemberships($accessToken);
		if ($rawMemberships === null) {
			// don't assume user has no memberships on failed request, to avoid removing existing memberships
			$this->logger->debug('could not fetch OIDC memberships, skipping reconciliation', ['userId' => $userId]);
			return;
		}

		// ensure user is a member of circles matching OIDC memberships
		$desiredCircleIds = [];
		foreach ($rawMemberships as $rawMembership) {
			$circleId = $this->generateCircleIdFromString($rawMembership);
			$desiredCircleIds[] = $circleId;
			$this->ensureMember($userId, $circleId, $rawMembership);
		}

		// remove user from circles they were added to via OIDC but no longer belong to
		foreach ($this->memberRequest->getMembersByUserId($userId) as $member) {
			if ($member->getNote(Member::NOTE_MANAGED_BY) !== self::MANAGED_BY_OIDC) {
				continue;
			}
			if (in_array($member->getCircleId(), $desiredCircleIds, true)) {
				continue;
			}
			$this->removeMember($userId, $member->getCircleId());
		}
	}

	private function ensureMember(string $userId, string $circleId, string $rawMembership): void {
		try {
			$circle = $this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			$this->logger->debug('circle not found, skipping', ['circleId' => $circleId, 'rawMembership' => $rawMembership]);
			return;
		}

		try {
			$this->memberRequest->getMemberByUserId($circleId, $userId);
			// already a member
			return;
		} catch (MemberNotFoundException) {
		}

		try {
			$this->setInitiatorForCircle($circle);

			$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
			$this->memberService->addMember($circleId, $federatedUser);

			// mark this membership as managed by OIDC
			$member = $this->memberRequest->getMemberByUserId($circleId, $userId);
			$member->setNote(Member::NOTE_MANAGED_BY, self::MANAGED_BY_OIDC);
			$this->memberRequest->update($member);
		} catch (Exception $e) {
			$this->logger->error('could not add user to circle', ['userId' => $userId, 'circleId' => $circleId, 'exception' => $e]);
		}
	}

	private function removeMember(string $userId, string $circleId): void {
		try {
			$circle = $this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			$this->logger->debug('circle not found, skipping', ['circleId' => $circleId]);
			return;
		}

		try {
			$member = $this->memberRequest->getMemberByUserId($circleId, $userId);

			$this->setInitiatorForCircle($circle);

			$this->memberService->removeMember($member->getId());
		} catch (Exception $e) {
			$this->logger->error('could not remove user from circle', ['userId' => $userId, 'circleId' => $circleId, 'exception' => $e]);
		}
	}

	/**
	 * sets the correct initiator to act on the given circle
	 * - local circle is managed by 'app:circles:{singleId}'
	 * - remote circle is managed by 'app:federation_agent:{singleId}'
	 */
	private function setInitiatorForCircle(Circle $circle): void {
		if ($this->configService->isLocalInstance($circle->getInstance())) {
			$this->federatedUserService->setLocalCurrentApp(Application::APP_ID, Member::APP_CIRCLES);
			$currentApp = $this->federatedUserService->getCurrentApp();
			$this->federatedUserService->setCurrentUser($currentApp);
		} else {
			$this->federationAgentService->setFederationAgentAsCurrentUser();
		}
	}

	/**
	 * @return string|null fresh access token or null on failure
	 */
	private function refreshAccessToken(string $userId, string $refreshToken): ?string {
		$tokenEndpoint = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_TOKEN_ENDPOINT);
		$clientId = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_CLIENT_ID);
		$clientSecret = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_CLIENT_SECRET);

		$client = $this->clientService->newClient();
		try {
			$response = $client->post($tokenEndpoint, [
				'auth' => [$clientId, $clientSecret],
				'body' => [
					'grant_type' => 'refresh_token',
					'refresh_token' => $refreshToken,
				],
			]);
		} catch (Exception $e) {
			$this->logger->error('OIDC token refresh failed', ['exception' => $e]);
			return null;
		}

		$data = json_decode($response->getBody(), true);

		if (!empty($data['refresh_token'])) {
			$this->credentialsManager->store($userId, self::CREDENTIAL_REFRESH_TOKEN, $data['refresh_token']);
		}

		return $data['access_token'] ?? null;
	}

	/**
	 * @return list<string>|null raw membership entries (e.g. "urn:geant:company.co:group:my_group#login.company.co")
	 *                           null if the request failed
	 */
	private function fetchMemberships(string $accessToken): ?array {
		$userinfoEndpoint = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_USERINFO_ENDPOINT);
		$membershipClaim = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_MEMBERSHIP_CLAIM);

		$client = $this->clientService->newClient();
		try {
			$response = $client->get($userinfoEndpoint, [
				'headers' => ['Authorization' => 'Bearer ' . $accessToken],
			]);
		} catch (Exception $e) {
			$this->logger->error('OIDC userinfo request failed', ['exception' => $e]);
			return null;
		}

		$response = json_decode($response->getBody(), true);
		$this->logger->debug('OIDC userinfo response: ' . json_encode($response));

		$rawMemberships = $response[$membershipClaim] ?? [];
		if (!is_array($rawMemberships)) {
			$rawMemberships = [$rawMemberships];
		}

		$this->logger->debug('OIDC raw memberships (' . $membershipClaim . '): ' . json_encode($rawMemberships));

		return $rawMemberships;
	}
}

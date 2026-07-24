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
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICredentialsManager;
use Psr\Log\LoggerInterface;

class OidcService {
	use TStringTools;

	public const CREDENTIAL_REFRESH_TOKEN = 'circles_oidc_refresh_token';

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IClientService $clientService,
		private readonly LoggerInterface $logger,
		private readonly IUserManager $userManager,
		private readonly ICredentialsManager $credentialsManager,
		private readonly CircleRequest $circleRequest,
		private readonly FederatedUserService $federatedUserService,
		private readonly MemberService $memberService,
		private readonly MemberRequest $memberRequest,
	) {
	}

	public function syncMemberships(): void {
		$moderatorSingleId = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::REMOTE_MOD_CIRCLE_LOCAL_ID, '');
		$moderator = $this->circleRequest->getFederatedUserBySingleId($moderatorSingleId);

		$this->userManager->callForSeenUsers(function (IUser $user) use ($moderator): void {
			$this->syncMembershipsForUser($user->getUID(), moderator: $moderator);
		});
	}

	public function syncMembershipsForUser(string $userId, ?string $accessToken = null, ?FederatedUser $moderator = null): void {

		if ($accessToken === null) {
			$refreshToken = $this->credentialsManager->retrieve($userId, self::CREDENTIAL_REFRESH_TOKEN);
			if (empty($refreshToken)) {
				return;
			}

			$accessToken = $this->refreshAccessToken($refreshToken);
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

		if ($moderator === null) {
			$moderatorSingleId = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::REMOTE_MOD_CIRCLE_LOCAL_ID, '');
			$moderator = $this->circleRequest->getFederatedUserBySingleId($moderatorSingleId);
		}
		$this->federatedUserService->setCurrentUser($moderator);

		// ensure user is a member of circles matching OIDC memberships
		$desiredCircleIds = [];
		foreach ($rawMemberships as $rawMembership) {
			$circleId = $this->generateCircleIdFromString($rawMembership);
			$desiredCircleIds[] = $circleId;
			$this->ensureMember($userId, $circleId);
		}

		// remove user from third-party circles not present in the current OIDC memberships
		$currentCircleIds = $this->getThirdPartyCirclesForUser($userId);
		foreach ($currentCircleIds as $circleId) {
			if (in_array($circleId, $desiredCircleIds, true)) {
				continue;
			}
			$this->removeMember($userId, $circleId);
		}
	}

	private function ensureMember(string $userId, string $circleId): void {
		try {
			$this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			return;
		}

		try {
			$this->memberRequest->getMemberByUserId($circleId, $userId);
			return;
		} catch (MemberNotFoundException) {
		}

		try {
			$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
			$this->memberService->addMember($circleId, $federatedUser);
		} catch (Exception $e) {
			$this->logger->error('could not add user to circle', ['userId' => $userId, 'circleId' => $circleId, 'exception' => $e]);
		}
	}

	private function removeMember(string $userId, string $circleId): void {
		try {
			$member = $this->memberRequest->getMemberByUserId($circleId, $userId);
			$this->memberService->removeMember($member->getId());
		} catch (Exception $e) {
			$this->logger->error('could not remove user from circle', ['userId' => $userId, 'circleId' => $circleId, 'exception' => $e]);
		}
	}

	/**
	 * @return string|null fresh access token or null on failure
	 */
	private function refreshAccessToken(string $refreshToken): ?string {
		$tokenEndpoint = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::OIDC_TOKEN_ENDPOINT, '');
		$clientId = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::OIDC_CLIENT_ID, '');
		$clientSecret = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::OIDC_CLIENT_SECRET, '');

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

		return $data['access_token'] ?? null;
	}

	/**
	 * @return list<string>|null raw membership entries (e.g. "urn:geant:company.co:group:my_group#login.company.co")
	 *                           null if the request failed
	 */
	private function fetchMemberships(string $accessToken): ?array {
		$userinfoEndpoint = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::OIDC_USERINFO_ENDPOINT, '');
		$membershipClaim = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::OIDC_MEMBERSHIP_CLAIM, '');

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

	/**
	 * @return list<string> circleIds of third-party circles the user currently belongs to
	 */
	private function getThirdPartyCirclesForUser(string $userId): array {
		$circleIds = [];
		foreach ($this->circleRequest->getThirdParty() as $circle) {
			try {
				$this->memberRequest->getMemberByUserId($circle->getSingleId(), $userId);
				$circleIds[] = $circle->getSingleId();
			} catch (MemberNotFoundException) {
			}
		}

		return $circleIds;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\AuditLog\Operation;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\OidcService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\ICredentialsManager;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;
use Random\Randomizer;

class OidcController extends Controller {
	private const SESSION_STATE = 'circles.oidc.state';
	private const SESSION_USER_ID = 'circles.oidc.user_id';

	public function __construct(
		IRequest $request,
		private readonly IAppConfig $appConfig,
		private readonly IUserSession $userSession,
		private readonly ISession $session,
		private readonly IURLGenerator $urlGenerator,
		private readonly IClientService $clientService,
		private readonly ICredentialsManager $credentialsManager,
		private readonly OidcService $oidcService,
		private readonly AuditLogService $auditLogService,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function connect(): RedirectResponse {
		if (!$this->appConfig->getAppValueBool(ConfigLexicon::OIDC_ENABLED)) {
			return $this->redirectToPersonalSettings('disabled');
		}

		$state = (new Randomizer())->getBytesFromString(ISecureRandom::CHAR_ALPHANUMERIC, 32);
		$userId = $this->userSession->getUser()?->getUID();
		if ($userId === null) {
			return $this->redirectToPersonalSettings('error');
		}
		$this->session->set(self::SESSION_STATE, $state);
		$this->session->set(self::SESSION_USER_ID, $userId);
		$this->session->close();

		$authorizationEndpoint = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_AUTHORIZATION_ENDPOINT);
		$clientId = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_CLIENT_ID);
		$scope = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_SCOPE);
		$redirectUri = $this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.Oidc.callback');

		$authorizationUrl = $this->buildAuthorizationUrl($authorizationEndpoint, [
			'response_type' => 'code',
			'client_id' => $clientId,
			'redirect_uri' => $redirectUri,
			'scope' => $scope,
			'state' => $state,
			'prompt' => 'consent',
		]);

		$this->logger->debug('Redirecting user to OIDC provider: ' . $authorizationUrl);

		return new RedirectResponse($authorizationUrl);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function callback(string $state = '', string $code = '', string $error = '', string $error_description = ''): RedirectResponse {
		$userId = $this->userSession->getUser()?->getUID();
		if ($error !== '') {
			$this->logger->error('OIDC provider returned an error: ' . $error . ' - ' . $error_description);
			$this->logFailedLink($userId, 'provider_error');
			return $this->redirectToPersonalSettings('error');
		}

		if ($state === '' || $state !== $this->session->get(self::SESSION_STATE)) {
			$this->logger->error('OIDC callback state mismatch');
			$this->logFailedLink($userId, 'state_mismatch');
			return $this->redirectToPersonalSettings('error');
		}
		if ($userId === null || $userId !== $this->session->get(self::SESSION_USER_ID)) {
			$this->logger->error('OIDC callback user mismatch: started as ' . $this->session->get(self::SESSION_USER_ID) . ', completed as ' . $userId);
			$this->logFailedLink($userId, 'user_mismatch');
			return $this->redirectToPersonalSettings('error');
		}
		$this->session->remove(self::SESSION_STATE);
		$this->session->remove(self::SESSION_USER_ID);

		$tokenEndpoint = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_TOKEN_ENDPOINT);
		$clientId = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_CLIENT_ID);
		$clientSecret = $this->appConfig->getAppValueString(ConfigLexicon::OIDC_CLIENT_SECRET);
		$redirectUri = $this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.Oidc.callback');

		$client = $this->clientService->newClient();
		try {
			$response = $client->post($tokenEndpoint, [
				'auth' => [$clientId, $clientSecret],
				'body' => [
					'grant_type' => 'authorization_code',
					'code' => $code,
					'redirect_uri' => $redirectUri,
				],
			]);
		} catch (\Exception $e) {
			$this->logger->error('OIDC token exchange failed', ['exception' => $e]);
			$this->logFailedLink($userId, 'token_exchange_failed');
			return $this->redirectToPersonalSettings('error');
		}

		$data = json_decode($response->getBody(), true);

		if (empty($data['refresh_token'])) {
			$this->logger->error('OIDC provider did not return a refresh_token for user ' . $userId);
			$this->logFailedLink($userId, 'missing_refresh_token');
			return $this->redirectToPersonalSettings('error');
		}

		$this->credentialsManager->store($userId, OidcService::CREDENTIAL_REFRESH_TOKEN, $data['refresh_token']);
		$this->auditLogService->log(Operation::OidcAccountLinked, 'OIDC account of "%s" linked to synchronize team memberships', [
			'userId' => $userId,
		]);

		// initial sync
		if (!empty($data['access_token'])) {
			$this->oidcService->syncMembershipsForUser($userId, $data['access_token']);
		}

		return $this->redirectToPersonalSettings('success');
	}

	private function logFailedLink(?string $userId, string $reason): void {
		$this->auditLogService->log(Operation::OidcAccountLinkFailed, 'Linking the OIDC account of "%s" failed: %s', [
			'userId' => $userId,
			'reason' => $reason,
		]);
	}

	private function redirectToPersonalSettings(string $result): RedirectResponse {
		$url = $this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'circles']);

		return new RedirectResponse($url . '?oidcResult=' . $result);
	}

	private function buildAuthorizationUrl(string $authorizationEndpoint, array $params): string {
		$parsedUrl = parse_url($authorizationEndpoint);

		$urlWithoutParams
			= ($parsedUrl['scheme'] ?? '') . '://'
			. ($parsedUrl['host'] ?? '')
			. (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '')
			. ($parsedUrl['path'] ?? '');

		$queryParams = $params;
		if (isset($parsedUrl['query'])) {
			parse_str($parsedUrl['query'], $existingParams);
			$queryParams = array_merge($queryParams, $existingParams);
		}

		return $urlWithoutParams . '?' . http_build_query($queryParams);
	}
}

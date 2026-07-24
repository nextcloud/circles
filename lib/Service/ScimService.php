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
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\FederatedItems\CircleCreate;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class ScimService {
	use TStringTools;

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IClientService $clientService,
		private readonly LoggerInterface $logger,
		private readonly FederatedUserService $federatedUserService,
		private readonly PermissionService $permissionService,
		private readonly FederatedEventService $federatedEventService,
		private readonly CircleService $circleService,
		private readonly CircleRequest $circleRequest,
	) {
	}

	public function syncCircles(): void {
		$circles = $this->fetchCircles();
		foreach ($circles as $circle) {
			$circleId = $this->generateCircleIdFromString($circle['id']);

			try {
				$this->circleRequest->getCircle($circleId);
				continue;
			} catch (CircleNotFoundException) {
			}

			try {
				$this->createCircle($circleId, $circle['displayName']);

				$this->logger->debug('circle created from SCIM group', ['scimGroupId' => $circle['id'], 'circleId' => $circleId, 'displayName' => $circle['displayName']]);
			} catch (Exception $e) {
				$this->logger->error('could not create circle from SCIM group', ['scimGroupId' => $circle['id'], 'exception' => $e]);
			}
		}
	}

	private function fetchCircles(): array {
		return $this->mockGroups();

		$endpoint = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::SCIM_ENDPOINT, '');
		$token = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::SCIM_TOKEN, '');

		$client = $this->clientService->newClient();
		try {
			$response = $client->get(rtrim($endpoint, '/') . '/Groups', [
				'headers' => ['Authorization' => 'Bearer ' . $token],
			]);
		} catch (Exception $e) {
			$this->logger->error('SCIM groups request failed', ['exception' => $e]);
			return [];
		}

		$response = json_decode($response->getBody(), true);
		$this->logger->debug('SCIM groups response: ' . json_encode($response));

		$resources = $response['Resources'] ?? [];

		return array_map(
			static fn (array $resource): array => [
				'id' => (string)($resource['id'] ?? ''),
				'displayName' => (string)($resource['displayName'] ?? ''),
			],
			$resources
		);
	}

	/**
	 * @throws Exception
	 */
	public function createCircle(string $singleId, string $name): void {
		$this->federatedUserService->setLocalCurrentApp(Application::APP_ID, Member::APP_CIRCLES);
		$owner = $this->federatedUserService->getCurrentApp();

		$config = Circle::CFG_ROOT + Circle::CFG_FEDERATED + Circle::CFG_THIRD_PARTY;

		$circle = new Circle();
		$circle->setName($this->circleService->cleanCircleName($name))
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
	}

	private function mockGroups(): array {
		return [
			['id' => 'urn:geant:company.co:group:dev_vo1#login.company.co', 'displayName' => 'dev_vo1'],
			['id' => 'urn:geant:company.co:group:dev_vo2#login.company.co', 'displayName' => 'dev_vo2'],
			['id' => 'urn:geant:company.co:group:dev_vo3#login.company.co', 'displayName' => 'dev_vo3'],
			['id' => 'urn:geant:company.co:group:dev_vo4#login.company.co', 'displayName' => 'dev_vo4'],
		];
	}
}

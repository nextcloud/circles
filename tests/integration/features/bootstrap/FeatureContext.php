<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context {
	private const TEST_PASSWORD = '123456';
	private const ADMIN_USER = 'admin';
	private const ADMIN_PASSWORD = 'admin';
	private const ANONYMOUS_USER = 'anonymous';

	private ?ResponseInterface $response = null;
	private ?string $currentUser = null;
	private array $createdUsers = [];
	private array $createdCircles = [];

	// Behat scenarios cannot return values, so created circles are kept
	// in an alias -> singleId register to be referenced by later steps.
	private array $circleIds = [];

	public function __construct(
		private readonly string $baseUrl,
		private readonly string $ocsUrl,
	) {
	}

	/**
	 * @BeforeScenario
	 */
	public function setUp(): void {
		$this->createdUsers = [];
		$this->createdCircles = [];
		$this->circleIds = [];
	}

	/**
	 * @AfterScenario
	 */
	public function tearDown(): void {
		foreach ($this->createdCircles as $circle) {
			$this->setCurrentUser($circle['user']);
			$this->sendOcsRequest('DELETE', '/apps/circles/circles/' . $circle['id']);
		}
		$this->createdCircles = [];

		$this->setCurrentUser(self::ADMIN_USER);
		foreach ($this->createdUsers as $user) {
			$this->sendOcsRequest('DELETE', '/cloud/users/' . $user);
		}
		$this->createdUsers = [];
	}

	/**
	 * @Given user :user exists
	 */
	public function assureUserExists(string $user): void {
		$this->setCurrentUser(self::ADMIN_USER);
		$this->sendOcsRequest('GET', '/cloud/users/' . $user);
		if ($this->response->getStatusCode() === 200) {
			return;
		}

		$this->sendOcsRequest('POST', '/cloud/users', [
			'userid' => $user,
			'password' => self::TEST_PASSWORD,
		]);
		$this->assertStatusCode(200, 'Failed to create user ' . $user);
		$this->createdUsers[] = $user;
	}

	/**
	 * @When user :user sends an OCS :verb request to :url
	 * @When user :user sends an OCS :verb request to :url with:
	 */
	public function userSendsOcsRequest(string $user, string $verb, string $url, ?TableNode $body = null): void {
		$this->setCurrentUser($user);
		$this->sendOcsRequest($verb, $url, $body?->getRowsHash());
	}

	/**
	 * @Given user :user creates circle :circle as :alias
	 */
	public function userCreatesCircle(string $user, string $circle, string $alias): void {
		$this->setCurrentUser($user);
		$this->sendOcsRequest('POST', '/apps/circles/circles', [
			'name' => $circle,
			'createTeamFolder' => '0',
		]);
		$this->assertStatusCode(200, 'Failed to create circle ' . $circle);

		$singleId = $this->getJson()['ocs']['data']['id'] ?? null;
		Assert::assertNotNull($singleId, 'Circle id missing in create response');
		$this->circleIds[$alias] = $singleId;
		$this->createdCircles[] = ['user' => $user, 'id' => $singleId];
	}

	/**
	 * @Given user :user adds user :member to circle :alias
	 */
	public function userAddsMemberToCircle(string $user, string $member, string $alias): void {
		$this->setCurrentUser($user);
		$this->sendOcsRequest('POST', '/apps/circles/circles/' . $this->circleIdByAlias($alias) . '/members', [
			'userId' => $member,
			'type' => 1, // Member::TYPE_USER
		]);
		$this->assertStatusCode(200, 'Failed to add member ' . $member);
	}

	/**
	 * @When user :user fetches members of circle :alias
	 */
	public function userFetchesMembersOfCircle(string $user, string $alias): void {
		$this->setCurrentUser($user);
		$this->sendOcsRequest('GET', '/apps/circles/circles/' . $this->circleIdByAlias($alias) . '/members');
	}

	/**
	 * @Then the circles list should contain circle :alias
	 */
	public function circlesListShouldContainCircle(string $alias): void {
		$circleId = $this->circleIdByAlias($alias);
		foreach ($this->getJson()['ocs']['data'] ?? [] as $circle) {
			if (($circle['id'] ?? null) === $circleId) {
				return;
			}
		}
		Assert::fail('Circle ' . $alias . ' not found in circles list');
	}

	/**
	 * @Then the response data should contain :count entries
	 */
	public function responseDataShouldContainNEntries(int $count): void {
		Assert::assertCount($count, $this->getJson()['ocs']['data'] ?? []);
	}

	/**
	 * @Then the members should contain user :user with level :level
	 */
	public function membersShouldContainUserWithLevel(string $user, int $level): void {
		foreach ($this->getJson()['ocs']['data'] ?? [] as $member) {
			if (($member['userId'] ?? null) === $user) {
				Assert::assertSame($level, $member['level'] ?? null, 'Unexpected member level for ' . $user);
				return;
			}
		}
		Assert::fail('User ' . $user . ' not found in members list');
	}

	/**
	 * @Then the last response should have a :statusCode status code
	 */
	public function theLastResponseShouldHaveAStatusCode(int $statusCode): void {
		$this->assertStatusCode($statusCode);
	}

	/**
	 * @Then the OCS meta status should be :status
	 */
	public function ocsMetaStatusShouldBe(string $status): void {
		Assert::assertSame($status, $this->getJson()['ocs']['meta']['status'] ?? null);
	}

	/**
	 * @Then the response should contain a list of circles
	 */
	public function responseShouldContainAListOfCircles(): void {
		Assert::assertIsArray($this->getJson()['ocs']['data'] ?? null);
	}

	private function sendOcsRequest(string $verb, string $url, ?array $formParams = null): void {
		$fullUrl = $this->ocsUrl . $url;
		$fullUrl .= str_contains($fullUrl, '?') ? '&' : '?';
		$fullUrl .= 'format=json';

		$options = [
			'headers' => [
				'OCS-APIRequest' => 'true',
				'Accept' => 'application/json',
			],
		];

		if ($this->currentUser === self::ADMIN_USER) {
			$options['auth'] = [self::ADMIN_USER, self::ADMIN_PASSWORD];
		} elseif ($this->currentUser !== self::ANONYMOUS_USER) {
			$options['auth'] = [$this->currentUser, self::TEST_PASSWORD];
		}

		if ($formParams !== null) {
			$options['form_params'] = $formParams;
		}

		$client = new Client(['verify' => false]);
		try {
			$this->response = $client->request($verb, $fullUrl, $options);
		} catch (ClientException|ServerException $e) {
			$this->response = $e->getResponse();
		}
	}

	private function getJson(): array {
		return json_decode((string)$this->response->getBody(), true) ?? [];
	}

	private function setCurrentUser(string $user): void {
		$this->currentUser = $user;
	}

	private function circleIdByAlias(string $alias): string {
		Assert::assertArrayHasKey($alias, $this->circleIds, 'Unknown circle alias ' . $alias);
		return $this->circleIds[$alias];
	}

	private function assertStatusCode(int $statusCode, string $message = ''): void {
		Assert::assertSame(
			$statusCode,
			$this->response->getStatusCode(),
			$message . ($message !== '' ? ': ' : '') . (string)$this->response->getBody()
		);
	}
}

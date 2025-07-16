<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Service\ShareWrapperService;
use OCA\Circles\ShareByCircleProvider;
use OCP\Defaults;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Share\IShare;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShareByCircleProviderTest extends TestCase {
	public function testSendShareNotificationCallsSendUserShareMail(): void {
		$share = $this->createConfiguredMock(IShare::class, [
			'getId' => 42,
			'getToken' => 'abc123',
			'getSharedBy' => 'user123',
			'getExpirationDate' => null,
			'getNote' => ''
		]);
		$node = $this->createConfiguredMock(\OCP\Files\File::class, ['getName' => 'testfile.txt']);
		$share->method('getNode')->willReturn($node);

		$member = $this->createConfiguredMock(\OCA\Circles\Model\Member::class, [
			'getUserType' => 1,
			'getUserId' => 'user123',
		]);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => [$member]]);

		$user = $this->createConfiguredMock(IUser::class, [
			'getEMailAddress' => 'user@example.com',
			'getDisplayName' => 'User 123',
		]);

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->with('user123')->willReturn($user);

		$mailer = $this->createMock(IMailer::class);
		$mailer->method('validateMailAddress')->willReturn(true);

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')->willReturn('https://nextcloud.test/some/link');

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(fn ($text, $args = []) => vsprintf($text, $args));

		$provider = $this->buildProvider([
			'userManager' => $userManager,
			'mailer' => $mailer,
			'urlGenerator' => $urlGenerator,
			'l10n' => $l10n,
		], ['sendUserShareMail']);

		$provider->expects($this->once())
			->method('sendUserShareMail')
			->with(
				'https://nextcloud.test/some/link',
				'user@example.com',
				$share
			);

		$provider->sendShareNotification($share, $circle);
	}

	public function testNoMailIsSentWhenSharingMailIsDisabled(): void {
		$config = $this->createConfiguredMock(IConfig::class, ['getSystemValueBool' => false]);

		$provider = $this->buildProvider(['config' => $config], ['sendUserShareMail']);

		$share = $this->createMock(IShare::class);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => []]);

		$provider->sendShareNotification($share, $circle);
		$this->assertTrue(true); // no exception = pass
	}

	public function testNonUserTypeMembersAreSkipped(): void {
		$member = $this->createConfiguredMock(\OCA\Circles\Model\Member::class, [
			'getUserType' => \OCA\Circles\Model\Member::TYPE_GROUP,
		]);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => [$member]]);
		$share = $this->createMock(IShare::class);

		$provider = $this->buildProvider([], ['sendUserShareMail']);

		$provider->expects($this->never())->method('sendUserShareMail');
		$provider->sendShareNotification($share, $circle);
	}

	public function testNullUserIsSkipped(): void {
		$member = $this->createConfiguredMock(\OCA\Circles\Model\Member::class, [
			'getUserType' => 1,
			'getUserId' => 'user123'
		]);

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->with('user123')->willReturn(null);

		$provider = $this->buildProvider([
			'userManager' => $userManager
		], ['sendUserShareMail']);

		$share = $this->createMock(IShare::class);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => [$member]]);

		$provider->expects($this->never())->method('sendUserShareMail');
		$provider->sendShareNotification($share, $circle);
	}

	public function testInvalidOrEmptyEmailIsSkipped(): void {
		$member = $this->createConfiguredMock(\OCA\Circles\Model\Member::class, [
			'getUserType' => 1,
			'getUserId' => 'user123'
		]);

		$user = $this->createConfiguredMock(IUser::class, [
			'getEMailAddress' => ''
		]);

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->willReturn($user);

		$mailer = $this->createMock(IMailer::class);
		$mailer->method('validateMailAddress')->willReturn(false);

		$provider = $this->buildProvider([
			'userManager' => $userManager,
			'mailer' => $mailer
		], ['sendUserShareMail']);

		$share = $this->createMock(IShare::class);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => [$member]]);

		$provider->expects($this->never())->method('sendUserShareMail');
		$provider->sendShareNotification($share, $circle);
	}

	public function testAllValidMembersReceiveEmail(): void {
		$userManager = $this->createMock(IUserManager::class);
		$mailer = $this->createMock(IMailer::class);
		$mailer->method('validateMailAddress')->willReturn(true);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')->willReturn('https://nextcloud.test/some/link');

		$userMap = [];
		$members = [];

		for ($i = 0; $i < 3; $i++) {
			$userId = "user$i";
			$email = "$userId@example.com";

			$member = $this->createConfiguredMock(\OCA\Circles\Model\Member::class, [
				'getUserType' => 1,
				'getUserId' => $userId
			]);
			$members[] = $member;

			$user = $this->createConfiguredMock(IUser::class, [
				'getEMailAddress' => $email,
				'getDisplayName' => "User $i"
			]);

			$userMap[] = [$userId, $user];
		}
		$userManager->method('get')->willReturnMap($userMap);

		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => $members]);

		$node = $this->createConfiguredMock(\OCP\Files\File::class, ['getName' => 'test.txt']);

		$share = $this->createConfiguredMock(IShare::class, [
			'getNode' => $node,
			'getToken' => 'abc123',
			'getSharedBy' => 'initiator',
			'getExpirationDate' => null,
			'getNote' => ''
		]);

		$provider = $this->buildProvider([
			'userManager' => $userManager,
			'mailer' => $mailer,
			'urlGenerator' => $urlGenerator
		], ['sendUserShareMail']);

		$provider->expects($this->exactly(3))->method('sendUserShareMail');
		$provider->sendShareNotification($share, $circle);
	}

	private function buildProvider(array $overrides = [], array $mockMethods = []) {
		$mailer = $overrides['mailer'] ?? $this->createMock(IMailer::class);
		$l10n = $overrides['l10n'] ?? $this->createMock(IL10N::class);
		$logger = $overrides['logger'] ?? $this->createMock(LoggerInterface::class);
		$urlGenerator = $overrides['urlGenerator'] ?? $this->createMock(IURLGenerator::class);
		$config = $overrides['config'] ?? $this->createConfiguredMock(IConfig::class, ['getSystemValueBool' => true]);
		$userManager = $overrides['userManager'] ?? $this->createMock(IUserManager::class);
		$defaults = $overrides['defaults'] ?? $this->createConfiguredMock(Defaults::class, ['getName' => 'Nextcloud']);
		$rootFolder = $overrides['rootFolder'] ?? $this->createMock(IRootFolder::class);
		$shareWrapperService = $overrides['shareWrapperService'] ?? $this->createMock(ShareWrapperService::class);
		$shareTokenService = $overrides['shareTokenService'] ?? $this->createMock(ShareTokenService::class);
		$federatedUserService = $overrides['federatedUserService'] ?? $this->createMock(FederatedUserService::class);
		$federatedEventService = $overrides['federatedEventService'] ?? $this->createMock(FederatedEventService::class);
		$circleService = $overrides['circleService'] ?? $this->createMock(CircleService::class);
		$eventService = $overrides['eventService'] ?? $this->createMock(EventService::class);

		$provider = $this->getMockBuilder(ShareByCircleProvider::class)
			->setConstructorArgs([
				$userManager,
				$rootFolder,
				$l10n,
				$logger,
				$urlGenerator,
				$shareWrapperService,
				$shareTokenService,
				$federatedUserService,
				$federatedEventService,
				$circleService,
				$eventService,
				$defaults,
				$mailer,
				$config,
			]);

		if (!empty($mockMethods)) {
			$provider->onlyMethods($mockMethods);
		}

		return $provider->getMock();
	}


}

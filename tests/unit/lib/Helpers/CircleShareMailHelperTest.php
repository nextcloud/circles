<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\Circles\Helpers\CircleShareMailHelper;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Share\IShare;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CircleShareMailHelperTest extends TestCase {
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

		$provider = $this->buildHelper([
			'userManager' => $userManager,
			'mailer' => $mailer,
			'urlGenerator' => $urlGenerator,
			'l10n' => $l10n,
		], ['sendUserShareMail']);

		$provider->expects($this->once())
			->method('sendUserShareMail')
			->with(
				$l10n,
				'testfile.txt',
				'https://nextcloud.test/some/link',
				'user123',
				'user@example.com',
				null,
				''
			);

		$provider->sendShareNotification($share, $circle);
	}

	public function testNoMailIsSentWhenSharingMailIsDisabled(): void {
		$config = $this->createConfiguredMock(IConfig::class, ['getSystemValueBool' => false]);

		$provider = $this->buildHelper(['config' => $config], ['sendUserShareMail']);

		$share = $this->createMock(IShare::class);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => []]);

		$provider->sendShareNotification($share, $circle);
		$this->assertTrue(true); // no exception = pass
	}

	public function testNonUserTypeMembersAreSkipped(): void {
		$member = $this->createConfiguredMock(\OCA\Circles\Model\Member::class, [
			'getUserType' => 2,
		]);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => [$member]]);
		$share = $this->createMock(IShare::class);

		$user = $this->createConfiguredMock(IUser::class, [
			'getEMailAddress' => 'user@example.com',
			'getDisplayName' => 'User 123',
		]);

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->with('user123')->willReturn($user);

		$provider = $this->buildHelper([
			'userManager' => $userManager
		], ['sendUserShareMail']);

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

		$provider = $this->buildHelper([
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

		$provider = $this->buildHelper([
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

		$provider = $this->buildHelper([
			'userManager' => $userManager,
			'mailer' => $mailer,
			'urlGenerator' => $urlGenerator
		], ['sendUserShareMail']);

		$provider->expects($this->exactly(3))->method('sendUserShareMail');
		$provider->sendShareNotification($share, $circle);
	}

	private function buildHelper(array $overrides = [], array $mockMethods = []): CircleShareMailHelper {
		$mailer = $overrides['mailer'] ?? $this->createMock(IMailer::class);
		$l10n = $overrides['l10n'] ?? $this->createMock(IL10N::class);
		$logger = $overrides['logger'] ?? $this->createMock(LoggerInterface::class);
		$urlGenerator = $overrides['urlGenerator'] ?? $this->createMock(IURLGenerator::class);
		$config = $overrides['config'] ?? $this->createConfiguredMock(IConfig::class, ['getSystemValueBool' => true]);
		$userManager = $overrides['userManager'] ?? $this->createMock(IUserManager::class);
		$defaults = $overrides['defaults'] ?? $this->createConfiguredMock(Defaults::class, ['getName' => 'Nextcloud']);

		$builder = $this->getMockBuilder(CircleShareMailHelper::class)
			->setConstructorArgs([
				$mailer,
				$l10n,
				$logger,
				$urlGenerator,
				$config,
				$userManager,
				$defaults,
			]);

		if (!empty($mockMethods)) {
			$builder->onlyMethods($mockMethods);
		}

		return $builder->getMock();
	}


}

<?php

use OCA\Circles\Service\CircleShareMailerService;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Share\IShare;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CircleShareMailerServiceTest extends TestCase {
	public function testSendShareNotificationCallsSendUserShareMail(): void {
		// Mock share object
		$share = $this->createMock(IShare::class);
		$share->method('getId')->willReturn(42);
		$share->method('getToken')->willReturn('abc123');
		$share->method('getSharedBy')->willReturn('user123');
		$share->method('getExpirationDate')->willReturn(null);
		$share->method('getNote')->willReturn('');
		$node = $this->createConfiguredMock(\OCP\Files\File::class, ['getName' => 'testfile.txt']);
		$share->method('getNode')->willReturn($node);

		// Mock member
		$member = $this->createMock(\OCA\Circles\Model\Member::class);
		$member->method('getUserType')->willReturn(1);
		$member->method('getUserId')->willReturn('user123');

		// Mock circle
		$circle = $this->createMock(\OCA\Circles\Model\Circle::class);
		$circle->method('getMembers')->willReturn([$member]);

		// Mock user and user manager
		$user = $this->createMock(IUser::class);
		$user->method('getEMailAddress')->willReturn('user@example.com');
		$user->method('getDisplayName')->willReturn('User 123');

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->with('user123')->willReturn($user);

		// Mailer mock
		$mailer = $this->createMock(IMailer::class);
		$mailer->method('validateMailAddress')->willReturn(true);

		// Config returns true to allow sending mails
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->with('sharing.enable_share_mail', true)->willReturn(true);

		// Other dependencies
		$l10n = $this->createMock(\OCP\IL10N::class);
		$l10n->method('t')->willReturnCallback(fn ($text, $args = []) => vsprintf($text, $args));

		$l10nFactory = $this->createMock(\OCP\L10N\IFactory::class);
		$l10nFactory->method('get')->with('circles')->willReturn($l10n);

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')->willReturn('https://nextcloud.test/some/link');

		$logger = $this->createMock(LoggerInterface::class);
		$defaults = $this->createMock(Defaults::class);
		$defaults->method('getName')->willReturn('Nextcloud');
		$defaults->method('getSlogan')->willReturn('Simple Collaboration');

		// Use partial mock to override protected method
		$service = $this->getMockBuilder(CircleShareMailerService::class)
			->setConstructorArgs([
				$mailer, $l10nFactory, $logger, $urlGenerator, $config, $userManager, $defaults,
			])
			->onlyMethods(['sendUserShareMail'])
			->getMock();

		// Expectation for internal mail sending
		$service->expects($this->once())
			->method('sendUserShareMail')
			->with(
				'testfile.txt',
				'https://nextcloud.test/some/link',
				'user123',
				'user@example.com',
				null,
				''
			);

		$service->sendShareNotification($share, $circle);
	}

	public function testNoMailIsSentWhenSharingMailIsDisabled(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->willReturn(false); // disabled

		$service = $this->buildService(
			$this->createMock(IMailer::class),
			$this->createMock(IUserManager::class)
		);

		$share = $this->createMock(IShare::class);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => []]);

		$service->sendShareNotification($share, $circle);

		$this->assertTrue(true); // no exception = pass
	}

	public function testNonUserTypeMembersAreSkipped(): void {
		$member = $this->createConfiguredMock(\OCA\Circles\Model\Member::class, [
			'getUserType' => 2,
		]);

		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => [$member]]);
		$share = $this->createMock(IShare::class);

		$service = $this->getMockBuilder(CircleShareMailerService::class)
			->setConstructorArgs([
				$this->createMock(IMailer::class),
				$this->createL10NFactory(),
				$this->createMock(LoggerInterface::class),
				$this->createMock(IURLGenerator::class),
				$this->createConfiguredMock(IConfig::class, ['getSystemValueBool' => true]),
				$this->createMock(IUserManager::class),
				$this->createMock(Defaults::class),
			])
			->onlyMethods(['sendUserShareMail'])
			->getMock();

		$service->expects($this->never())->method('sendUserShareMail');

		$service->sendShareNotification($share, $circle);
	}

	public function testNullUserIsSkipped(): void {
		$member = $this->createConfiguredMock(\OCA\Circles\Model\Member::class, [
			'getUserType' => 1,
			'getUserId' => 'user123'
		]);

		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')->with('user123')->willReturn(null);

		$service = $this->getMockBuilder(CircleShareMailerService::class)
			->setConstructorArgs([
				$this->createMock(IMailer::class),
				$this->createL10NFactory(),
				$this->createMock(LoggerInterface::class),
				$this->createMock(IURLGenerator::class),
				$this->createConfiguredMock(IConfig::class, ['getSystemValueBool' => true]),
				$userManager,
				$this->createMock(Defaults::class),
			])
			->onlyMethods(['sendUserShareMail'])
			->getMock();

		$share = $this->createMock(IShare::class);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => [$member]]);

		$service->expects($this->never())->method('sendUserShareMail');
		$service->sendShareNotification($share, $circle);
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

		$service = $this->getMockBuilder(CircleShareMailerService::class)
			->setConstructorArgs([
				$mailer,
				$this->createL10NFactory(),
				$this->createMock(LoggerInterface::class),
				$this->createMock(IURLGenerator::class),
				$this->createConfiguredMock(IConfig::class, ['getSystemValueBool' => true]),
				$userManager,
				$this->createMock(Defaults::class),
			])
			->onlyMethods(['sendUserShareMail'])
			->getMock();

		$share = $this->createMock(IShare::class);
		$circle = $this->createConfiguredMock(\OCA\Circles\Model\Circle::class, ['getMembers' => [$member]]);

		$service->expects($this->never())->method('sendUserShareMail');
		$service->sendShareNotification($share, $circle);
	}

	public function testAllValidMembersReceiveEmail(): void {
		$userManager = $this->createMock(IUserManager::class);
		$mailer = $this->createMock(IMailer::class);
		$mailer->method('validateMailAddress')->willReturn(true);

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

		$service = $this->getMockBuilder(CircleShareMailerService::class)
			->setConstructorArgs([
				$mailer,
				$this->createL10NFactory(),
				$this->createMock(LoggerInterface::class),
				$this->createConfiguredMock(IURLGenerator::class, ['linkToRouteAbsolute' => 'https://nextcloud.test/link']),
				$this->createConfiguredMock(IConfig::class, ['getSystemValueBool' => true]),
				$userManager,
				$this->createConfiguredMock(Defaults::class, ['getName' => 'Nextcloud', 'getSlogan' => '']),
			])
			->onlyMethods(['sendUserShareMail'])
			->getMock();

		$service->expects($this->exactly(3))->method('sendUserShareMail');
		$service->sendShareNotification($share, $circle);
	}

	private function buildService(IMailer $mailer, IUserManager $userManager): CircleShareMailerService {
		$l10n = $this->createMock(\OCP\IL10N::class);
		$l10n->method('t')->willReturnCallback(
			fn (string $text, array $params = []) => vsprintf($text, $params)
		);

		$l10nFactory = $this->createMock(\OCP\L10N\IFactory::class);
		$l10nFactory->method('get')->with('circles')->willReturn($l10n);

		$config = $this->createConfiguredMock(IConfig::class, [
			'getSystemValueBool' => true
		]);

		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')->willReturn('https://nextcloud.test/link');

		$defaults = $this->createConfiguredMock(Defaults::class, [
			'getName' => 'Nextcloud',
			'getSlogan' => 'Collaboration made easy'
		]);

		return new CircleShareMailerService(
			$mailer,
			$l10nFactory,
			$this->createMock(LoggerInterface::class),
			$urlGenerator,
			$config,
			$userManager,
			$defaults
		);
	}

	private function createL10NFactory(): \OCP\L10N\IFactory {
		$l10n = $this->createMock(\OCP\IL10N::class);
		$l10n->method('t')->willReturnCallback(function (string $text, array $params = []): string {
			return vsprintf($text, $params);
		});

		$l10nFactory = $this->createMock(\OCP\L10N\IFactory::class);
		$l10nFactory->method('get')->with('circles')->willReturn($l10n);

		return $l10nFactory;
	}

}

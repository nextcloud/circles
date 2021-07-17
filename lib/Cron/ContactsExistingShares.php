<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Cron;

use ArtificialOwl\MySmallPhpTools\Traits\TArrayTools;
use OC\BackgroundJob\TimedJob;
use OC\Share20\Share;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Circles\FileSharingBroadcaster;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Db\FileSharesRequest;
use OCA\Circles\Db\TokensRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SharesToken;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\DavService;
use OCA\Circles\Service\MiscService;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IShare;

/**
 * Class GlobalSync
 *
 * @package OCA\Cicles\Cron
 */
class ContactsExistingShares extends TimedJob {
	use TArrayTools;


	/** @var IRootFolder */
	private $rootFolder;

	/** @var IUserManager */
	private $userManager;

	/** @var DavService */
	private $davService;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var FileSharesRequest */
	private $fileSharesRequest;

	/** @var TokensRequest */
	private $tokensRequest;

	/** @var FileSharingBroadcaster */
	private $fileSharingBroadcaster;

	/** @var MiscService */
	private $miscService;


	/**
	 * Cache constructor.
	 */
	public function __construct() {
		$this->setInterval(1);
	}


	/**
	 * @param mixed $argument
	 */
	protected function run($argument) {
		return;
		$app = \OC::$server->query(Application::class);
		$c = $app->getContainer();

		$this->davService = $c->query(DavService::class);
		$this->rootFolder = $c->query(IRootFolder::class);
		$this->userManager = $c->query(IUserManager::class);
		$this->membersRequest = $c->query(DeprecatedMembersRequest::class);
		$this->circlesRequest = $c->query(DeprecatedCirclesRequest::class);
		$this->tokensRequest = $c->query(TokensRequest::class);
		$this->fileSharesRequest = $c->query(FileSharesRequest::class);
		$this->fileSharingBroadcaster = $c->query(FileSharingBroadcaster::class);
		$this->miscService = $c->query(MiscService::class);

		$configService = $c->query(ConfigService::class);
		if (!$configService->isContactsBackend()) {
			return;
		}

		$this->fileSharingBroadcaster->init();

		$members = $this->getNewMembers();
	}


	/**
	 * @return DeprecatedMember[]
	 * @throws GSStatusException
	 */
	private function getNewMembers(): array {
		$knownMembers = $this->membersRequest->forceGetAllRecentContactEdit();

		$members = [];
		foreach ($knownMembers as $member) {
			try {
				$circle = $this->circlesRequest->forceGetCircle($member->getCircleId());
			} catch (CircleDoesNotExistException $e) {
				continue;
			}

			try {
				$davCard = $this->davService->getDavCardFromMember($member);
			} catch (MemberDoesNotExistException $e) {
				continue;
			}

			$contactMeta = $member->getContactMeta();
			$missingMails = array_diff(
				$davCard->getEmails(), $this->getArray('existing_shares.emails', $contactMeta, [])
			);
			$missingClouds = array_diff(
				$davCard->getClouds(), $this->getArray('existing_shares.clouds', $contactMeta, [])
			);

			$owners = $this->membersRequest->forceGetMembers($member->getCircleId(), DeprecatedMember::LEVEL_OWNER);
			$owner = $owners[0];

			// send mail to $missingMails
			$allShares = $this->fileSharesRequest->getSharesForCircle($member->getCircleId());

			foreach ($missingMails as $recipient) {
				$this->fileSharingBroadcaster->sendMailExitingShares(
					$circle, $allShares, $owner, $member, $recipient
				);
				$this->updateContactMeta($member, 'emails', $recipient);
			}

			foreach ($missingClouds as $cloudId) {
				foreach ($allShares as $item) {
					try {
						$share = $this->generateShare($item);
						$sharesToken =
							$this->tokensRequest->generateTokenForMember($member, (int)$share->getId());

						if ($this->fileSharingBroadcaster->sharedByFederated(
							$circle, $share, $cloudId, $sharesToken
						)) {
							$this->updateContactMeta($member, 'clouds', $cloudId);
						}
					} catch (IllegalIDChangeException | TokenDoesNotExistException $e) {
					}
				}
			}
		}

		return $members;
	}


	/**
	 * @param int $id
	 * @param SharesToken[] $tokens
	 *
	 * @return SharesToken
	 * @throws TokenDoesNotExistException
	 */
	private function getSharesTokenById(int $id, array $tokens): SharesToken {
		foreach ($tokens as $token) {
			if ($token->getShareId() === $id) {
				return $token;
			}
		}

		throw new TokenDoesNotExistException();
	}


	/**
	 * @param DeprecatedMember $member
	 * @param string $key
	 * @param string $value
	 */
	private function updateContactMeta(DeprecatedMember $member, string $key, string $value) {
		$current = $member->getContactMeta();
		if (!array_key_exists('existing_shares', $current)) {
			$current['existing_shares'] = [];
		}

		if (!array_key_exists($key, $current['existing_shares'])) {
			$current['existing_shares'][$key] = [];
		}

		$current['existing_shares'][$key][] = $value;

		$member->setContactMeta($current);
		$this->membersRequest->updateContactMeta($member);
	}


	/**
	 * @param $data
	 *
	 * @return IShare
	 */
	private function generateShare($data): IShare {
		$share = new Share($this->rootFolder, $this->userManager);

		try {
			$share->setId($data['id']);
		} catch (IllegalIDChangeException $e) {
		}
		$share->setSharedBy($data['uid_initiator']);
		$share->setSharedWith($data['share_with']);
		$share->setNodeId($data['file_source']);
		$share->setShareOwner($data['uid_owner']);
		$share->setPermissions($data['permissions']);
		$share->setToken($data['token']);
		$share->setPassword($data['password']);

		return $share;
	}
}

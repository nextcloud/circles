<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles\Model;

use ArtificialOwl\MySmallPhpTools\Db\Nextcloud\nc22\INC22QueryRow;
use ArtificialOwl\MySmallPhpTools\Exceptions\InvalidItemException;
use ArtificialOwl\MySmallPhpTools\IDeserializable;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Deserialize;
use ArtificialOwl\MySmallPhpTools\Traits\TArrayTools;
use DateTime;
use JsonSerializable;
use OC;
use OC\Files\Cache\Cache;
use OC\Share20\Share;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\ShareByCircleProvider;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IShare;

/**
 * Class ShareWrapper
 *
 * @package OCA\Circles\Model
 */
class ShareWrapper extends ManagedModel implements IDeserializable, INC22QueryRow, JsonSerializable {
	use TArrayTools;
	use TNC22Deserialize;


	/** @var string */
	private $id = '';

	/** @var int */
	private $permissions = 0;

	/** @var string */
	private $itemType = '';

	/** @var int */
	private $itemSource = 0;

	/** @var string */
	private $itemTarget = '';

	/** @var int */
	private $fileSource = 0;

	/** @var string */
	private $fileTarget = '';

	/** @var string */
	private $token = '';

	/** @var int */
	private $status = 0;

	/** @var string */
	private $providerId = '';

	/** @var DateTime */
	private $shareTime = '';

	/** @var string */
	private $sharedWith = '';

	/** @var string */
	private $sharedBy = '';

	/** @var string */
	private $shareOwner = '';

	/** @var int */
	private $shareType = 0;

	/** @var Circle */
	private $circle;

	/** @var int */
	private $childId = 0;

	/** @var string */
	private $childFileTarget = '';

	/** @var int */
	private $childPermissions = 0;

	/** @var FileCacheWrapper */
	private $fileCache;

	/** @var Member */
	private $initiator;

	/** @var Member */
	private $owner;

	/** @var ShareToken */
	private $shareToken;


	/**
	 * @param string $id
	 *
	 * @return ShareWrapper
	 */
	public function setId(string $id): self {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}


	/**
	 * @param int $permissions
	 *
	 * @return ShareWrapper
	 */
	public function setPermissions(int $permissions): self {
		$this->permissions = $permissions;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPermissions(): int {
		return $this->permissions;
	}


	/**
	 * @param string $itemType
	 *
	 * @return ShareWrapper
	 */
	public function setItemType(string $itemType): self {
		$this->itemType = $itemType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getItemType(): string {
		return $this->itemType;
	}


	/**
	 * @param int $itemSource
	 *
	 * @return ShareWrapper
	 */
	public function setItemSource(int $itemSource): self {
		$this->itemSource = $itemSource;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getItemSource(): int {
		return $this->itemSource;
	}


	/**
	 * @param string $itemTarget
	 *
	 * @return ShareWrapper
	 */
	public function setItemTarget(string $itemTarget): self {
		$this->itemTarget = $itemTarget;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getItemTarget(): string {
		return $this->itemTarget;
	}


	/**
	 * @param int $fileSource
	 *
	 * @return ShareWrapper
	 */
	public function setFileSource(int $fileSource): self {
		$this->fileSource = $fileSource;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getFileSource(): int {
		return $this->fileSource;
	}


	/**
	 * @param string $fileTarget
	 *
	 * @return ShareWrapper
	 */
	public function setFileTarget(string $fileTarget): self {
		$this->fileTarget = $fileTarget;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFileTarget(): string {
		return $this->fileTarget;
	}


	/**
	 * @param string $token
	 *
	 * @return ShareWrapper
	 */
	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}


	/**
	 * @param int $status
	 *
	 * @return ShareWrapper
	 */
	public function setStatus(int $status): self {
		$this->status = $status;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}


	/**
	 * @param string $providerId
	 *
	 * @return $this
	 */
	public function setProviderId(string $providerId): self {
		$this->providerId = $providerId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getProviderId(): string {
		return $this->providerId;
	}


	/**
	 * @param DateTime $shareTime
	 *
	 * @return ShareWrapper
	 */
	public function setShareTime(DateTime $shareTime): self {
		$this->shareTime = $shareTime;

		return $this;
	}

	/**
	 * @return DateTime
	 */
	public function getShareTime(): DateTime {
		return $this->shareTime;
	}


	/**
	 * @param string $sharedWith
	 *
	 * @return ShareWrapper
	 */
	public function setSharedWith(string $sharedWith): self {
		$this->sharedWith = $sharedWith;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSharedWith(): string {
		return $this->sharedWith;
	}

	/**
	 * @param string $sharedBy
	 *
	 * @return ShareWrapper
	 */
	public function setSharedBy(string $sharedBy): self {
		$this->sharedBy = $sharedBy;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSharedBy(): string {
		return $this->sharedBy;
	}


	/**
	 * @param string $shareOwner
	 *
	 * @return ShareWrapper
	 */
	public function setShareOwner(string $shareOwner): self {
		$this->shareOwner = $shareOwner;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getShareOwner(): string {
		return $this->shareOwner;
	}


	/**
	 * @param int $shareType
	 *
	 * @return ShareWrapper
	 */
	public function setShareType(int $shareType): self {
		$this->shareType = $shareType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getShareType(): int {
		return $this->shareType;
	}


	/**
	 * @param Circle $circle
	 *
	 * @return ShareWrapper
	 */
	public function setCircle(Circle $circle): self {
		$this->circle = $circle;

		return $this;
	}

	/**
	 * @return Circle
	 */
	public function getCircle(): Circle {
		return $this->circle;
	}

	/**
	 * @return bool
	 */
	public function hasCircle(): bool {
		return (!is_null($this->circle));
	}


	/**
	 * @param int $childId
	 *
	 * @return ShareWrapper
	 */
	public function setChildId(int $childId): self {
		$this->childId = $childId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getChildId(): int {
		return $this->childId;
	}


	/**
	 * @param string $childFileTarget
	 *
	 * @return ShareWrapper
	 */
	public function setChildFileTarget(string $childFileTarget): self {
		$this->childFileTarget = $childFileTarget;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getChildFileTarget(): string {
		return $this->childFileTarget;
	}


	/**
	 * @param int $childPermissions
	 *
	 * @return ShareWrapper
	 */
	public function setChildPermissions(int $childPermissions): self {
		$this->childPermissions = $childPermissions;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getChildPermissions(): int {
		return $this->childPermissions;
	}


	/**
	 * @param FileCacheWrapper $fileCache
	 *
	 * @return $this
	 */
	public function setFileCache(FileCacheWrapper $fileCache): self {
		$this->fileCache = $fileCache;

		return $this;
	}

	/**
	 * @return FileCacheWrapper
	 */
	public function getFileCache(): FileCacheWrapper {
		return $this->fileCache;
	}

	/**
	 * @return bool
	 */
	public function hasFileCache(): bool {
		return (!is_null($this->fileCache));
	}


	/**
	 * @param Member $initiator
	 *
	 * @return ShareWrapper
	 */
	public function setInitiator(Member $initiator): self {
		$this->initiator = $initiator;

		return $this;
	}

	/**
	 * @return Member
	 */
	public function getInitiator(): Member {
		return $this->initiator;
	}

	/**
	 * @return bool
	 */
	public function hasInitiator(): bool {
		return (!is_null($this->initiator));
	}


	/**
	 * @param Member $owner
	 *
	 * @return ShareWrapper
	 */
	public function setOwner(Member $owner): self {
		$this->owner = $owner;

		return $this;
	}

	/**
	 * @return Member
	 */
	public function getOwner(): Member {
		return $this->owner;
	}

	/**
	 * @return bool
	 */
	public function hasOwner(): bool {
		return (!is_null($this->owner));
	}


	/**
	 * @param ShareToken $shareToken
	 *
	 * @return ShareWrapper
	 */
	public function setShareToken(ShareToken $shareToken): self {
		$this->shareToken = $shareToken;

		return $this;
	}

	/**
	 * @return ShareToken
	 */
	public function getShareToken(): ShareToken {
		return $this->shareToken;
	}

	/**
	 * @return bool
	 */
	public function hasShareToken(): bool {
		return !is_null($this->shareToken);
	}


	/**
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 * @param IURLGenerator $urlGenerator
	 * @param bool $nullOnMissingFileCache
	 *
	 * @return IShare
	 * @throws IllegalIDChangeException
	 */
	public function getShare(
		IRootFolder $rootFolder,
		IUserManager $userManager,
		IURLGenerator $urlGenerator,
		bool $nullOnMissingFileCache = false
	): ?IShare {
		$share = new Share($rootFolder, $userManager);
		$share->setId($this->getId());
		$share->setPermissions($this->getPermissions());
		$share->setNodeType($this->getItemType());
		$share->setNodeId($this->getFileSource());
		$share->setTarget($this->getFileTarget());
		$share->setProviderId($this->getProviderId());
		$share->setStatus($this->getStatus());

		$share->setShareTime($this->getShareTime())
			  ->setSharedWith($this->getSharedWith())
			  ->setSharedBy($this->getSharedBy())
			  ->setShareOwner($this->getShareOwner())
			  ->setShareType($this->getShareType());

		if ($this->getChildId() > 0) {
			$share->setTarget($this->getChildFileTarget());
			if ($this->getChildPermissions() < $this->getPermissions()) {
				$share->setPermissions($this->getChildPermissions());
			}
		}

		$this->setShareDisplay($share, $urlGenerator);

		if ($this->hasFileCache()) {
			if (!$this->getFileCache()->isAccessible()) {
				return null;
			}
			$share->setNodeCacheEntry(
				Cache::cacheEntryFromData($this->getFileCache()->toCache(), OC::$server->getMimeTypeLoader())
			);
		} elseif ($nullOnMissingFileCache) {
			return null;
		}

		return $share;
	}


	/**
	 * @param IShare $share
	 * @param IURLGenerator $urlGenerator
	 */
	private function setShareDisplay(IShare $share, IURLGenerator $urlGenerator) {
		if (!$this->hasCircle()) {
			return;
		}

		$circle = $this->getCircle();
		if ($circle->isConfig(Circle::CFG_PERSONAL)
			&& $this->hasInitiator()
			&& $circle->getOwner()->getSingleId() !== $this->getInitiator()->getSingleId()) {
			$share->setSharedWithDisplayName(' ');

			return;
		}

		$display = $circle->getDisplayName();
		if ($circle->getSource() === Member::TYPE_CIRCLE) {
			$display .= ' (Circle owned by ' . $circle->getOwner()->getDisplayName() . ')';
		} else {
			$display .= ' (' . Circle::$DEF_SOURCE[$circle->getSource()] . ')';
		}

		$share->setSharedWithDisplayName($display);

		$icon = $urlGenerator->getAbsoluteURL(
			$urlGenerator->imagePath(Application::APP_ID, 'circles.svg')
		);
		$share->setSharedWithAvatar($icon);


//		if (array_key_exists('circle_type', $data)
//			&& method_exists($share, 'setSharedWithDisplayName')) {
//			$name = $data['circle_name'];
//			if ($data['circle_alt_name'] !== '') {
//				$name = $data['circle_alt_name'];
//			}
//
//			$share->setSharedWithAvatar(CirclesService::getCircleIcon($data['circle_type']))
//				  ->setSharedWithDisplayName(
//					  sprintf(
//						  ' % s(%s, %s)', $name,
//						  $this->l10n->t(DeprecatedCircle::TypeLongString($data['circle_type'])),
//						  $this->miscService->getDisplayName($data['circle_owner'], true)
//					  )
//				  );
//		}
	}


	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->getInt('id', $data) === 0) {
			throw new InvalidItemException();
		}

		$shareTime = new DateTime();
		$shareTime->setTimestamp($this->getInt('shareTime', $data));

		$this->setId($this->get('id', $data))
			 ->setShareType($this->getInt('shareType', $data))
			 ->setPermissions($this->getInt('permissions', $data))
			 ->setItemType($this->get('itemType', $data))
			 ->setItemSource($this->getInt('itemSource', $data))
			 ->setItemTarget($this->get('itemTarget', $data))
			 ->setFileSource($this->getInt('fileSource', $data))
			 ->setFileTarget($this->get('fileTarget', $data))
			 ->setSharedWith($this->get('shareWith', $data))
			 ->setSharedBy($this->get('uidInitiator', $data))
			 ->setShareOwner($this->get('uidOwner', $data))
			 ->setToken($this->get('token', $data))
			 ->setShareTime($shareTime);

		$this->setChildId($this->getInt('childId', $data))
			 ->setChildFileTarget($this->get('childFileTarget', $data))
			 ->setChildPermissions($this->getInt('childPermissions', $data))
			 ->setProviderId(ShareByCircleProvider::IDENTIFIER)
			 ->setStatus(Ishare::STATUS_ACCEPTED);

		try {
			$circle = new Circle();
			$this->setCircle($circle->import($this->getArray('circle', $data)));
		} catch (InvalidItemException $e) {
		}

		try {
			$fileCache = new FileCacheWrapper();
			$this->setFileCache($fileCache->import($this->getArray('fileCache', $data)));
		} catch (InvalidItemException $e) {
		}

		try {
			$owner = new Member();
			$this->setOwner($owner->import($this->getArray('owner', $data)));
		} catch (InvalidItemException $e) {
		}

		try {
			$member = new Member();
			$this->setInitiator($member->import($this->getArray('viewer', $data)));
		} catch (InvalidItemException $e) {
		}

		try {
			$shareToken = new ShareToken();
			$this->setShareToken($shareToken->import($this->getArray('shareToken', $data)));
		} catch (InvalidItemException $e) {
		}

		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return INC22QueryRow
	 */
	public function importFromDatabase(array $data, string $prefix = ''): INC22QueryRow {
		$shareTime = new DateTime();
		$shareTime->setTimestamp($this->getInt($prefix . 'stime', $data));

		$this->setId($this->get($prefix . 'id', $data))
			 ->setShareType($this->getInt($prefix . 'share_type', $data))
			 ->setPermissions($this->getInt($prefix . 'permissions', $data))
			 ->setItemType($this->get($prefix . 'item_type', $data))
			 ->setItemSource($this->getInt($prefix . 'item_source', $data))
			 ->setItemTarget($this->get($prefix . 'item_target', $data))
			 ->setFileSource($this->getInt($prefix . 'file_source', $data))
			 ->setFileTarget($this->get($prefix . 'file_target', $data))
			 ->setSharedWith($this->get($prefix . 'share_with', $data))
			 ->setSharedBy($this->get($prefix . 'uid_initiator', $data))
			 ->setShareOwner($this->get($prefix . 'uid_owner', $data))
			 ->setToken($this->get($prefix . 'token', $data))
			 ->setShareTime($shareTime);

//		if (($password = $this->get('personal_password', $data, '')) !== '') {
//			$share->setPassword($this->get('personal_password', $data, ''));
//		} else if (($password = $this->get('password', $data, '')) !== '') {
//			$share->setPassword($this->get('password', $data, ''));
//		}

		$this->setChildId($this->getInt($prefix . 'child_id', $data))
			 ->setChildFileTarget($this->get($prefix . 'child_file_target', $data))
			 ->setChildPermissions($this->getInt($prefix . 'child_permissions', $data))
			 ->setProviderId(ShareByCircleProvider::IDENTIFIER)
			 ->setStatus(Ishare::STATUS_ACCEPTED);

		$this->getManager()->manageImportFromDatabase($this, $data, $prefix);

		return $this;
	}


	/**
	 * @return string[]
	 */
	public function jsonSerialize(): array {
		$arr = [
			'id' => $this->getId(),
			'shareType' => $this->getShareType(),
			'providerId' => $this->getProviderId(),
			'permissions' => $this->getPermissions(),
			'itemType' => $this->getItemType(),
			'itemSource' => $this->getItemSource(),
			'itemTarget' => $this->getItemTarget(),
			'fileSource' => $this->getFileSource(),
			'fileTarget' => $this->getFileTarget(),
			'status' => $this->getStatus(),
			'shareTime' => $this->getShareTime()->getTimestamp(),
			'sharedWith' => $this->getSharedWith(),
			'sharedBy' => $this->getSharedBy(),
			'shareOwner' => $this->getShareOwner(),
			'token' => $this->getToken(),
			'childId' => $this->getChildId(),
			'childFileTarget' => $this->getChildFileTarget(),
			'childPermissions' => $this->getChildPermissions()
		];

		if ($this->hasOwner()) {
			$arr['owner'] = $this->getOwner();
		}

		if ($this->hasCircle()) {
			$arr['circle'] = $this->getCircle();
		}

		if ($this->hasInitiator()) {
			$arr['viewer'] = $this->getInitiator();
		}

		if ($this->hasFileCache()) {
			$arr['fileCache'] = $this->getFileCache();
		}

		if ($this->hasShareToken()) {
			$arr['shareToken'] = $this->getShareToken();
		}

		return $arr;
	}
}

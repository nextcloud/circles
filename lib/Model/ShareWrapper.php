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

use DateTime;
use JsonSerializable;
use OC;
use OC\Files\Cache\Cache;
use OC\Share20\Share;
use OC\Share20\ShareAttributes;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\ShareByCircleProvider;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IAttributes;
use OCP\Share\IShare;

/**
 * Class ShareWrapper
 *
 * @package OCA\Circles\Model
 */
class ShareWrapper extends ManagedModel implements IDeserializable, IQueryRow, JsonSerializable {
	use TArrayTools;
	use TDeserialize;

	private string $id = '';
	private int $permissions = 0;
	private string $itemType = '';
	private int $itemSource = 0;
	private string $itemTarget = '';
	private int $fileSource = 0;
	private string $fileTarget = '';
	private string $token = '';
	private int $status = 0;
	private string $providerId = '';
	private DateTime $shareTime;
	private string $sharedWith = '';
	private string $sharedBy = '';
	private ?DateTime $expirationDate = null;
	private string $shareOwner = '';
	private int $shareType = 0;
	private int $parent = 0;
	private ?Circle $circle = null;
	private int $childId = 0;
	private string $childFileTarget = '';
	private int $childPermissions = 0;
	private ?FileCacheWrapper $fileCache = null;
	private ?Member $initiator = null;
	private ?Member $owner = null;
	private ?ShareToken $shareToken = null;
	private ?IAttributes $attributes = null;
	private bool $hideDownload = false;

	public function __construct() {
		$this->shareTime = new DateTime();
	}

	public function setId(string $id): self {
		$this->id = $id;

		return $this;
	}

	public function getId(): string {
		return $this->id;
	}

	public function setPermissions(int $permissions): self {
		$this->permissions = $permissions;

		return $this;
	}

	public function getPermissions(): int {
		return $this->permissions;
	}

	public function setItemType(string $itemType): self {
		$this->itemType = $itemType;

		return $this;
	}

	public function getItemType(): string {
		return $this->itemType;
	}

	public function setItemSource(int $itemSource): self {
		$this->itemSource = $itemSource;

		return $this;
	}

	public function getItemSource(): int {
		return $this->itemSource;
	}

	public function setItemTarget(string $itemTarget): self {
		$this->itemTarget = $itemTarget;

		return $this;
	}

	public function getItemTarget(): string {
		return $this->itemTarget;
	}

	public function setFileSource(int $fileSource): self {
		$this->fileSource = $fileSource;

		return $this;
	}

	public function getFileSource(): int {
		return $this->fileSource;
	}

	public function setFileTarget(string $fileTarget): self {
		$this->fileTarget = $fileTarget;

		return $this;
	}

	public function getFileTarget(): string {
		return $this->fileTarget;
	}

	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}

	public function getToken(): string {
		return $this->token;
	}

	public function setStatus(int $status): self {
		$this->status = $status;

		return $this;
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function setProviderId(string $providerId): self {
		$this->providerId = $providerId;

		return $this;
	}

	public function getProviderId(): string {
		return $this->providerId;
	}

	public function setShareTime(DateTime $shareTime): self {
		$this->shareTime = $shareTime;

		return $this;
	}

	public function getShareTime(): DateTime {
		return $this->shareTime;
	}

	public function setSharedWith(string $sharedWith): self {
		$this->sharedWith = $sharedWith;

		return $this;
	}

	public function getSharedWith(): string {
		return $this->sharedWith;
	}

	public function setSharedBy(string $sharedBy): self {
		$this->sharedBy = $sharedBy;

		return $this;
	}

	public function getExpirationDate(): ?DateTime {
		return $this->expirationDate;
	}

	public function setExpirationDate(?DateTime $date):self {
		$this->expirationDate = $date;

		return $this;
	}

	public function getSharedBy(): string {
		return $this->sharedBy;
	}

	public function setShareOwner(string $shareOwner): self {
		$this->shareOwner = $shareOwner;

		return $this;
	}

	public function getShareOwner(): string {
		return $this->shareOwner;
	}

	public function setShareType(int $shareType): self {
		$this->shareType = $shareType;

		return $this;
	}

	public function getShareType(): int {
		return $this->shareType;
	}

	public function setParent(int $parent): self {
		$this->parent = $parent;
		return $this;
	}

	public function getParent(): int {
		return $this->parent;
	}

	public function setCircle(Circle $circle): self {
		$this->circle = $circle;

		return $this;
	}

	public function getCircle(): Circle {
		return $this->circle;
	}

	public function hasCircle(): bool {
		return (!is_null($this->circle));
	}

	public function setChildId(int $childId): self {
		$this->childId = $childId;

		return $this;
	}

	public function getChildId(): int {
		return $this->childId;
	}

	public function setChildFileTarget(string $childFileTarget): self {
		$this->childFileTarget = $childFileTarget;

		return $this;
	}

	public function getChildFileTarget(): string {
		return $this->childFileTarget;
	}

	public function setChildPermissions(int $childPermissions): self {
		$this->childPermissions = $childPermissions;

		return $this;
	}

	public function getChildPermissions(): int {
		return $this->childPermissions;
	}

	public function setFileCache(FileCacheWrapper $fileCache): self {
		$this->fileCache = $fileCache;

		return $this;
	}

	public function getFileCache(): FileCacheWrapper {
		return $this->fileCache;
	}

	public function hasFileCache(): bool {
		return (!is_null($this->fileCache));
	}

	public function setInitiator(Member $initiator): self {
		$this->initiator = $initiator;

		return $this;
	}

	public function getInitiator(): Member {
		return $this->initiator;
	}

	public function hasInitiator(): bool {
		return (!is_null($this->initiator));
	}

	public function setOwner(Member $owner): self {
		$this->owner = $owner;

		return $this;
	}

	public function getOwner(): Member {
		return $this->owner;
	}

	public function hasOwner(): bool {
		return (!is_null($this->owner));
	}

	public function setShareToken(ShareToken $shareToken): self {
		$this->shareToken = $shareToken;

		return $this;
	}

	public function getShareToken(): ShareToken {
		return $this->shareToken;
	}

	public function hasShareToken(): bool {
		return !is_null($this->shareToken);
	}

	public function getAttributes(): ?IAttributes {
		return $this->attributes;
	}

	public function setAttributes(?IAttributes $attributes): self {
		$this->attributes = $attributes;

		return $this;
	}

	public function getHideDownload(): bool {
		return $this->hideDownload;
	}

	public function setHideDownload(bool $hideDownload): self {
		$this->hideDownload = $hideDownload;

		return $this;
	}


	/**
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
		$share->setHideDownload($this->getHideDownload());
		$share->setAttributes($this->getAttributes());
		if ($this->hasShareToken()) {
			$password = $this->getShareToken()->getPassword();
			if ($password !== '') {
				$share->setPassword($password);
			}
		}

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

	private function setShareDisplay(IShare $share, IURLGenerator $urlGenerator): void {
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
			$l10n = \OCP\Server::get(IFactory::class)->get('circles');
			$display = $l10n->t('%s (Circle owned by %s)', [$display, $circle->getOwner()->getDisplayName()]);
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
			 ->setParent($data['parent'] ?? 0)
			 ->setPermissions($this->getInt('permissions', $data))
			 ->setHideDownload($this->getBool('hideDownload', $data))
			 ->setItemType($this->get('itemType', $data))
			 ->setItemSource($this->getInt('itemSource', $data))
			 ->setItemTarget($this->get('itemTarget', $data))
			 ->setFileSource($this->getInt('fileSource', $data))
			 ->setFileTarget($this->get('fileTarget', $data))
			 ->setSharedWith($this->get('sharedWith', $data))
			 ->setSharedBy($this->get('sharedBy', $data))
			 ->setShareOwner($this->get('shareOwner', $data))
			 ->setToken($this->get('token', $data))
			 ->setShareTime($shareTime);

		$this->importAttributesFromDatabase($this->get('attributes', $data));

		try {
			$this->setExpirationDate(new DateTime($this->get('expiration', $data)));
		} catch (\Exception $e) {
		}

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

	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
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

		$this->importAttributesFromDatabase($this->get('attributes', $data));

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
	 * Load from database format (JSON string) to IAttributes
	 * based on \OC\Share20\DefaultShareProvider
	 */
	private function importAttributesFromDatabase(string $data): void {
		if ($data === '') {
			return;
		}

		$attributes = new ShareAttributes();
		$compressedAttributes = json_decode($data, true);
		if (!is_array($compressedAttributes)) {
			return;
		}

		foreach ($compressedAttributes as $compressedAttribute) {
			$attributes->setAttribute(...$compressedAttribute);
		}

		$this->setHideDownload(!($attributes->getAttribute('permissions', 'download') ?? true));
		$this->setAttributes($attributes);
	}


	public function jsonSerialize(): array {
		$arr = [
			'id' => $this->getId(),
			'shareType' => $this->getShareType(),
			'providerId' => $this->getProviderId(),
			'permissions' => $this->getPermissions(),
			'attributes' => ($this->getAttributes() !== null) ? json_encode($this->getAttributes()->toArray()) : null,
			'hideDownload' => $this->getHideDownload(),
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

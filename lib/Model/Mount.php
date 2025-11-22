<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\MountManager\CircleMountManager;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClientService;

/**
 * Class Mount
 *
 * @package OCA\Circles\Model
 */
class Mount extends ManagedModel implements IDeserializable, IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $id = 0;
	private string $mountId = '';
	private string $circleId = '';
	private Member $owner;
	private Member $initiator;
	private int $parent = -1;
	private string $token = '';
	private string $password = '';
	private string $originalMountPoint = '';
	private string $originalMountPointHash = '';
	private ?Mountpoint $alternateMountPoint = null;
	private string $storage;
	private ICloudIdManager $cloudIdManager;
	private IClientService $httpClientService;
	private CircleMountManager $mountManager;
	private string $remote = '';
	private int $remoteShareId = 0;


	/**
	 * Mount constructor.
	 */
	public function __construct() {
	}


	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	public function setId(int $id): self {
		$this->id = $id;

		return $this;
	}


	/**
	 *
	 * @return string
	 */
	public function getMountId(): string {
		return $this->mountId;
	}

	/**
	 * @param string $mountId
	 *
	 * @return Mount
	 */
	public function setMountId(string $mountId): self {
		$this->mountId = $mountId;

		return $this;
	}


	/**
	 *
	 * @return string
	 */
	public function getCircleId(): string {
		return $this->circleId;
	}

	/**
	 * @param string $circleId
	 *
	 * @return Mount
	 */
	public function setCircleId(string $circleId): self {
		$this->circleId = $circleId;

		return $this;
	}

	/**
	 * @param bool $raw
	 *
	 * @return string
	 */
	public function getMountPoint(bool $raw = true): string {
		$mountPoint = $this->getAlternateMountPoint()?->getMountPoint() ?? $this->getOriginalMountPoint();
		if ($raw) {
			return $mountPoint;
		}

		return '/' . $this->getInitiator()->getUserId() . '/files/' . ltrim($mountPoint, '/');
	}

	/**
	 * @return string
	 */
	public function getOriginalMountPoint(): string {
		return $this->originalMountPoint;
	}

	/**
	 * @param string $originalMountPoint
	 *
	 * @return Mount
	 */
	public function setOriginalMountPoint(string $originalMountPoint): self {
		$this->originalMountPoint = $originalMountPoint;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOriginalMountPointHash(): string {
		return $this->originalMountPointHash;
	}

	/**
	 * @param string $originalMountPointHash
	 *
	 * @return Mount
	 */
	public function setOriginalMountPointHash(string $originalMountPointHash): self {
		$this->originalMountPointHash = $originalMountPointHash;

		return $this;
	}

	public function setAlternateMountPoint(Mountpoint $mountPoint): self {
		$this->alternateMountPoint = $mountPoint;

		return $this;
	}

	public function getAlternateMountPoint(): ?Mountpoint {
		return $this->alternateMountPoint;
	}

	/**
	 * @return int
	 */
	public function getParent(): int {
		return $this->parent;
	}

	/**
	 * @param int $parent
	 *
	 * @return Mount
	 */
	public function setParent(int $parent): self {
		$this->parent = $parent;

		return $this;
	}


	/**
	 * @return Member
	 */
	public function getOwner(): Member {
		return $this->owner;
	}

	/**
	 * @param Member $owner
	 *
	 * @return Mount
	 */
	public function setOwner(Member $owner): self {
		$this->owner = $owner;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasInitiator(): bool {
		return !is_null($this->initiator);
	}

	/**
	 * @return Member
	 */
	public function getInitiator(): Member {
		return $this->initiator;
	}

	/**
	 * @param Member $initiator
	 *
	 * @return Mount
	 */
	public function setInitiator(Member $initiator): self {
		$this->initiator = $initiator;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @param string $token
	 *
	 * @return Mount
	 */
	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * @param string $password
	 *
	 * @return Mount
	 */
	public function setPassword(string $password): self {
		$this->password = $password;

		return $this;
	}


	/**
	 * @param ICloudIdManager $cloudIdManager
	 *
	 * @return Mount
	 */
	public function setCloudIdManager(ICloudIdManager $cloudIdManager): self {
		$this->cloudIdManager = $cloudIdManager;

		return $this;
	}

	/**
	 * @return ICloudIdManager
	 */
	public function getCloudIdManager(): ICloudIdManager {
		return $this->cloudIdManager;
	}


	/**
	 * @param IClientService $httpClientService
	 *
	 * @return Mount
	 */
	public function setHttpClientService(IClientService $httpClientService): self {
		$this->httpClientService = $httpClientService;

		return $this;
	}

	/**
	 * @return IClientService
	 */
	public function getHttpClientService(): IClientService {
		return $this->httpClientService;
	}


	/**
	 * @param CircleMountManager $mountManager
	 *
	 * @return Mount
	 */
	public function setMountManager(CircleMountManager $mountManager): self {
		$this->mountManager = $mountManager;

		return $this;
	}

	/**
	 * @return CircleMountManager
	 */
	public function getMountManager(): CircleMountManager {
		return $this->mountManager;
	}

	public function setRemote(string $remote): void {
		$this->remote = $remote;
	}

	public function getRemote(): string {
		return $this->remote;
	}

	public function setRemoteShareId(int $remoteShareId): void {
		$this->remoteShareId = $remoteShareId;
	}

	public function getRemoteShareId(): int {
		return $this->remoteShareId;
	}

	/**
	 * @return array
	 */
	public function toMount(): array {
		$member = $this->getOwner();

		return [
			'owner' => $member->getUserId(),
			'remote' => $member->getRemoteInstance()->getRoot(),
			'token' => $this->getToken(),
			'password' => $this->getPassword(),
			'mountpoint' => $this->getMountPoint(false),
			//			'manager'           => $this->getMountManager(),
			'HttpClientService' => $this->getHttpClientService(),
			'manager' => $this->getMountManager(),
			'cloudId' => $this->getCloudIdManager()->getCloudId(
				$member->getUserId(),
				$member->getRemoteInstance()->getRoot()
			)
		];
	}


	/**
	 * @param ShareWrapper $wrappedShare
	 *
	 * @throws CircleNotFoundException
	 */
	public function fromShare(ShareWrapper $wrappedShare) {
		if (!$wrappedShare->hasCircle()) {
			throw new CircleNotFoundException('ShareWrapper has no Team');
		}

		$circle = $wrappedShare->getCircle();
		$this->setCircleId($circle->getSingleId());
		$this->setOwner($wrappedShare->getOwner());
		$this->setToken($wrappedShare->getToken());
		$this->setParent(-1);
		$this->setOriginalMountPoint($wrappedShare->getFileTarget());
		$this->setOriginalMountPointHash(md5($wrappedShare->getFileTarget()));
		$this->setRemote($wrappedShare->getOwner()->getInstance());
		$this->setRemoteShareId((int)$wrappedShare->getId());
	}


	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 */
	public function import(array $data): IDeserializable {
		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return Mount
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		$this->setId($this->getInt('id', $data));
		$this->setCircleId($this->get('circle_id', $data));
		$this->setToken($this->get('token', $data));
		$this->setParent($this->getInt('parent', $data));
		$this->setOriginalMountPoint($this->get('mountpoint', $data));
		$this->setOriginalMountPointHash($this->get('mountpoint_hash', $data));
		$this->setMountId($this->get('mount_id', $data));
		$this->setRemote($this->get('remote', $data));
		$this->setRemoteShareId($this->getInt('remote_id', $data));

		$this->getManager()->manageImportFromDatabase($this, $data, $prefix);

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$arr = [
			'id' => $this->getId(),
			'circleId' => $this->getCircleId(),
			'mountId' => $this->getMountId(),
			'parent' => $this->getParent(),
			'owner' => $this->getOwner(),
			'token' => $this->getToken(),
			'password' => $this->getPassword(),
			'originalMountPoint' => $this->getOriginalMountPoint(),
			'originalMountPointHash' => $this->getOriginalMountPointHash(),
			'alternateMountPoint' => $this->getAlternateMountPoint()
		];

		if ($this->hasInitiator()) {
			$arr['initiator'] = $this->getInitiator();
			$arr['toMount'] = $this->toMount();
		}

		return $arr;
	}
}

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

namespace OCA\Circles\MountManager\Model;

use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\MountManager\CircleMountManager;
use OCP\Federation\ICloudIdManager;
use OCP\Http\Client\IClientService;

class Mount extends ManagedModel implements IDeserializable, IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $id = 0;
	private string $mountId = '';
	private string $circleId = '';
	private Member $owner;
	private ?Member $initiator = null;
	private int $parent = -1;
	private string $token = '';
	private string $password = '';
	private string $mountPoint = '';
	private string $mountPointHash = '';
	private string $storage = '';
	private ICloudIdManager $cloudIdManager;
	private IClientService $httpClientService;
	private CircleMountManager $mountManager;

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
		if ($raw) {
			return $this->mountPoint;
		}

		return '/' . 'cult' . '/files/' . ltrim($this->mountPoint, '/');
//		return '/' . $this->getInitiator()->getUserId() . '/files/' . ltrim($this->mountPoint, '/');
	}

	/**
	 * @param string $mountPoint
	 *
	 * @return Mount
	 */
	public function setMountPoint(string $mountPoint): self {
		$this->mountPoint = $mountPoint;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getMountPointHash(): string {
		return $this->mountPointHash;
	}

	/**
	 * @param string $mountPointHash
	 *
	 * @return Mount
	 */
	public function setMountPointHash(string $mountPointHash): self {
		$this->mountPointHash = $mountPointHash;

		return $this;
	}


	public function setStorage(string $storage): self {
		$this->storage = $storage;

		return $this;
	}

	public function getStorage(): string {
		return $this->storage;
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

	/**
	 * @return array
	 */
	public function toMount(): array {
		return [];
	}


	/**
	 * @param ShareWrapper $wrappedShare
	 *
	 * @throws CircleNotFoundException
	 */
	public function fromShare(ShareWrapper $wrappedShare) {
		if (!$wrappedShare->hasCircle()) {
			throw new CircleNotFoundException('ShareWrapper has no Circle');
		}

		$circle = $wrappedShare->getCircle();
		$this->setCircleId($circle->getSingleId());
		$this->setOwner($wrappedShare->getOwner());
		$this->setToken($wrappedShare->getToken());
		$this->setParent(-1);
		$this->setMountPoint($wrappedShare->getFileTarget());
		$this->setMountPointHash(md5($wrappedShare->getFileTarget()));
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
		$this->setMountPoint($this->get('mountpoint', $data));
		$this->setMountPointHash($this->get('mountpoint_hash', $data));

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
			'mountPoint' => $this->getMountPoint(),
			'mountPointHash' => $this->getMountPointHash(),
		];

		if ($this->hasInitiator()) {
			$arr['initiator'] = $this->getInitiator();
			$arr['toMount'] = $this->toMount();
		}

		return $arr;
	}
}

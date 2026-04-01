<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\MountManager;

use Exception;
use JsonSerializable;
use OC\Files\Mount\MountPoint;
use OCA\Circles\Exceptions\MountPointConstructionException;
use OCA\Circles\Model\Mount;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IStorageFactory;
use Override;

/**
 * Class CircleMount
 *
 * @package OCA\Circles\MountManager
 */
class CircleMount extends MountPoint implements JsonSerializable {
	use TArrayTools;

	/** @var class-string<IStorage> */
	private string $storageClass;

	/**
	 * @param IStorage|class-string<IStorage> $storage
	 *
	 * @throws MountPointConstructionException
	 */
	public function __construct(
		private Mount $mount,
		IStorage|string $storage,
		?IStorageFactory $loader = null,
	) {
		try {
			parent::__construct(
				$storage,
				$mount->getMountPoint(false),
				$mount->toMount(),
				$loader
			);
		} catch (Exception) {
			throw new MountPointConstructionException();
		}

		$this->storageClass = $storage instanceof IStorage ? get_class($storage) : $storage;
	}

	#[Override]
	public function getMountType(): string {
		return 'shared';
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'mount' => $this->mount,
			'storage' => $this->storageClass
		];
	}
}

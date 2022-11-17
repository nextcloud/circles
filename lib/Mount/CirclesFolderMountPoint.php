<?php

declare(strict_types=1);

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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

namespace OCA\Circles\Mount;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Storage;
use OCA\Circles\Model\Mount\FolderMount;
use OCP\Files\Storage\IStorageFactory;

class CirclesFolderMountPoint extends MountPoint {
	private FolderMount $folderMount;
	private CirclesFolderManager $circlesFolderManager;

	public function __construct(
		FolderMount $folderMount,
		CirclesFolderManager $circlesFolderManager,
		Storage $storage,
		IStorageFactory $loader = null
	) {
		$this->folderMount = $folderMount;
		$this->circlesFolderManager = $circlesFolderManager;
		parent::__construct(
			$storage,
			$folderMount->getAbsoluteMountPoint(),
			[],
			$loader,
			null,
			0,
			MountProvider::class
		);
	}

	/**
	 * @return string
	 */
	public function getMountType(): string {
		return 'circles-folder';
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption($name, $default) {
		$options = $this->getOptions();

		return $options[$name] ?? $default;
	}

	/**
	 * @return array
	 */
	public function getOptions(): array {
		$options = parent::getOptions();
		$options['encrypt'] = false;

		return $options;
	}

	/**
	 * @return string
	 */
	public function getSourcePath(): string {
		return '/' . $this->circlesFolderManager->getRootFolder()->getPath() . '/'
			   . $this->folderMount->getCircleId();
	}
}

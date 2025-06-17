<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Enum\TeamMemberLevel;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getSingleId()
 * @method void setSingleId(string $singleId)
 * @method string getTeamSingleId()
 * @method void setTeamSingleId(string $teamSingleId)
 * @method int getLevel()
 * @method void setLevel(int $level)
 * @method string getInheritanceFirst()
 * @method void setInheritanceFirst(string $inheritanceFirst)
 * @method string getInheritanceLast()
 * @method void setInheritanceLast(string $inheritanceLast)
 * @method int getInheritanceDepth()
 * @method void setInheritanceDepth(int $inheritanceDepth)
 * @method array getInheritancePath()
 * @method void setInheritancePath(array $inheritancePath)
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TeamMembership extends TeamCore implements JsonSerializable {
	protected string $singleId = '';
	protected string $teamSingleId = '';
	protected int $level = 0;
	protected string $inheritanceFirst = '';
	protected string $inheritanceLast = '';
	protected int $inheritanceDepth = 0;
	protected array $inheritancePath = [];

	public function __construct(
	) {
		$this->addType('singleId', 'string');
		$this->addType('teamSingleId', 'string');
		$this->addType('level', 'integer');
		$this->addType('inheritanceFirst', 'string');
		$this->addType('inheritanceLast', 'string');
		$this->addType('inheritanceDepth', 'integer');
		$this->addType('inheritancePath', 'json');
	}

	public function setPath(array $path): void {
		array_shift($path);
		$path = array_reverse($path);
		$this->setInheritancePath($path);

		$depth = count($path);
		$this->setInheritanceDepth($depth);
		if ($depth === 0) {
			return;
		}

		$this->setInheritanceFirst($path[0]);
		$this->setInheritanceLast(array_pop($path));
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		if ($this->isApiVersion(TeamApi::V2)) {
			return [
				'singleId' => $this->getSingleId(),
				'teamSingleId' => $this->getTeamSingleId(),
				'level' => $this->getLevel(),
				'inheritanceFirst' => $this->getInheritanceFirst(),
				'inheritanceLast' => $this->getInheritanceLast(),
				'inheritanceDepth' => $this->getInheritanceDepth(),
				'inheritancePath' => $this->getInheritancePath()
			];
		}

		if ($this->isApiVersion(TeamAPI::V1)) {
			return [];            // TODO: old API compat
		}

		return [
			'singleId' => $this->getSingleId(),
			'teamSingleId' => $this->getTeamSingleId(),
			'level' => $this->getLevel(),
			'inheritanceFirst' => $this->getInheritanceFirst(),
			'inheritanceLast' => $this->getInheritanceLast(),
			'inheritanceDepth' => $this->getInheritanceDepth(),
			'inheritancePath' => $this->getInheritancePath()
		];
	}
}

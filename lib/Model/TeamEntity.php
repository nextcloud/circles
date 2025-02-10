<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Managers\TeamEntityManager;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getSingleId()
 * @method void setSingleId(string $teamSingleId)
 * @method int getType()
 * @method void setType(int $type)
 * @method string getOrigId()
 * @method void setOrigId(string $oridId)
 * @method void setDisplayName(string $displayName)
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TeamEntity extends Entity implements JsonSerializable {
	protected string $singleId = '';
	protected int $type = 0;
	protected string $origId = '';
	protected string $displayName = '';

	private bool $lazyLoadingEnabled = false;
	private ?TeamEntityManager $teamEntityManager = null;

	public function __construct() {
		$this->addType('single_id', 'string');
		$this->addType('type', 'integer');
		$this->addType('orig_id', 'string');
		$this->addType('display_name', 'string');
	}

	public function getTeamEntityType(): TeamEntityType {
		return TeamEntityType::from($this->getType());
	}

	public function setTeamEntityType(TeamEntityType $type): void {
		$this->setType($type->value);
	}

	public function getDisplayName(): string {
		if ($this->displayName === '' && $this->singleId !== '') {
			try {
				$lazyTeamEntity = $this->getTeamEntityManager()?->getTeamEntity($this->singleId);
				$this->displayName = $lazyTeamEntity?->getDisplayName() ?? '';
			} catch (TeamEntityNotFoundException) {
			}
		}

		return $this->displayName;
	}

	public function isTeam(): bool {
		return ($this->getTeamEntityType() === TeamEntityType::TEAM);
	}

	public function asTeam(): Team {
		// TODO: generate a team from current entity;
	}

	public function enableLazyLoading(bool $enabled = true): void {
		$this->lazyLoadingEnabled = $enabled;
	}

	public function getTeamEntityManager(): ?TeamEntityManager {
		if ($this->lazyLoadingEnabled && $this->teamEntityManager === null) {
			$this->teamEntityManager = \OCP\Server::get(TeamEntityManager::class);
		}

		return $this->teamEntityManager;
	}

	public function import(array $data): void {
		$this->setSingleId($data['singleId']);
		$this->setType($data['type']);
		$this->setOrigId($data['origId']);
		$this->setDisplayName($data['displayName']);
	}

	public function isValid(): bool {
		return true; // TODO
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(bool $ignoreApi = false): array {
		if (!$ignoreApi && USING_TEAMS_API === TeamApi::V2) {
			return [
				'singleId' => $this->getSingleId(),
				'displayName' => $this->getDisplayName(),
				'type' => $this->getType(),
			];
		}

		if (!$ignoreApi && USING_TEAMS_API === TeamAPI::V1) {
			return [];            // TODO: old API compat
		}

		return [
			'singleId' => $this->getSingleId(),
			'type' => $this->getType(),
			'origId' => $this->getOrigId(),
			'displayName' => $this->getDisplayName(),
		];
	}
}

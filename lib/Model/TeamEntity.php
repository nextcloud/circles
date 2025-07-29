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
class TeamEntity extends TeamCore implements JsonSerializable {
	protected string $singleId = '';
	protected int $type = 0;
	protected string $origId = '';
	protected string $displayName = '';

	public function __construct(?Team $orig = null) {
		$this->addType('singleId', 'string');
		$this->addType('type', 'integer');
		$this->addType('origId', 'string');
		$this->addType('displayName', 'string');

		if ($orig === null) {
			return;
		}

		if ($orig instanceof Team) {
			$this->setSingleId($orig->getSingleId());
			$this->setTeamEntityType(TeamEntityType::TEAM);
			$this->setOrigId($orig->getSingleId());
			$this->setDisplayName($orig->getDisplayName());
		}
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

		if (!$ignoreApi && $this->isApiVersion(TeamApi::V2)) {
			return [
				'singleId' => $this->getSingleId(),
				'displayName' => $this->getDisplayName(),
				'type' => $this->getType(),
			];
		}

		if (!$ignoreApi && $this->isApiVersion(TeamAPI::V1)) {
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

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
 * @method string getTeamSingleId()
 * @method void setTeamSingleId(string $teamSingleId)
 * @method string getMemberSingleId()
 * @method void setMemberSingleId(string $memberSingleId)
 * @method int getLevel()
 * @method void setLevel(int $level)
 * @method string getInvitedBySingleId()
 * @method void setInvitedBySingleId(string $invitedBySingleId)
 * @method ?array getMetadata()
 * @method void setMetadata(array $metadata)
 * @method int getCreation()
 * @method void setCreation(int $creation)
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TeamMember extends TeamCore implements JsonSerializable {
	protected string $teamSingleId = '';
	protected string $memberSingleId = '';
	protected int $level = 0;
	protected string $invitedBySingleId = '';
	protected array $metadata = [];
	protected int $creation = 0;

	private ?Team $team = null;
	private ?TeamEntity $entity = null;
	private ?TeamEntity $invitedBy = null;

	public function __construct(
	) {
		$this->addType('teamSingleId', 'string');
		$this->addType('memberSingleId', 'string');
		$this->addType('level', 'integer');
		$this->addType('invitedBySingleId', 'string');
		$this->addType('metadata', 'json');
		$this->addType('creation', 'integer');
	}

	public function getTeamMemberLevel(): TeamMemberLevel {
		return TeamMemberLevel::from($this->getLevel());
	}

	public function setTeamMemberLevel(TeamMemberLevel $level): void {
		$this->setLevel($level->value);
	}

	public function getTeam(): ?Team {
		// TODO: team can be null, build from meta (needed ?)
		return $this->team;
	}

	public function setTeam(Team $team): void {
		$this->team = $team;
		$this->setTeamSingleId($team->getSingleId());
	}

	public function getEntity(): ?TeamEntity {
		// TODO: member can be null, build from meta (needed ?)
		return $this->entity;
	}

	public function setEntity(TeamEntity $entity): void {
		$this->entity = $entity;
		$this->setMemberSingleId($entity->getSingleId());
	}

	/**
	 * @throws TeamEntityNotFoundException
	 */
	public function getInvitedBy(): TeamEntity {
		if ($this->invitedBy === null) {
			$invitedBy = new TeamEntity();
			$invitedBy->import($this->metadata['_invitedBy'] ?? []);
			if ($invitedBy->isValid()) {
				$this->invitedBy = $invitedBy;
			}
		}

		if ($this->invitedBy === null) {
			throw new TeamEntityNotFoundException();
		}

		return $this->invitedBy;
	}

	public function setInvitedBy(TeamEntity $invitedBy): void {
		$this->invitedBy = $invitedBy;
		$this->setInvitedBySingleId($invitedBy->getSingleId());
		$this->setMetaValue('_invitedBy', $invitedBy->jsonSerialize(true));
	}

	public function setMetaValue(string $key, string|int|float|bool|array $value): void {
		$metadata = $this->metadata;
		$metadata[$key] = $value;
		$this->setter('metadata', [$metadata]);
	}

	/**
	 * only returns metadata that does not start with underscore '_'
	 */
	public function getFilteredMetadata(): array {
		return array_filter($this->metadata, static fn(string $k): bool => !str_starts_with($k, '_'), ARRAY_FILTER_USE_KEY);
	}

	public function import(array $data): void {
		$this->setTeamSingleId($data['teamSingleId'] ?? '');
		$this->setMemberSingleId($data['memberSingleId'] ?? '');
		$this->setLevel($data['level'] = 0);
		$this->setMetadata($data['metadata'] ?? []);
		$this->setCreation($data['creation'] ?? 0);
	}

	public function isValid(): bool {
		return ($this->getTeamSingleId() !== '' && $this->getMemberSingleId() !== '');
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		if ($this->isApiVersion(TeamApi::V2)) {
			return [
				'teamId' => $this->getTeamSingleId(),
				'singleId' => $this->getMemberSingleId(),
				'displayName' => $this->getEntity()?->getDisplayName() ?? '',
				'level' => $this->getLevel(),
				'metadata' => $this->getFilteredMetadata(),
				'invitedBy' => $this->getInvitedBy(),
				'creation' => $this->getCreation()
			];
		}

		if ($this->isApiVersion(TeamAPI::V1)) {
			return [];            // TODO: old API compat
		}

		return [
			'teamSingleId' => $this->getTeamSingleId(),
			'memberSingleId' => $this->getMemberSingleId(),
			'team' => $this->getTeam(),
			'member' => $this->getEntity(),
			'level' => $this->getLevel(),
			'metadata' => $this->getMetadata(),
			'creation' => $this->getCreation(),
			'invitedBy' => $this->metadata['_invitedBy'] ?? [],
		];
	}
}

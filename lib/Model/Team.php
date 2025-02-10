<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Exceptions\TeamOwnerNotFoundException;
use OCA\Circles\Managers\TeamMemberManager;
use OCP\AppFramework\Db\Entity;

/**
 * @method void setSingleId(string $singleId)
 * @method string getSingleId()
 * @method void setDisplayName(string $displayName)
 * @method string getDisplayName()
 * @method void setSanitizedName(string $sanitizedName)
 * @method string getSanitizedName()
 * @method void setConfig(int $config)
 * @method int getConfig()
 * @method ?array getSettings()
 * @method void setSettings(array $settings)
 * @method ?array getMetadata()
 * @method void setMetadata(array $metadata)
 * @method int getCreation()
 * @method void setCreation(int $creation)
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Team extends Entity implements JsonSerializable {
	protected string $singleId = '';
	protected string $displayName = '';
	protected string $sanitizedName = '';
	protected int $config = 0;
	protected ?array $settings = null;
	protected ?array $metadata = null;
	protected int $creation = 0;

	private ?TeamEntity $owner = null;
	/** @var null|TeamMember[] */
	private ?array $members = null;
	private bool $lazyLoadingEnabled = false;
	private ?TeamMemberManager $teamMemberManager = null;

	public function __construct() {
		$this->addType('single_id', 'string');
		$this->addType('display_name', 'string');
		$this->addType('sanitized_name', 'string');
		$this->addType('config', 'integer');
		$this->addType('settings', 'json');
		$this->addType('metadata', 'json');
		$this->addType('creation', 'integer');
	}

	public function getOwner(): TeamEntity {
		if ($this->owner === null) {
			$owner = new TeamEntity();
			$owner->import($this->metadata['_owner'] ?? []);
			if ($owner->isValid()) {
				$this->owner = $owner;
			}
		}

		if ($this->owner === null) {
			throw new TeamOwnerNotFoundException();
		}

		return $this->owner;
	}

	public function setOwner(TeamEntity $owner): void {
		$this->owner = $owner;
		$this->setMetaValue('_owner', $owner->jsonSerialize());
	}

	/**
	 * only returns metadata that does not start with underscore '_'
	 */
	public function getFilteredMetadata(): array {
		return array_filter($this->metadata, static fn(string $k): bool => !str_starts_with($k, '_'), ARRAY_FILTER_USE_KEY);
	}

	public function setMetaValue(string $key, string|int|float|bool|array $value): void {
		$metadata = $this->metadata;
		$metadata[$key] = $value;
		$this->setter('metadata', [$metadata]);
	}

	public function setMembers(array $members): void {
		$this->members = $members;
	}

	public function getMembers(): ?array {
		if (null === $this->members) {
			$this->members = $this->getTeamMemberManager()?->getMembersFromTeam($this->getSingleId());
		}
		return $this->members ?? [];
	}

	public function enableLazyLoading(bool $enabled = true): void {
		$this->lazyLoadingEnabled = $enabled;
	}

	public function getTeamMemberManager(): ?TeamMemberManager {
		if ($this->lazyLoadingEnabled && $this->teamMemberManager === null) {
			$this->teamMemberManager = \OCP\Server::get(TeamMemberManager::class);
		}

		return $this->teamMemberManager;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		if (USING_TEAMS_API === TeamApi::V2) {
			return [
				'singleId' => $this->getSingleId(),
				'displayName' => $this->getDisplayName(),
				'sanitizedName' => $this->getSanitizedName(),
				'config' => $this->getConfig(),
				'settings' => $this->getSettings(),
				'metadata' => $this->getFilteredMetadata(),
				'members' => $this->getMembers(),
				'creation' => $this->getCreation(),
				'owner' => $this->metadata['_owner'] ?? []
			];
		}

		if (USING_TEAMS_API === TeamApi::V1) {
			return [];
		}

		return [
			'singleId' => $this->getSingleId(),
			'displayName' => $this->getDisplayName(),
			'sanitizedName' => $this->getSanitizedName(),
			'config' => $this->getConfig(),
			'settings' => $this->getSettings(),
			'metadata' => $this->getMetadata(),
			'members' => $this->getMembers(),
			'creation' => $this->getCreation(),
			'owner' => $this->metadata['_owner'] ?? []
		];
	}
}

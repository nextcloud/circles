<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles;

use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ShareWrapperService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Teams\ITeamResourceProvider;
use OCP\Teams\TeamResource;

class FileSharingTeamResourceProvider implements ITeamResourceProvider {
	public function __construct(
		private IL10N $l10n,
		private ?CirclesManager $circlesManager,
		private ShareWrapperService $shareByCircleProvider,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getId(): string {
		return 'files';
	}

	public function getName(): string {
		return $this->l10n->t('Files');
	}

	public function getIconSvg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-folder" viewBox="0 0 24 24"><path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" /></svg>';
	}

	public function getSharedWith(string $teamId): array {
		if (!$this->circlesManager) {
			return [];
		}

		$shares = $this->shareByCircleProvider->getSharesToCircle($teamId);
		return $this->convertWrappedShareToResource($shares);
	}

	/**
	 * @return array<string, TeamResource[]>
	 */
	public function getSharedWithList(array $teams): array {
		$data = $shares = [];
		foreach ($this->shareByCircleProvider->getSharesToCircles($teams) as $share) {
			if (!array_key_exists($share->getId(), $shares)) {
				$shares[$share->getSharedWith()] = [];
			}
			$shares[$share->getSharedWith()][] = $share;
		}

		foreach ($teams as $teamId) {
			$data[$teamId] = $this->convertWrappedShareToResource($shares[$teamId]);
		}

		return $data;
	}

	/**
	 * convert list of ShareWrapper to TeamResource
	 *
	 * @param ShareWrapper[] $shares
	 * @return TeamResource[]
	 */
	private function convertWrappedShareToResource(array $shares): array {
		usort($shares, function ($a, $b) {
			return (int)($b->getItemType() === 'folder') - (int)($a->getItemType() === 'folder');
		});
		return array_map(function (ShareWrapper $shareWrapper) {
			$isFolder = $shareWrapper->getItemType() === 'folder';
			return new TeamResource(
				$this,
				(string)$shareWrapper->getFileSource(),
				basename($shareWrapper->getFileTarget()),
				$this->urlGenerator->getAbsoluteURL('/index.php/f/' . $shareWrapper->getFileSource()),
				iconSvg: $isFolder ? '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-folder" viewBox="0 0 24 24"><path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" /></svg>' : null,
				iconURL: !$isFolder ?
					$this->urlGenerator->linkToRouteAbsolute('core.preview.getPreviewByFileId', ['fileId' => $shareWrapper->getFileSource(), 'mimeFallback' => true, ])
					: null,
			);
		}, $shares);
	}

	public function isSharedWithTeam(string $teamId, string $resourceId): bool {
		if (!$this->circlesManager) {
			return false;
		}

		return count(array_filter($this->getSharedWith($teamId), function (TeamResource $resource) use ($resourceId) {
			return $resource->getId() === $resourceId;
		})) !== 0;
	}

	public function getTeamsForResource(string $resourceId): array {
		if (!$this->circlesManager) {
			return [];
		}

		$shares = $this->shareByCircleProvider->getSharesByFileId((int)$resourceId);

		return array_map(function ($share) {
			return $share->getSharedWith();
		}, $shares);
	}
}

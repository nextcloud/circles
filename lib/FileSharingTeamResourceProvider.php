<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
		private IURLGenerator $urlGenerator
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

<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\FileSharingTeamResourceProvider;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class TeamResourceService {
	
	public function __construct(
		private FileSharingTeamResourceProvider $resourceProvider,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Get ALL resources for a team using real data with previews
	 */
	public function getAllTeamResources(string $teamId): array {
		try {
			// Get real shared resources for this team
			$teamResources = $this->resourceProvider->getSharedWith($teamId);
			
			$resources = [];
			foreach ($teamResources as $resource) {
				try {
					$resourceId = $resource->getId();
					$resourceLabel = $resource->getLabel();
					$resourceUrl = $resource->getUrl();
					
					// Use the URLs and icons that FileSharingTeamResourceProvider already provides
					// This works correctly for all users with team access, including non-owners
					$iconUrl = $resource->getIconURL();
					$iconSvg = $resource->getIconSvg();
					
					// Determine if it's a folder based on having iconSvg (folders have SVG, files have iconURL)
					$isFolder = !empty($iconSvg) && empty($iconUrl);
					
					// Use the appropriate icon
					$previewUrl = $iconUrl ?: $this->urlGenerator->imagePath('core', 'filetypes/folder.svg');
					$fallbackIcon = $iconUrl ?: $this->urlGenerator->imagePath('core', 'filetypes/folder.svg');
					
					$resources[] = [
						'id' => $resourceId,
						'name' => $resourceLabel,
						'type' => $isFolder ? 'folder' : 'file',
						'iconUrl' => $previewUrl,
						'fallbackIcon' => $fallbackIcon,
						'url' => $resourceUrl,
					];
					
				} catch (\Exception $e) {
					$this->logger->warning('Failed to process team resource: ' . $e->getMessage());
					continue;
				}
			}
			
			return $resources;
			
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch team resources: ' . $e->getMessage());
			return [];
		}
	}
}

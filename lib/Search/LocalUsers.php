<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Search;

use OCA\Circles\ISearch;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SearchResult;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\Collaboration\Collaborators\ISearch as ICollaboratorSearch;
use OCP\IUserManager;
use OCP\Server;
use OCP\Share\IShare;

class LocalUsers implements ISearch {
	use TArrayTools;


	/** @var ICollaboratorSearch */
	private $search;

	/** @var ConfigService */
	private $configService;


	/**
	 * LocalUsers constructor.
	 *
	 * @param ICollaboratorSearch $search
	 * @param ConfigService $configService
	 */
	public function __construct(
		ICollaboratorSearch $search,
		ConfigService $configService,
	) {
		$this->search = $search;
		$this->configService = $configService;
	}


	/**
	 * {@inheritdoc}
	 */
	public function search($needle): array {
		$result = [];
		$userManager = Server::get(IUserManager::class);

		if ($this->configService->getAppValue(ConfigService::CIRCLES_SEARCH_FROM_COLLABORATOR) === '1') {
			return $this->searchFromCollaborator($needle);
		}

		$users = $userManager->search($needle);
		foreach ($users as $user) {
			$result[] = new SearchResult(
				$user->getUID(),
				Member::TYPE_USER,
				'',
				['display' => $userManager->getDisplayName($user->getUID())]
			);
		}

		return $result;
	}


	/**
	 * @param $search
	 *
	 * @return array
	 */
	private function searchFromCollaborator($search): array {
		[$temp, $hasMore] = $this->search->search($search, [IShare::TYPE_USER, IShare::TYPE_EMAIL], false, 50, 0);

		$result = array_merge($temp['exact']['users'], $temp['users']);
		$parsed = [];
		foreach ($result as $entry) {
			$parsed[] =
				new SearchResult(
					$this->get('value.shareWith', $entry),
					Member::TYPE_USER,
					'',
					['display' => $this->get('label', $entry)]
				);
		}

		return $parsed;
	}
}

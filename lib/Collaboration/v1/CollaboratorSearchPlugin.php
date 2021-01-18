<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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

namespace OCA\Circles\Collaboration\v1;

use OC\User\NoUserException;
use OCA\Circles\Api\v1\Circles;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\QueryException;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\IL10N;
use OCP\Share;

class CollaboratorSearchPlugin implements ISearchPlugin {


	/** @var IL10N */
	private $l10n;

	/** @var MiscService */
	private $miscService;


	/**
	 * CollaboratorSearchPlugin constructor.
	 *
	 * @throws QueryException
	 */
	public function __construct() {
		$this->l10n = \OC::$server->getL10N(Application::APP_ID);
		$this->miscService = \OC::$server->query(MiscService::class);
	}


	/**
	 * {@inheritdoc}
	 */
	public function search($search, $limit, $offset, ISearchResult $searchResult) {
		$wide = $exact = [];

		$circles = Circles::listCircles(DeprecatedCircle::CIRCLES_ALL, $search, Member::LEVEL_MEMBER);
		foreach ($circles as $circle) {
			try {
				$entry = $this->addResultEntry($circle);
			} catch (NoUserException $e) {
				continue;
			}

			if (strtolower($circle->getName()) === strtolower($search)) {
				$exact[] = $entry;
			} else {
				$wide[] = $entry;
			}
		}

		$type = new SearchResultType('circles');
		$searchResult->addResultSet($type, $wide, $exact);
	}


	/**
	 * @param DeprecatedCircle $circle
	 *
	 * @return array
	 * @throws NoUserException
	 */
	private function addResultEntry(DeprecatedCircle $circle) {

		return [
			'label' => sprintf(
				'%s (%s, %s)',
				$circle->getName(),
				$this->l10n->t($circle->getTypeLongString()),
				$this->miscService->getDisplayName(
					$circle->getOwner()
						   ->getUserId(), true
				)
			),
			'value' => [
				'shareType'   => Share::SHARE_TYPE_CIRCLE,
				'shareWith'   => $circle->getUniqueId(),
				'circleInfo'  => $circle->getInfo(),
				'circleOwner' => $circle->getOwner()
										->getCachedName()
			],
		];
	}
}

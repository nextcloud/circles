<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles\Collaboration\v2;


use daita\MySmallPhpTools\Model\SimpleDataStore;
use OC\Share20\Share;
use OC\User\NoUserException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;


/**
 * Class CollaboratorSearchPlugin
 *
 * @package OCA\Circles\Collaboration\v2
 */
class CollaboratorSearchPlugin implements ISearchPlugin {


	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;


	/**
	 * CollaboratorSearchPlugin constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 */
	public function __construct(FederatedUserService $federatedUserService, CircleService $circleService) {
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
	}


	/**
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @param ISearchResult $searchResult
	 *
	 * @return bool
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		$this->federatedUserService->initCurrentUser();

		$wide = $exact = [];

		$filterCircle = new Circle();
		$filterCircle->setName($search)
					 ->setDisplayName($search);

		$filterMember = new Member();
		$filterMember->importFromIFederatedUser($this->federatedUserService->getCurrentUser());
		$filterMember->setLevel(Member::LEVEL_MEMBER);

		$circles = $this->circleService->getCircles(
			$filterCircle, $filterMember,
			new SimpleDataStore(
				[
					'limit'  => $limit,
					'offset' => $offset
				]
			)
		);

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

		return false;
	}


	/**
	 * @param Circle $circle
	 *
	 * @return array
	 */
	private function addResultEntry(Circle $circle): array {
		return [
			'label'                => $circle->getDisplayName(),
			'shareWithDescription' => $circle->getOwner()->getDisplayName(),
			'value'                => [
				'shareType' => Share::TYPE_CIRCLE,
				'shareWith' => $circle->getId(),
				'circle'    => $circle
			],
		];
	}

}


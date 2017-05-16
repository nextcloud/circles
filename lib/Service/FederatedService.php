<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

namespace OCA\Circles\Service;


use OCA\Circles\BackgroundJob\FederatedCircle;
use OCA\Circles\Db\CirclesMapper;
use OCA\Circles\Db\MembersMapper;
use OCA\Circles\Exceptions\FederatedCircleLinkFormatException;
use OCA\Circles\Exceptions\FederatedSourceCircleTypeNotValid;
use OCA\Circles\Exceptions\MemberIsNotAdminException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IL10N;

class FederatedService {

	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;

	/** @var CirclesService */
	private $circlesService;

	/** @var CirclesMapper */
	private $dbCircles;

	/** @var MembersMapper */
	private $dbMembers;

	/** @var MiscService */
	private $miscService;


	/**
	 * CirclesService constructor.
	 *
	 * @param $userId
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param DatabaseService $databaseService
	 * @param CirclesService $circlesService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		DatabaseService $databaseService,
		CirclesService $circlesService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->circlesService = $circlesService;
		$this->miscService = $miscService;

		$this->dbCircles = $databaseService->getCirclesMapper();
		$this->dbMembers = $databaseService->getMembersMapper();
	}


	/**
	 * link to a circle.
	 *
	 * @param int $circleId
	 * @param string $link
	 *
	 * @return bool
	 * @throws FederatedCircleLinkFormatException
	 * @throws FederatedSourceCircleTypeNotValid
	 * @throws MemberIsNotAdminException
	 */
	public function linkCircle($circleId, $link) {


		if (strpos($link, '@') === false) {
			throw new FederatedCircleLinkFormatException("Link format is not valid");
		}

		// check circle type (personal can't link)
		$circle = $this->circlesService->detailsCircle($circleId);
		if (!$circle->getUser()
					->isLevel(Member::LEVEL_ADMIN)
		) {
			throw new MemberIsNotAdminException("You are not admin of this circle");
		}

		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
			throw new FederatedSourceCircleTypeNotValid(
				"The type of this circle cannot be linked to an other circle"
			);
		}

		list($linkCircle, $linkCloud) = explode("@", $link, 2);
		$this->requestLinkWithCircle($circle, $linkCircle, $linkCloud);

		return true;
	}


	private function requestLinkWithCircle(Circle $circle, $linkCircle, $linkCloud) {
		$this->miscService->log(
			"create link : " . $linkCircle . ' - ' . $linkCloud . ' - ' . var_export(
				$circle->getId(), true
			)
		);
	}


}
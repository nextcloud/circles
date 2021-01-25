<?php declare(strict_types=1);


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


namespace OCA\Circles\Controller;


use Exception;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\MembersService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;


/**
 * Class OcsController
 *
 * @package OCA\Circles\Controller
 */
class OcsController extends Controller {


	/** @var IUserSession */
	private $userSession;

	/** @var CirclesService */
	private $circlesService;

	/** @var MembersService */
	private $membersService;


	/**
	 * OcsController constructor.
	 *
	 * @param $appName
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param CirclesService $circlesService
	 * @param MembersService $membersService
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserSession $userSession,
		CirclesService $circlesService,
		MembersService $membersService
	) {
		parent::__construct($appName, $request);

		$this->userSession = $userSession;
		$this->circlesService = $circlesService;
		$this->membersService = $membersService;
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function circles(): DataResponse {
		$user = $this->userSession->getUser();
		try {
			$circles = $this->circlesService->listCircles($user->getUID(), Circle::CIRCLES_ALL);

			$circles = array_map(
				function(Circle $circle) {
					$circle->setSettings([]);

					return $circle;
				}, $circles
			);

			return new DataResponse($circles);
		} catch (Exception $e) {
			return new DataResponse(['message' => $$e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 */
	public function members(string $circleId): DataResponse {
		try {
			$circle = $this->circlesService->detailsCircle($circleId);
			$members = ($circle->getMembers() === null) ? [] : $circle->getMembers();

			return new DataResponse($members);
		} catch (Exception $e) {
			return new DataResponse(['message' => $$e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}

}

